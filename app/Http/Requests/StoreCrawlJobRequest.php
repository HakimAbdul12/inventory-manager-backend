<?php

namespace App\Http\Requests;

use App\Enums\CrawlExclusionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCrawlJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seed_url' => ['required', 'url', 'max:2048'],
            'max_depth' => ['nullable', 'integer', 'min:1', 'max:10'],
            'max_pages' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'exclusions' => ['nullable', 'array', 'max:50'],
            'exclusions.*.pattern' => ['required_with:exclusions', 'string', 'max:1024'],
            'exclusions.*.type' => [
                'required_with:exclusions',
                'string',
                Rule::in(array_column(CrawlExclusionType::cases(), 'value')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'seed_url.required' => 'A seed URL is required to start crawling.',
            'seed_url.url' => 'The seed URL must be a valid URL.',
            'max_depth.max' => 'Maximum depth cannot exceed 10 levels.',
            'max_pages.max' => 'Maximum pages cannot exceed 5000.',
        ];
    }
}
