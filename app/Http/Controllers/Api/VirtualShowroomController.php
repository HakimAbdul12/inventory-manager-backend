<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VirtualShowroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VirtualShowroomController extends Controller
{
    /**
     * List user's virtual showrooms.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id ?? 'demo_user';

        $showrooms = VirtualShowroom::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($showroom) {
                return [
                    'id' => $showroom->id,
                    'name' => $showroom->name,
                    'description' => $showroom->description,
                    'imageUrl' => Storage::url($showroom->image_path),
                    'createdAt' => $showroom->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $showrooms,
        ]);
    }

    /**
     * Upload a new virtual showroom image.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()?->id ?? 'demo_user';

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:10240', // 10MB max
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'showroom_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = "virtual-showrooms/{$userId}/{$filename}";

            Storage::disk('public')->put($path, file_get_contents($file));

            $showroom = VirtualShowroom::create([
                'user_id' => $userId,
                'image_path' => $path,
                'name' => $request->input('name') ?? $file->getClientOriginalName(),
                'description' => $request->input('description'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Virtual showroom uploaded successfully',
                'data' => [
                    'id' => $showroom->id,
                    'name' => $showroom->name,
                    'description' => $showroom->description,
                    'imageUrl' => Storage::url($showroom->image_path),
                    'createdAt' => $showroom->created_at->toIso8601String(),
                ],
            ], 201);
        }

        return response()->json(['success' => false, 'message' => 'No image file provided'], 400);
    }

    /**
     * Delete a virtual showroom.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $userId = $request->user()?->id ?? 'demo_user';

        $showroom = VirtualShowroom::where('user_id', $userId)->find($id);

        if (!$showroom) {
            return response()->json(['success' => false, 'message' => 'Showroom not found'], 404);
        }

        if (Storage::disk('public')->exists($showroom->image_path)) {
            Storage::disk('public')->delete($showroom->image_path);
        }

        $showroom->delete();

        return response()->json(['success' => true, 'message' => 'Virtual showroom deleted']);
    }
}
