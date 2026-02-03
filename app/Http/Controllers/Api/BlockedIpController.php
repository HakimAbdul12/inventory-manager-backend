<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockedIpController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(BlockedIp::all());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ip_address' => 'required|string|ip|unique:blocked_ips,ip_address',
            'reason' => 'nullable|string',
        ]);

        $blockedIp = BlockedIp::create([
            'ip_address' => $request->ip_address,
            'reason' => $request->reason,
            'blocked_by_user_id' => $request->user()->id,
        ]);

        return response()->json($blockedIp, 201);
    }

    public function destroy(string $ip_address): JsonResponse
    {
        $deleted = BlockedIp::where('ip_address', $ip_address)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'IP address not found in blocklist.'], 404);
        }

        return response()->json(['message' => 'IP address unblocked successfully.']);
    }
}
