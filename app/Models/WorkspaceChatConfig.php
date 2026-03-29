<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkspaceChatConfig extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'widget_api_key',
        'bot_name',
        'bot_personality',
        'greeting_message',
        'widget_settings',
        'business_hours',
        'auto_human_handoff',
        'fallback_timeout_minutes',
        'ai_aggressiveness',
        'is_active',
        'allowed_domains',
        'external_api_config',
    ];

    protected $casts = [
        'widget_settings' => 'array',
        'business_hours' => 'array',
        'allowed_domains' => 'array',
        'auto_human_handoff' => 'boolean',
        'is_active' => 'boolean',
        'external_api_config' => 'array',
    ];

    protected $attributes = [
        'bot_name' => 'Sales Assistant',
        'bot_personality' => 'professional',
        'greeting_message' => 'Hi 👋 What\'s your name?',
        'ai_aggressiveness' => 'balanced',
        'auto_human_handoff' => true,
        'fallback_timeout_minutes' => 5,
        'is_active' => false,
    ];

    public const PERSONALITIES = ['professional', 'friendly', 'luxury', 'casual'];
    public const AGGRESSIVENESS_LEVELS = ['informational', 'balanced', 'sales_driven'];

    protected static function booted(): void
    {
        static::creating(function (self $config) {
            if (empty($config->widget_api_key)) {
                $config->widget_api_key = self::generateApiKey();
            }
        });
    }

    public static function generateApiKey(): string
    {
        return 'wk_' . Str::random(61);
    }

    public function regenerateApiKey(): self
    {
        $this->update(['widget_api_key' => self::generateApiKey()]);
        return $this;
    }

    /**
     * Check if the current time is within configured business hours.
     */
    public function isWithinBusinessHours(): bool
    {
        $hours = $this->business_hours;
        if (!$hours || !($hours['enabled'] ?? false)) {
            return true; // No business hours configured = always open
        }

        $timezone = $hours['timezone'] ?? 'UTC';
        $now = now()->timezone($timezone);
        $dayKey = strtolower($now->format('D')); // mon, tue, etc.
        $schedule = $hours['schedule'][$dayKey] ?? null;

        if (!$schedule) {
            return false;
        }

        $open = $now->copy()->setTimeFromTimeString($schedule['open']);
        $close = $now->copy()->setTimeFromTimeString($schedule['close']);

        return $now->between($open, $close);
    }

    /**
     * Get the default widget settings.
     */
    public static function defaultWidgetSettings(): array
    {
        return [
            'primary_color' => '#2563eb',
            'accent_color' => '#1e40af',
            'position' => 'right',
            'auto_open_delay' => 0,
            'logo_url' => null,
            'dark_mode' => false,
            'welcome_message' => 'Welcome! How can we help you today?',
            'vdp_url_template' => null,
        ];
    }

    /**
     * Check if this tenant has an enabled external API for inventory.
     */
    public function hasExternalApi(): bool
    {
        $config = $this->external_api_config;

        return !empty($config)
            && ($config['enabled'] ?? false)
            && !empty($config['base_url']);
    }

    /**
     * Get the external API config with defaults applied.
     */
    public function getExternalApiConfig(): ?array
    {
        if (!$this->hasExternalApi()) {
            return null;
        }

        $config = $this->external_api_config;

        return array_merge([
            'enabled' => false,
            'base_url' => '',
            'http_method' => 'GET',
            'auth' => ['type' => 'none'],
            'request' => [
                'params_location' => 'query',
                'search_param' => 'q',
                'filter_params' => [],
                'extra_params' => [],
            ],
            'response' => [
                'data_path' => '',
                'field_map' => [],
                'sample_response' => '',
            ],
        ], $config);
    }
}
