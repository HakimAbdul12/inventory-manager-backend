<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmTemplateController extends Controller
{
    /**
     * List message templates.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MessageTemplate::query()
            ->with('createdByUser:id,name')
            ->orderBy('name');

        if ($request->filled('channel')) {
            $query->byChannel($request->channel);
        }

        if (!$request->boolean('all', false) && !$request->boolean('include_inactive', false)) {
            $query->active();
        }

        $templates = $query->get();

        return response()->json(['data' => $templates]);
    }

    /**
     * Create a new template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'channel' => 'required|string|in:' . implode(',', MessageTemplate::CHANNELS),
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'body_html' => 'nullable|string',
            'required_variables' => 'nullable|array',
            'required_variables.*' => 'string',
        ]);

        $template = MessageTemplate::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Template created successfully.',
            'data' => $template->load('createdByUser:id,name'),
        ], 201);
    }

    /**
     * Show a single template.
     */
    public function show(string $id): JsonResponse
    {
        $template = MessageTemplate::with('createdByUser:id,name')->findOrFail($id);

        return response()->json(['data' => $template]);
    }

    /**
     * Update a template.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $template = MessageTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body' => 'sometimes|string',
            'body_html' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'required_variables' => 'nullable|array',
            'required_variables.*' => 'string',
        ]);

        $template->update($validated);

        return response()->json([
            'message' => 'Template updated successfully.',
            'data' => $template->fresh('createdByUser:id,name'),
        ]);
    }

    /**
     * Delete a template.
     */
    public function destroy(string $id): JsonResponse
    {
        $template = MessageTemplate::findOrFail($id);

        if ($template->is_system) {
            return response()->json([
                'message' => 'System templates cannot be deleted. You may edit them instead.',
            ], 403);
        }

        $template->delete();

        return response()->json(['message' => 'Template deleted successfully.']);
    }
}
