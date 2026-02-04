<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Models\User;
use App\Jobs\ProcessTransferJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string']);
        $dealer = User::where('dealer_code', $request->code)
            ->where('id', '!=', auth()->id()) // Exclude self
            ->select('id', 'name', 'company_name', 'dealer_code')
            ->first();

        if (!$dealer) {
            return response()->json(['message' => 'Dealer not found'], 404);
        }

        return response()->json(['data' => $dealer]);
    }

    public function index(): JsonResponse
    {
        $userId = auth()->id();
        $transfers = Transfer::with(['sender:id,name,company_name', 'recipient:id,name,company_name'])
            ->where('sender_id', $userId)
            ->orWhere('recipient_id', $userId)
            ->latest()
            ->paginate(10); // Or get()

        return response()->json($transfers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'inventory_ids' => 'required|array',
            'inventory_ids.*' => 'exists:inventory_items,id',
            'type' => 'required|in:move,duplicate',
        ]);

        $transfer = Transfer::create([
            'sender_id' => auth()->id(),
            'recipient_id' => $validated['recipient_id'],
            'inventory_ids' => $validated['inventory_ids'],
            'type' => $validated['type'],
            'status' => 'pending',
        ]);

        // TODO: Broadcast event

        return response()->json(['message' => 'Transfer request sent', 'data' => $transfer], 201);
    }

    public function accept(string $id): JsonResponse
    {
        $transfer = Transfer::where('id', $id)->where('recipient_id', auth()->id())->firstOrFail();

        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Transfer already processed'], 400);
        }

        $transfer->update(['status' => 'processing']);
        ProcessTransferJob::dispatch($transfer);

        return response()->json(['message' => 'Transfer accepted and processing started', 'data' => $transfer]);
    }

    public function decline(string $id): JsonResponse
    {
        $transfer = Transfer::where('id', $id)->where('recipient_id', auth()->id())->firstOrFail();

        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Transfer already processed'], 400);
        }

        $transfer->update(['status' => 'declined']);

        return response()->json(['message' => 'Transfer declined', 'data' => $transfer]);
    }
}
