<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Chat\KnowledgeBaseService;

class SearchKnowledgeBaseTool implements McpTool
{
    protected KnowledgeBaseService $knowledgeBase;

    public function __construct(KnowledgeBaseService $knowledgeBase)
    {
        $this->knowledgeBase = $knowledgeBase;
    }

    public function name(): string
    {
        return 'search_knowledge_base';
    }

    public function description(): string
    {
        return 'Search the workspace knowledge base for information about the dealership, policies, FAQs, or any custom content that has been indexed.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Natural language search query.',
                ],
                'max_results' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results. Default 5, max 20.',
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'inventory.view';
    }

    public function category(): string
    {
        return 'AI';
    }

    public function execute(array $args, User $user, Tenant $tenant): array
    {
        try {
            $maxResults = min($args['max_results'] ?? 5, 20);
            $results = $this->knowledgeBase->retrieveRelevant(
                $args['query'],
                $tenant->id,
                $maxResults
            );

            if (empty($results)) {
                return [
                    ['type' => 'text', 'text' => 'No relevant information found in the knowledge base for your query.'],
                ];
            }

            $text = "Found " . count($results) . " relevant knowledge base entries:\n\n";
            foreach ($results as $i => $chunk) {
                $text .= ($i + 1) . ". " . $chunk . "\n\n";
            }

            return [
                ['type' => 'text', 'text' => $text],
            ];
        } catch (\Exception $e) {
            return [
                ['type' => 'text', 'text' => 'Knowledge base search failed: ' . $e->getMessage()],
            ];
        }
    }
}
