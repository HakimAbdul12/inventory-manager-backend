<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    /**
     * List all users with search and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('dealer_code', 'like', "%{$search}%");
            });
        }

        $users = $query->with('roles')->paginate(10);

        $users->through(function ($user) {
            $roles = $user->getRoleNames();
            unset($user->roles);
            $user->setAttribute('roles', $roles);
            return $user;
        });

        return response()->json($users);
    }

    /**
     * Toggle the blocked status of a user.
     */
    public function toggleBlock(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('admin')) {
            return response()->json(['message' => 'Cannot block an admin user.'], 403);
        }

        if ($user->blocked_at) {
            $user->blocked_at = null;
            $message = 'User unblocked successfully.';
        } else {
            $user->blocked_at = now();
            $message = 'User blocked successfully.';
        }

        $user->save();

        return response()->json(['message' => $message, 'user' => $user]);
    }

    /**
     * Delete a user.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('admin')) {
            return response()->json(['message' => 'Cannot delete an admin user.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
