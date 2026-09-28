<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreProjectRequest extends FormRequest
{
    /**
     * Admin access is enforced by the route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'project_code' => Str::upper(trim((string) $this->input('project_code'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'project_code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/',
                $this->uniqueProjectCode(),
            ],
            'description' => ['required', 'string', 'max:10000'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'demo_url' => ['nullable', 'url:http,https', 'max:2048'],
            'repository_url' => ['nullable', 'url:http,https', 'max:2048'],
            'technology_stack' => ['nullable', 'string', 'max:255'],
            'presentation_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'bug_reporting_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_code.regex' => 'Use letters, numbers and single hyphens only, e.g. PORTFOLIO-AI.',
            'project_code.unique' => 'This project code is already used by another project.',
        ];
    }

    protected function uniqueProjectCode(): Unique
    {
        return Rule::unique('projects', 'project_code');
    }
}
