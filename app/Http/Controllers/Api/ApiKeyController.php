<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiKeyController extends Controller
{
    /**
     * List all active API tokens for the user.
     */
    public function index(Request $request)
    {
        $tokens = $request->user()->tokens()
            ->select(['id', 'name', 'last_used_at', 'created_at', 'abilities'])
            ->orderBy('last_used_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tokens);
    }

    /**
     * Create a new API token.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tokenName = $request->input('name');
        // Create a token with read access to inventory
        $token = $request->user()->createToken($tokenName, ['inventory:read']);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_info' => $token->accessToken,
        ], 201);
    }

    /**
     * Revoke a specific API token.
     */
    public function destroy(Request $request, string $tokenId)
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        return response()->json(['message' => 'Token revoked successfully']);
    }
}
