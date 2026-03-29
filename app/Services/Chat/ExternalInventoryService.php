<?php

namespace App\Services\Chat;

use App\Models\WorkspaceChatConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalInventoryService
{
    /**
     * Search external API using tenant's configuration and extracted filters.
     */
    public function search(WorkspaceChatConfig $config, array $filters = [], int $limit = 5): array
    {
        $apiConfig = $config->getExternalApiConfig();
        if (!$apiConfig) {
            return [];
        }

        try {
            $response = $this->makeRequest($apiConfig, $filters);

            if (!$response->successful()) {
                Log::warning('External inventory API returned error', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                    'tenant_id' => $config->tenant_id,
                ]);
                return [];
            }

            $data = $response->json();
            $responseConfig = $apiConfig['response'] ?? [];
            $vdpTemplate = $config->widget_settings['vdp_url_template'] ?? null;

            $vehicles = $this->parseResponse($data, $responseConfig, $vdpTemplate, $limit);

            return $vehicles;
        } catch (\Exception $e) {
            Log::error('External inventory API call failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $config->tenant_id,
            ]);
            return [];
        }
    }

    /**
     * Test an external API config (not yet saved) and return raw + mapped results.
     */
    public function testConnection(array $apiConfig, ?string $vdpTemplate = null): array
    {
        try {
            $response = $this->makeRequest($apiConfig, []);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => "API returned HTTP {$response->status()}",
                    'raw_response' => substr($response->body(), 0, 2000),
                ];
            }

            $data = $response->json();
            $responseConfig = $apiConfig['response'] ?? [];
            $vehicles = $this->parseResponse($data, $responseConfig, $vdpTemplate, 3);

            return [
                'success' => true,
                'raw_sample' => array_slice(
                    $this->extractDataArray($data, $responseConfig['data_path'] ?? ''),
                    0,
                    2
                ),
                'mapped_vehicles' => $vehicles,
                'total_raw_count' => count(
                    $this->extractDataArray($data, $responseConfig['data_path'] ?? '')
                ),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ─── HTTP Request Builder ───────────────────────────────────────

    protected function makeRequest(array $apiConfig, array $filters): \Illuminate\Http\Client\Response
    {
        $url = $apiConfig['base_url'];
        $method = strtoupper($apiConfig['http_method'] ?? 'GET');
        $requestConfig = $apiConfig['request'] ?? [];
        $authConfig = $apiConfig['auth'] ?? ['type' => 'none'];

        // Build parameters from filters + extras
        $params = $this->buildParams($requestConfig, $filters);

        // Build HTTP client with auth
        $http = Http::timeout(15)->acceptJson();
        $http = $this->applyAuth($http, $authConfig);

        $paramsLocation = $requestConfig['params_location'] ?? 'query';

        if ($method === 'GET') {
            return $http->get($url, $params);
        }

        // POST: params go to body or query depending on config
        if ($paramsLocation === 'query') {
            return $http->post($url . '?' . http_build_query($params));
        }

        return $http->post($url, $params);
    }

    /**
     * Build the parameter array from filter mapping + static extra params.
     */
    protected function buildParams(array $requestConfig, array $filters): array
    {
        $params = [];

        // Map our standard filters to the external API's parameter names
        $filterMapping = $requestConfig['filter_params'] ?? [];
        foreach ($filters as $ourKey => $value) {
            $theirKey = $filterMapping[$ourKey] ?? null;
            if ($theirKey && $value !== null && $value !== '') {
                $params[$theirKey] = $value;
            }
        }

        // Add search query if provided and a search param is configured
        if (!empty($filters['_query']) && !empty($requestConfig['search_param'])) {
            $params[$requestConfig['search_param']] = $filters['_query'];
        }

        // Add static extra params (always sent)
        $extraParams = $requestConfig['extra_params'] ?? [];
        foreach ($extraParams as $key => $value) {
            $params[$key] = $value;
        }

        return $params;
    }

    /**
     * Apply authentication to the HTTP client.
     */
    protected function applyAuth($http, array $authConfig)
    {
        $type = $authConfig['type'] ?? 'none';

        return match ($type) {
            'api_key' => $http->withHeaders([
                ($authConfig['header_name'] ?? 'X-Api-Key') => $authConfig['value'] ?? '',
            ]),
            'bearer' => $http->withToken($authConfig['value'] ?? ''),
            'basic' => $http->withBasicAuth(
                $authConfig['username'] ?? '',
                $authConfig['password'] ?? ''
            ),
            default => $http,
        };
    }

    // ─── Response Parsing ───────────────────────────────────────────

    /**
     * Parse the API response, extract vehicle array, and map to our standard format.
     */
    protected function parseResponse(
        array $data,
        array $responseConfig,
        ?string $vdpTemplate,
        int $limit
    ): array {
        $dataPath = $responseConfig['data_path'] ?? '';
        $fieldMap = $responseConfig['field_map'] ?? [];

        $rawVehicles = $this->extractDataArray($data, $dataPath);

        // Limit results
        $rawVehicles = array_slice($rawVehicles, 0, $limit);

        return array_values(array_filter(
            array_map(
                fn($raw) => $this->mapVehicle($raw, $fieldMap, $vdpTemplate),
                $rawVehicles
            )
        ));
    }

    /**
     * Extract the vehicle array from the response using a dot-notation path.
     * e.g., "data.vehicles" → $response['data']['vehicles']
     */
    protected function extractDataArray(array $data, string $path): array
    {
        if (empty($path)) {
            // If no path, assume top-level is the array
            return is_array($data) && !Arr::isAssoc($data) ? $data : [$data];
        }

        $result = Arr::get($data, $path);

        if (!is_array($result)) {
            return [];
        }

        // If it's an associative array, it's a single item
        return Arr::isAssoc($result) ? [$result] : $result;
    }

    /**
     * Map a single raw vehicle object to our standard vehicle card format.
     */
    protected function mapVehicle(array $raw, array $fieldMap, ?string $vdpTemplate): ?array
    {
        $mapped = [];

        // Standard fields we need
        $standardFields = [
            'year', 'make', 'model', 'trim', 'price', 'mileage',
            'image_url', 'vin', 'stock_number', 'title', 'id',
        ];

        foreach ($standardFields as $ourField) {
            $theirField = $fieldMap[$ourField] ?? $ourField; // fallback to same name

            if ($theirField === null) {
                $mapped[$ourField] = null;
                continue;
            }

            // Support dot-notation for nested fields (e.g., "photos.0.url")
            $mapped[$ourField] = Arr::get($raw, $theirField);
        }

        // Skip if we can't identify the vehicle at all
        if (empty($mapped['make']) && empty($mapped['model']) && empty($mapped['title'])) {
            return null;
        }

        // Auto-generate title if not mapped
        if (empty($mapped['title'])) {
            $mapped['title'] = trim(sprintf(
                '%s %s %s %s',
                $mapped['year'] ?? '',
                $mapped['make'] ?? '',
                $mapped['model'] ?? '',
                $mapped['trim'] ?? ''
            ));
        }

        // Generate ID if not mapped
        if (empty($mapped['id'])) {
            $mapped['id'] = $mapped['vin'] ?? $mapped['stock_number'] ?? md5(json_encode($mapped));
        }

        // Format price
        $priceRaw = $mapped['price'];
        $priceFormatted = is_numeric($priceRaw) ? number_format((float) $priceRaw, 0) : ($priceRaw ?? '');

        // Build VDP URL from template
        $vdpUrl = null;
        if ($vdpTemplate) {
            $vdpUrl = $vdpTemplate;
            foreach (array_merge($mapped, $raw) as $key => $value) {
                if (is_scalar($value)) {
                    $vdpUrl = str_replace('{{' . $key . '}}', (string) $value, $vdpUrl);
                }
            }
        }

        return [
            'id' => $mapped['id'],
            'year' => $mapped['year'],
            'make' => $mapped['make'],
            'model' => $mapped['model'],
            'trim' => $mapped['trim'],
            'price' => $priceFormatted,
            'price_raw' => $priceRaw,
            'mileage' => is_numeric($mapped['mileage']) ? number_format((float) $mapped['mileage'], 0) : ($mapped['mileage'] ?? ''),
            'image_url' => $mapped['image_url'],
            'status' => 'available',
            'title' => $mapped['title'],
            'vdp_url' => $vdpUrl,
            'cta' => [
                ['label' => 'Book Test Drive', 'action' => 'test_drive'],
                ['label' => 'Request Financing', 'action' => 'financing'],
                ['label' => 'View Details', 'action' => 'view_details', 'url' => $vdpUrl],
            ],
        ];
    }
}
