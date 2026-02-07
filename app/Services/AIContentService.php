<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AIContentService
{
    protected OpenRouterClient $client;

    public function __construct(OpenRouterClient $client)
    {
        $this->client = $client;
    }

    /**
     * Generate inventory content for a category.
     */
    public function generateInventoryContent(
        Category $category,
        array $userInputs,
        ?string $customPrompt = null
    ): array {
        $prompt = $this->buildInventoryPrompt($category, $userInputs, $customPrompt);

        Log::info('Generating inventory content', [
            'category' => $category->slug,
            'user_inputs' => array_keys($userInputs),
        ]);

        $result = $this->client->jsonPrompt($prompt, [
            'system' => $this->getSystemPrompt(),
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ]);

        // Validate the result against the category schema
        $validated = $this->validateAndMerge($category, $userInputs, $result);

        return $validated;
    }

    /**
     * Generate field mapping suggestions for CSV headers.
     */
    public function generateMapping(array $csvHeaders, array $categoryFields): array
    {
        $prompt = $this->buildMappingPrompt($csvHeaders, $categoryFields);

        Log::info('Generating mapping suggestions', [
            'headers_count' => count($csvHeaders),
            'fields_count' => count($categoryFields),
        ]);

        $result = $this->client->jsonPrompt($prompt, [
            'system' => $this->getMappingSystemPrompt(),
            'temperature' => 0.1,
            'max_tokens' => 3000,
        ]);

        // POST-PROCESSING: Only keep keys that actually exist in the CSV headers
        // AI sometimes hallucinates headers to match all database fields
        $filteredResult = [];
        foreach ($result as $header => $field) {
            // Check if this header exists in the CSV (case-insensitive)
            $actualHeader = null;
            foreach ($csvHeaders as $h) {
                if (strtolower($h) === strtolower($header)) {
                    $actualHeader = $h;
                    break;
                }
            }

            if ($actualHeader && $field !== null) {
                $filteredResult[$actualHeader] = $field;
            }
        }

        return $filteredResult;
    }

    /**
     * Build the inventory generation prompt.
     */
    protected function buildInventoryPrompt(
        Category $category,
        array $userInputs,
        ?string $customPrompt
    ): string {
        $fields = $category->fields;
        $fieldDescriptions = $this->formatFieldDescriptions($fields);
        $userInputsFormatted = $this->formatUserInputs($userInputs);

        $prompt = <<<PROMPT
You are generating a professional {$category->name} inventory listing.

## FIELD SCHEMA

The following fields are defined for this category:

{$fieldDescriptions}

## USER-PROVIDED VALUES

The user has already provided the following values (DO NOT change these):

{$userInputsFormatted}

## YOUR TASK

Generate content for ALL fields that were NOT provided by the user.
For fields marked as "generated: true", you MUST provide a value.
For optional fields, provide a reasonable value based on the context.

PROMPT;

        if ($customPrompt) {
            $prompt .= <<<CUSTOM

## ADDITIONAL USER INSTRUCTIONS

{$customPrompt}

CUSTOM;
        }

        $prompt .= <<<OUTPUT

## OUTPUT FORMAT

Return a valid JSON object containing ALL fields from the schema.
Include the user-provided values exactly as given, plus your generated values.
Do not include any explanation, just the JSON object.

Example structure:
{
  "make": "...",
  "model": "...",
  "year": ...,
  "condition": "...",
  "description": "...",
  ...
}
OUTPUT;

        return $prompt;
    }

    /**
     * Format field descriptions for the prompt.
     */
    protected function formatFieldDescriptions(array $fields): string
    {
        $lines = [];

        foreach ($fields as $field) {
            $line = "- **{$field['key']}** ({$field['type']})";

            if ($field['required'] ?? false) {
                $line .= ' [REQUIRED]';
            }

            if ($field['generated'] ?? false) {
                $line .= ' [AI-GENERATED]';
            }

            $description = $field['description'] ?? '';
            $line .= ": {$description}";

            if (isset($field['options'])) {
                $line .= " Options: " . implode(', ', $field['options']);
            }

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * Format user inputs for the prompt.
     */
    protected function formatUserInputs(array $inputs): string
    {
        if (empty($inputs)) {
            return "(No values provided yet)";
        }

        $lines = [];
        foreach ($inputs as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $lines[] = "- {$key}: {$value}";
        }

        return implode("\n", $lines);
    }

    /**
     * Get the system prompt for inventory generation.
     */
    protected function getSystemPrompt(): string
    {
        return <<<SYSTEM
You are an expert inventory content writer for an online marketplace.
Your job is to create compelling, professional, and accurate product listings.

Guidelines:
1. Be professional and engaging in descriptions
2. Highlight key features and benefits
3. Use SEO-friendly language
4. Be realistic with pricing based on market values
5. Do not fabricate specific identifiers (VIN, serial numbers, etc.)
6. If unsure about a value, use null instead of guessing
7. Always maintain consistency with user-provided values
8. Return ONLY valid JSON, no additional text
SYSTEM;
    }

    /**
     * Validate AI output and merge with user inputs.
     */
    protected function validateAndMerge(
        Category $category,
        array $userInputs,
        array $aiOutput
    ): array {
        $fields = collect($category->fields)->keyBy('key');
        $result = [];

        foreach ($fields as $key => $field) {
            // User input takes priority
            if (isset($userInputs[$key])) {
                $result[$key] = $userInputs[$key];
                continue;
            }

            // AI output
            if (isset($aiOutput[$key])) {
                $result[$key] = $this->castValue($aiOutput[$key], $field['type']);
                continue;
            }

            // Default value if defined
            if (isset($field['default'])) {
                $result[$key] = $field['default'];
                continue;
            }

            // Null for missing optional fields
            $result[$key] = null;
        }

        return $result;
    }

    /**
     * Cast a value to the expected type.
     */
    protected function castValue($value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'number' => is_numeric($value) ? (float) $value : null,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array' => is_array($value) ? $value : [$value],
            default => (string) $value,
        };
    }

    /**
     * Generate image prompts for the inventory item.
     */
    public function generateImagePrompts(array $inventoryData, int $count = 3, ?string $showroomDescription = null, bool $hasInputImage = false): array
    {
        $basePrompt = $this->buildImageBasePrompt($inventoryData, $showroomDescription, $hasInputImage);
        $angles = ['front 3/4 view', 'rear 3/4 view', 'side profile', 'interior dashboard', 'interior seats'];

        $prompts = [];
        for ($i = 0; $i < $count && $i < count($angles); $i++) {
            $prompts[] = str_replace('{angle}', $angles[$i], $basePrompt);
        }

        return $prompts;
    }

    /**
     * Build base prompt for image generation.
     */
    /**
     * Build base prompt for image generation.
     */
    protected function buildImageBasePrompt(array $inventoryData, ?string $showroomDescription = null, bool $hasInputImage = false): string
    {
        $year  = $inventoryData['year'] ?? '2024';
        $make  = $inventoryData['make'] ?? 'Luxury';
        $model = $inventoryData['model'] ?? 'Car';
        $color = $inventoryData['color'] ?? 'Metallic';

        if ($showroomDescription) {
            $environment = "Custom Showroom Environment: {$showroomDescription}";
        } elseif ($hasInputImage) {
            $environment = "Custom Showroom Environment: The vehicle positioned naturally within the provided background image. Match lighting and perspective of the background.";
        } else {
            $environment = "Professional minimalist infinity cove studio, seamless matte grey floor merging into a soft light-grey background. Zero horizon line.";
        }

        return <<<PROMPT
Commercial automotive photography of a {$year} {$make} {$model} in {$color} paint finish.

Environment: 
{$environment}

Lighting & Composition:
Large overhead softbox lighting (top-down) creating soft, elegant highlights on the car's body contours. 
Camera: Shot on 50mm lens at eye-level, three-quarter front view. 
Framing: The car is centered, full-body visible, with equal negative space on all sides.

Technical Quality:
Photorealistic, 8k resolution, ray-traced reflections, sharp textures on tires and grill, showroom pristine condition.
Strictly no text, no license plates, no watermarks, no people, no motion blur.
PROMPT;
    }

    /**
     * Build the mapping generation prompt.
     */
    protected function buildMappingPrompt(array $csvHeaders, array $categoryFields): string
    {
        $headersList = implode("\n", array_map(fn($h) => "- $h", $csvHeaders));

        $fieldsList = [];
        foreach ($categoryFields as $field) {
            $desc = $field['description'] ?? '';
            $fieldsList[] = "- **{$field['key']}**: {$desc}"; // Only key and description matter mostly
        }
        $fieldsStr = implode("\n", $fieldsList);

        return <<<PROMPT
You are mapping CSV headers to database fields for an inventory import.

## CSV HEADERS (Source)
{$headersList}

## DATABASE FIELDS (Target)
{$fieldsStr}

## YOUR TASK
Match each CSV header to the most appropriate database field based on semantic meaning.

CRITICAL RULES:
1. You MUST ONLY use the keys listed in "DATABASE FIELDS". Do NOT invent new keys.
2. You MUST ONLY map the headers listed in "CSV HEADERS". Do NOT invent new headers to match database fields.
3. If a database field has no matching CSV header, do NOT include it in the output.
4. "MSRP" or "Invoice" usually refers to a price field (like "price"). 
5. "VIN" is "vin".

Return a breakdown of mapped pairs only for headers that exist in the source.

## OUTPUT FORMAT
JSON Object where keys are CSV headers and values are the database field keys (or null).
PROMPT;
    }

    /**
     * Get the system prompt for mapping generation.
     */
    protected function getMappingSystemPrompt(): string
    {
        return <<<SYSTEM
You are an intelligent data mapping assistant.
Your goal is to accurately map CSV column headers to database schema fields.
Strictly adhere to the provided schema keys.
Return ONLY valid JSON. Do not include markdown formatting (example: ```json ... ```), just the raw JSON object.
SYSTEM;
    }

    /**
     * Analyze inventory item quality, market pricing, and image matching.
     */
    public function analyzeInventory(\App\Models\InventoryItem $item): array
    {
        $data = $item->generated_data ?? [];
        $primaryImage = $item->primary_image;

        $base64Image = null;
        $imageUrl = null;

        if ($primaryImage) {
            // Default to original path
            $storagePath = $primaryImage->path;

            // Try to use optimized sizes (Medium > Large > Original)
            // Note: sizes paths are full URLs like "/storage/inventory/..." so we need to convert to relative storage path
            if (!empty($primaryImage->sizes['original'])) {
                $storagePath = preg_replace('/^\/storage\//', '', $primaryImage->sizes['original']);
            } elseif (!empty($primaryImage->sizes['large'])) {
                $storagePath = preg_replace('/^\/storage\//', '', $primaryImage->sizes['large']);
            } elseif (!empty($primaryImage->sizes['medium'])) {
                $storagePath = preg_replace('/^\/storage\//', '', $primaryImage->sizes['medium']);
            }

            if (Storage::disk('public')->exists($storagePath)) {
                $path = Storage::disk('public')->path($storagePath);
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $dataContent = file_get_contents($path);
                $base64Image = 'data:image/' . $type . ';base64,' . base64_encode($dataContent);
            } else {
                // Fallback to public URL
                $imageUrl = config('app.url') . Storage::url($primaryImage->path);
            }
        }

        $prompt = $this->buildAnalysisPrompt($data, $primaryImage);

        $messages = [];

        // System Message
        $messages[] = [
            'role' => 'system',
            'content' => $this->getAnalysisSystemPrompt(),
        ];

        // User Message (Text + Image)
        $userContent = [];
        $userContent[] = [
            'type' => 'text',
            'text' => $prompt,
        ];

        if ($base64Image) {
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $base64Image
                ]
            ];
        } elseif ($imageUrl) {
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imageUrl
                ]
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userContent,
        ];

        Log::info('Starting inventory analysis', [
            'item_id' => $item->id,
            'has_image' => $base64Image ? 'base64' : ($imageUrl ? 'url' : 'no'),
            'model' => config('openrouter.vision_model'),
        ]);

        $result = $this->client->chatCompletion($messages, [
            'model' => config('openrouter.vision_model'),
            'temperature' => 0.2,
            'max_tokens' => 1000,
            'json_mode' => true,
        ]);

        $content = $result['content'];
        $json = $this->extractJson($content);

        if ($json === null) {
            Log::error('JSON extraction failed', ['content' => $content]);
            return [
                'score' => 0,
                'summary' => 'Analysis failed to generate valid JSON output.',
                'breakdown' => []
            ];
        }

        return $json;
    }

    /**
     * Build the analysis prompt.
     */
    protected function buildAnalysisPrompt(array $data, ?\App\Models\InventoryImage $image = null): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $currentDate = now()->format('Y-m-d');

        $imageContext = "";
        if ($image) {
            $imageContext = <<<CONTEXT
            
## IMAGE METADATA
The attached image has the following metadata:
- Original Generation Prompt: "{$image->prompt}"
- Alt Text: "{$image->alt}"
- Generator: "{$image->generated_by}"
CONTEXT;
        }

        return <<<PROMPT
Please analyze this vehicle inventory listing for quality, market pricing, and visual consistency.

## VEHICLE DATA
{$json}
{$imageContext}

## CONTEXT
Current Date: {$currentDate}
The image provided is the primary photo for this listing.

## YOUR TASK
1. **Data Quality Check**: Are all critical fields present? Is the description professional and detailed?
2. **Market Analysis**: Based on the Year, Make, Model, Mileage, and Condition, is the Price realistc? 
   - Note: We are in year 2026. A 2015 car is 11 years old.
   - Depreciation: deeply consider standard depreciation curves.
   - If price is missing, flag it.
   - If price is significantly high or low, flag it.
3. **Visual Verification**: Does the car in the image match the description?
   - Compare the image visual details with the Vehicle Data.
   - Compare the image with the "IMAGE METADATA" (Original Prompt) to see if it respects the requested features.
   - Check Color.
   - Check Make/Model (if recognizable).
   - Check for visible damage vs condition rating.

## OUTPUT FORMAT
Return valid JSON:
{
    "score": <integer 0-100>, // Overall confidence score
    "summary": "<string>", // 2-3 sentence summary of findings
    "breakdown": {
        "data_quality": {
            "score": <0-100>,
            "issues": ["<string>", ...],
            "feedback": "<string>"
        },
        "market_pricing": {
            "score": <0-100>,
            "estimated_market_value": "<string range or N/A>", 
            "is_fair_price": <boolean>,
            "feedback": "<string>"
        },
        "visual_match": {
            "score": <0-100>,
            "match_confirmed": <boolean>,
            "feedback": "<string>"
        }
    }
}
PROMPT;
    }

    /**
     * System prompt for analysis.
     */
    protected function getAnalysisSystemPrompt(): string
    {
        return <<<SYSTEM
You are an expert automotive inventory auditor and market analyst. 
Your job is to protect the dealership from bad listings. 
You verify data integrity, check pricing against current market trends (in 2026), and visually confirm the vehicle matches its description.
Be strict but fair. If a price seems like a placeholder (e.g. $1 or $999999), penalize the score heavily.
SYSTEM;
    }

    protected function extractJson(string $content): ?array
    {
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        $firstBrace = strpos($content, '{');
        $lastBrace = strrpos($content, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $possibleJson = substr($content, $firstBrace, $lastBrace - $firstBrace + 1);
            $decoded = json_decode($possibleJson, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return null;
    }
}
