<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SftpConnection;
use App\Services\Sftp\SftpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SftpConnectionController extends Controller
{
    public function __construct(
        private SftpService $sftpService,
    ) {}

    /**
     * List all SFTP connections for the current tenant.
     */
    public function index(): JsonResponse
    {
        $connections = SftpConnection::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $connections,
        ]);
    }

    /**
     * Create a new SFTP connection.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'auth_type' => 'required|in:password,private_key',
            'password' => 'required_if:auth_type,password|nullable|string',
            'private_key' => 'required_if:auth_type,private_key|nullable|string',
            'default_remote_path' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $connection = new SftpConnection();
        $connection->fill([
            'name' => $validated['name'],
            'host' => $validated['host'],
            'port' => $validated['port'] ?? 22,
            'username' => $validated['username'],
            'auth_type' => $validated['auth_type'],
            'default_remote_path' => $validated['default_remote_path'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Set credentials via encrypted mutators
        if ($validated['auth_type'] === 'password') {
            $connection->password = $validated['password'];
        } else {
            $connection->private_key = $validated['private_key'];
        }

        $connection->save();

        return response()->json([
            'success' => true,
            'data' => $connection,
            'message' => 'SFTP connection created successfully.',
        ], 201);
    }

    /**
     * Update an existing SFTP connection.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $connection = SftpConnection::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'host' => 'sometimes|string|max:255',
            'port' => 'sometimes|integer|min:1|max:65535',
            'username' => 'sometimes|string|max:255',
            'auth_type' => 'sometimes|in:password,private_key',
            'password' => 'nullable|string',
            'private_key' => 'nullable|string',
            'default_remote_path' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $connection->fill(collect($validated)->except(['password', 'private_key'])->toArray());

        // Update credentials if provided
        if (isset($validated['password'])) {
            $connection->password = $validated['password'];
        }
        if (isset($validated['private_key'])) {
            $connection->private_key = $validated['private_key'];
        }

        $connection->save();

        return response()->json([
            'success' => true,
            'data' => $connection,
            'message' => 'SFTP connection updated successfully.',
        ]);
    }

    /**
     * Delete an SFTP connection.
     */
    public function destroy(string $id): JsonResponse
    {
        $connection = SftpConnection::findOrFail($id);
        $connection->delete();

        return response()->json([
            'success' => true,
            'message' => 'SFTP connection deleted successfully.',
        ]);
    }

    /**
     * Test an SFTP connection.
     */
    public function test(string $id): JsonResponse
    {
        $connection = SftpConnection::findOrFail($id);
        $result = $this->sftpService->testConnection($connection);

        return response()->json([
            'success' => $result['success'],
            'data' => $result,
            'message' => $result['message'],
        ]);
    }
}
