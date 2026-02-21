<?php

namespace App\Http\Controllers\Api;

use App\Models\KnowledgeDocument;
use App\Services\Chat\KnowledgeBaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class KnowledgeBaseController extends Controller
{
    protected KnowledgeBaseService $knowledgeService;

    public function __construct(KnowledgeBaseService $knowledgeService)
    {
        $this->knowledgeService = $knowledgeService;
    }

    /**
     * List all knowledge documents for the current workspace.
     */
    public function index(Request $request): JsonResponse
    {
        $documents = KnowledgeDocument::query()
            ->withCount('chunks')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json(['documents' => $documents]);
    }

    /**
     * Create a new knowledge document.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'doc_type' => 'required|in:' . implode(',', KnowledgeDocument::TYPES),
            'is_active' => 'sometimes|boolean',
        ]);

        $document = KnowledgeDocument::create($request->only([
            'title',
            'content',
            'doc_type',
            'is_active',
        ]));

        // Process in background: chunk and generate embeddings
        dispatch(function () use ($document) {
            $this->knowledgeService->processDocument($document);
        })->afterResponse();

        return response()->json([
            'document' => $document,
            'message' => 'Document created. Indexing will complete in the background.',
        ], 201);
    }

    /**
     * Update an existing knowledge document.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'doc_type' => 'sometimes|in:' . implode(',', KnowledgeDocument::TYPES),
            'is_active' => 'sometimes|boolean',
        ]);

        $document = KnowledgeDocument::findOrFail($id);
        $contentChanged = $request->has('content') && $request->content !== $document->content;

        $document->update($request->only([
            'title',
            'content',
            'doc_type',
            'is_active',
        ]));

        // Re-index if content changed
        if ($contentChanged) {
            dispatch(function () use ($document) {
                $this->knowledgeService->processDocument($document);
            })->afterResponse();
        }

        return response()->json([
            'document' => $document->fresh()->loadCount('chunks'),
            'message' => $contentChanged ? 'Document updated. Re-indexing in background.' : 'Document updated.',
        ]);
    }

    /**
     * Delete a knowledge document.
     */
    public function destroy(string $id): JsonResponse
    {
        $document = KnowledgeDocument::findOrFail($id);
        $document->delete(); // Cascades to chunks via FK

        return response()->json(['message' => 'Document deleted.']);
    }

    /**
     * Re-index a specific document.
     */
    public function reindex(string $id): JsonResponse
    {
        $document = KnowledgeDocument::findOrFail($id);

        dispatch(function () use ($document) {
            $this->knowledgeService->processDocument($document);
        })->afterResponse();

        return response()->json(['message' => 'Re-indexing started.']);
    }
}
