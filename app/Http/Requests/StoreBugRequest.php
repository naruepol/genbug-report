<?php

namespace App\Http\Requests;

use App\Enums\BugCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Public bug report. Severity, priority, score and status are deliberately absent:
 * only the admin sets them after review.
 */
class StoreBugRequest extends FormRequest
{
    /**
     * Name of the hidden honeypot input; humans leave it empty.
     */
    public const HONEYPOT = 'website';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['nullable', Rule::enum(BugCategory::class)],
            'steps_to_reproduce' => ['nullable', 'string', 'max:10000'],
            'expected_result' => ['nullable', 'string', 'max:5000'],
            'actual_result' => ['nullable', 'string', 'max:5000'],
            'page_screen' => ['nullable', 'string', 'max:255'],
            'browser' => ['nullable', 'string', 'max:100'],
            'operating_system' => ['nullable', 'string', 'max:100'],
            'device' => ['nullable', 'string', 'max:100'],
            'screenshot' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            self::HONEYPOT => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'screenshot.image' => 'The screenshot must be a PNG, JPG, JPEG or WEBP image.',
            'screenshot.mimes' => 'The screenshot must be a PNG, JPG, JPEG or WEBP image.',
            'screenshot.max' => 'The screenshot may not be larger than 5 MB.',
        ];
    }

    /**
     * Whether the honeypot was filled in, which only bots do.
     */
    public function isSpam(): bool
    {
        return filled($this->input(self::HONEYPOT));
    }
}
