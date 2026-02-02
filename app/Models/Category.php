<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'fields',
        'prompt_template',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'fields' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the inventory processes for this category.
     */
    public function inventoryProcesses(): HasMany
    {
        return $this->hasMany(InventoryProcess::class);
    }

    /**
     * Get the inventory items for this category.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Scope to only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get required fields only.
     */
    public function getRequiredFieldsAttribute(): array
    {
        return collect($this->fields)
            ->filter(fn($field) => $field['required'] ?? false)
            ->values()
            ->toArray();
    }

    /**
     * Get generated fields (AI will fill these).
     */
    public function getGeneratedFieldsAttribute(): array
    {
        return collect($this->fields)
            ->filter(fn($field) => $field['generated'] ?? false)
            ->values()
            ->toArray();
    }

    /**
     * Validate user inputs against field schema.
     */
    public function validateInputs(array $inputs): array
    {
        $errors = [];
        $fields = collect($this->fields)->keyBy('key');

        foreach ($inputs as $key => $value) {
            if (!$fields->has($key)) {
                continue; // Skip unknown fields
            }

            $field = $fields->get($key);

            // Type validation
            if ($field['type'] === 'number' && !is_numeric($value)) {
                $errors[$key] = "{$field['label']} must be a number";
            }

            if ($field['type'] === 'boolean' && !is_bool($value)) {
                $errors[$key] = "{$field['label']} must be true or false";
            }

            // Min/max validation for numbers
            if (isset($field['min']) && is_numeric($value) && $value < $field['min']) {
                $errors[$key] = "{$field['label']} must be at least {$field['min']}";
            }

            if (isset($field['max']) && is_numeric($value) && $value > $field['max']) {
                $errors[$key] = "{$field['label']} must be at most {$field['max']}";
            }

            // Options validation for select fields
            if ($field['type'] === 'select' && isset($field['options'])) {
                if (!in_array($value, $field['options'])) {
                    $errors[$key] = "{$field['label']} must be one of: " . implode(', ', $field['options']);
                }
            }
        }

        return $errors;
    }
}
