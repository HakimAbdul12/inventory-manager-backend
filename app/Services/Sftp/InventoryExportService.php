<?php

namespace App\Services\Sftp;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class InventoryExportService
{
    /**
     * Export inventory items to CSV and return temp file path.
     */
    public function exportToCsv(Collection $items, array $fields, string $delimiter = ','): string
    {
        $path = $this->tempPath('csv');
        $handle = fopen($path, 'w');

        if (!$handle) {
            throw new \RuntimeException('Cannot create temp file for CSV export.');
        }

        // Write UTF-8 BOM for Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF");

        // Header row
        $headers = array_map(fn($f) => $f['label'] ?? $f['key'], $fields);
        fputcsv($handle, $headers, $delimiter);

        // Data rows
        foreach ($items as $item) {
            $row = $this->extractRow($item, $fields);
            fputcsv($handle, $row, $delimiter);
        }

        fclose($handle);

        return $path;
    }

    /**
     * Export inventory items to XML and return temp file path.
     */
    public function exportToXml(Collection $items, array $fields): string
    {
        $path = $this->tempPath('xml');
        $handle = fopen($path, 'w');

        if (!$handle) {
            throw new \RuntimeException('Cannot create temp file for XML export.');
        }

        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
        fwrite($handle, '<inventory>' . PHP_EOL);

        foreach ($items as $item) {
            fwrite($handle, '  <item>' . PHP_EOL);
            $row = $this->extractRow($item, $fields);

            foreach ($fields as $i => $field) {
                $key = $this->sanitizeXmlTag($field['key']);
                $value = htmlspecialchars((string) ($row[$i] ?? ''), ENT_XML1, 'UTF-8');
                fwrite($handle, "    <{$key}>{$value}</{$key}>" . PHP_EOL);
            }

            fwrite($handle, '  </item>' . PHP_EOL);
        }

        fwrite($handle, '</inventory>' . PHP_EOL);
        fclose($handle);

        return $path;
    }

    /**
     * Export inventory items to JSON and return temp file path.
     */
    public function exportToJson(Collection $items, array $fields): string
    {
        $path = $this->tempPath('json');
        $handle = fopen($path, 'w');

        if (!$handle) {
            throw new \RuntimeException('Cannot create temp file for JSON export.');
        }

        $jsonItems = [];
        foreach ($items as $item) {
            $row = $this->extractRow($item, $fields);
            $obj = [];
            foreach ($fields as $i => $field) {
                $obj[$field['key']] = $this->castValue($row[$i] ?? null, $field['type'] ?? 'text');
            }
            $jsonItems[] = $obj;
        }

        fwrite($handle, json_encode($jsonItems, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fclose($handle);

        return $path;
    }

    /**
     * Generate the export file based on format.
     *
     * @return string Path to the generated file.
     */
    public function export(Collection $items, array $fields, string $format): string
    {
        return match ($format) {
            'csv' => $this->exportToCsv($items, $fields),
            'xml' => $this->exportToXml($items, $fields),
            'json' => $this->exportToJson($items, $fields),
            default => throw new \InvalidArgumentException("Unsupported export format: {$format}"),
        };
    }

    /**
     * Resolve the fields array from category IDs.
     * If no categories specified, returns common fields.
     */
    public function resolveFields(?array $categoryIds): array
    {
        if (empty($categoryIds)) {
            // Common default fields
            return [
                ['key' => 'id', 'label' => 'ID', 'type' => 'text'],
                ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'text'],
                ['key' => 'created_at', 'label' => 'Created At', 'type' => 'text'],
            ];
        }

        // Merge fields from all selected categories
        $allFields = [];
        $categories = Category::whereIn('id', $categoryIds)->get();

        foreach ($categories as $category) {
            $catFields = $category->fields ?? [];
            foreach ($catFields as $field) {
                // Deduplicate by key
                $allFields[$field['key']] = $field;
            }
        }

        return array_values($allFields);
    }

    // ─── Private Helpers ────────────────────────────────────

    /**
     * Extract a data row from an inventory item based on field definitions.
     */
    private function extractRow($item, array $fields): array
    {
        $data = $item->generated_data ?? [];
        $row = [];

        foreach ($fields as $field) {
            $key = $field['key'];
            $value = '';

            // Check generated_data first, then model attributes
            if (array_key_exists($key, $data)) {
                $value = $data[$key];
            } elseif (isset($item->{$key})) {
                $value = $item->{$key};
            }

            // Flatten arrays/objects to pipe-separated strings for CSV/XML
            if (is_array($value)) {
                $value = implode('|', array_map(function ($v) {
                    return is_array($v) ? json_encode($v) : (string) $v;
                }, $value));
            } elseif (is_object($value)) {
                $value = json_encode($value);
            }

            $row[] = $value;
        }

        return $row;
    }

    /**
     * Cast a value to its appropriate type for JSON export.
     */
    private function castValue($value, string $type)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'number' => is_numeric($value) ? (float) $value : $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array' => is_array($value) ? $value : [$value],
            default => is_array($value) ? implode('|', $value) : (string) $value,
        };
    }

    /**
     * Sanitize a string for use as an XML tag name.
     */
    private function sanitizeXmlTag(string $tag): string
    {
        $tag = preg_replace('/[^a-zA-Z0-9_]/', '_', $tag);

        // Tags can't start with a number
        if (preg_match('/^[0-9]/', $tag)) {
            $tag = '_' . $tag;
        }

        return $tag;
    }

    /**
     * Generate a temp file path with given extension.
     */
    private function tempPath(string $extension): string
    {
        $dir = storage_path('app/temp/exports');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir . '/inventory_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
    }
}
