<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateProjectRequest extends StoreProjectRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    protected function uniqueProjectCode(): Unique
    {
        return Rule::unique('projects', 'project_code')->ignore($this->route('project'));
    }
}
