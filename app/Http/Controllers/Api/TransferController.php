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

        $transfer->load(['sender:id,name,company_name', 'recipient:id,name,company_name']);
        \App\Events\TransferUpdated::dispatch($transfer);

        return response()->json(['message' => 'Transfer request sent', 'data' => $transfer], 201);
    }

    public function accept(string $id): JsonResponse
    {
        $transfer = Transfer::where('id', $id)->where('recipient_id', auth()->id())->firstOrFail();

        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Transfer already processed'], 400);
        }

        $transfer->update(['status' => 'processing']);
        ProcessTransferJob::dispatch($transfer)->onQueue('inventory');

        $transfer->load(['sender:id,name,company_name', 'recipient:id,name,company_name']);
        \App\Events\TransferUpdated::dispatch($transfer);

        return response()->json(['message' => 'Transfer accepted and processing started', 'data' => $transfer]);
    }

    public function decline(string $id): JsonResponse
    {
        $transfer = Transfer::where('id', $id)->where('recipient_id', auth()->id())->firstOrFail();

        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Transfer already processed'], 400);
        }

        $transfer->update(['status' => 'declined']);

        $transfer->load(['sender:id,name,company_name', 'recipient:id,name,company_name']);
        \App\Events\TransferUpdated::dispatch($transfer);

        return response()->json(['message' => 'Transfer declined', 'data' => $transfer]);
    }

    public function cancel(string $id): JsonResponse
    {
        $transfer = Transfer::where('id', $id)->where('sender_id', auth()->id())->firstOrFail();

        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Cannot cancel processed transfer'], 400);
        }

        $transfer->update(['status' => 'cancelled']);

        $transfer->load(['sender:id,name,company_name', 'recipient:id,name,company_name']);
        \App\Events\TransferUpdated::dispatch($transfer);

        return response()->json(['message' => 'Transfer cancelled', 'data' => $transfer]);
    }
    public function items(Request $request, string $id): JsonResponse
    {
        $transfer = Transfer::where('id', $id)
            ->where(function ($query) {
                $query->where('sender_id', auth()->id())
                    ->orWhere('recipient_id', auth()->id());
            })
            ->firstOrFail();

        $items = \App\Models\InventoryItem::whereIn('id', $transfer->inventory_ids ?? [])
            ->with(['category'])
            ->withCount('images')
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'pagination' => [
                'currentPage' => $items->currentPage(),
                'lastPage' => $items->lastPage(),
                'perPage' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }
}
