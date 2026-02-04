<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Import;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\AIContentService;
use App\Models\Category;

class ImportController extends Controller
{
    public function index(): JsonResponse
    {
        $imports = Import::latest()->get();
        return response()->json(['success' => true, 'data' => $imports]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'category_slug' => 'required|string|exists:categories,slug',
        ]);

        $file = $request->file('file');
        $path = $file->store('imports');

        // Count total rows (approximate or precise)
        $totalRows = count(file($file->getPathname())); // -1 if header exists? We'll clarify later.

        $import = Import::create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'total_rows' => $totalRows > 0 ? $totalRows - 1 : 0, // Assume header
            'status' => 'mapping',
            'category_slug' => $request->category_slug,
        ]);

        // Parse headers for initial mapping suggestion
        $handle = fopen($file->getPathname(), 'r');
        $headers = fgetcsv($handle);
        fclose($handle);

        return response()->json([
            'success' => true,
            'data' => $import,
            'headers' => $headers
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $import = Import::findOrFail($id);

        // If status is mapping, we might want to return CSV headers too if not stored
        $headers = [];
        if ($import->status === 'mapping' && Storage::exists($import->file_path)) {
            $path = Storage::path($import->file_path);
            if (($handle = fopen($path, 'r')) !== false) {
                $headers = fgetcsv($handle);
                fclose($handle);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $import,
            'headers' => $headers
        ]);
    }

    public function updateMapping(Request $request, string $id): JsonResponse
    {
        $import = Import::findOrFail($id);

        $request->validate([
            'mappings' => 'required|array',
        ]);

        $import->update([
            'mappings' => $request->mappings,
        ]);

        return response()->json(['success' => true, 'data' => $import]);
    }

    public function process(string $id): JsonResponse
    {
        $import = Import::findOrFail($id);

        if ($import->status !== 'mapping') {
            return response()->json(['success' => false, 'message' => 'Import is not in mapping state'], 400);
        }

        $import->update(['status' => 'pending']);

        // Dispatch Job Here
        // ImportInventoryJob::dispatch($import);
        \App\Jobs\ImportInventoryJob::dispatch($import);

        return response()->json(['success' => true, 'message' => 'Import processing started', 'data' => $import]);
    }

    public function predictMapping(string $id, \App\Services\AIContentService $aiService): JsonResponse
    {
        $import = Import::findOrFail($id);

        // 1. Get headers from CSV
        $headers = [];
        if (Storage::exists($import->file_path)) {
            $path = Storage::path($import->file_path);
            if (($handle = fopen($path, 'r')) !== false) {
                $headers = fgetcsv($handle);
                fclose($handle);
            }
        }

        if (empty($headers)) {
            return response()->json(['success' => false, 'message' => 'Could not read CSV headers'], 400);
        }

        // 2. Get Category Fields
        $category = \App\Models\Category::where('slug', $import->category_slug)->firstOrFail();

        // 3. AI Prediction
        try {
            $prediction = $aiService->generateMapping($headers, $category->fields ?? []);
            return response()->json([
                'success' => true,
                'data' => $prediction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI mapping failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
