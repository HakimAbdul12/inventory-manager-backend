<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReconditioningTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $id): JsonResponse
    {
        $inventoryItem = InventoryItem::where('tenant_id', app('current_tenant')->id)
            ->findOrFail($id);

        $tasks = $inventoryItem->reconditioningTasks()->get();

        return response()->json($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $inventoryItem = InventoryItem::where('tenant_id', app('current_tenant')->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'task_name' => 'required|string|max:255',
            'status' => 'required|string|in:pending,in_progress,completed,cancelled',
            'assigned_vendor' => 'nullable|string|max:255',
            'cost' => 'required|numeric|min:0',
            'priority' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $task = $inventoryItem->reconditioningTasks()->create(array_merge($validated, [
            'tenant_id' => app('current_tenant')->id,
        ]));

        return response()->json($task, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $inventoryId, string $taskId): JsonResponse
    {
        $inventoryItem = InventoryItem::where('tenant_id', app('current_tenant')->id)
            ->findOrFail($inventoryId);

        $task = $inventoryItem->reconditioningTasks()
            ->where('tenant_id', app('current_tenant')->id)
            ->findOrFail($taskId);

        $validated = $request->validate([
            'task_name' => 'required|string|max:255',
            'status' => 'required|string|in:pending,in_progress,completed,cancelled',
            'assigned_vendor' => 'nullable|string|max:255',
            'cost' => 'required|numeric|min:0',
            'priority' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $task->update($validated);

        return response()->json($task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $inventoryId, string $taskId): JsonResponse
    {
        $inventoryItem = InventoryItem::where('tenant_id', app('current_tenant')->id)
            ->findOrFail($inventoryId);

        $task = $inventoryItem->reconditioningTasks()
            ->where('tenant_id', app('current_tenant')->id)
            ->findOrFail($taskId);

        $task->delete();

        return response()->json(['message' => 'Reconditioning task deleted successfully']);
    }
}
