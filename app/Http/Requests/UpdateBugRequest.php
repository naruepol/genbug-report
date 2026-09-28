<?php

namespace App\Http\Requests;

use App\Enums\BugPriority;
use App\Enums\BugSeverity;
use App\Enums\BugStatus;
use App\Enums\VerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Admin review of a bug. Every field is optional so quick actions (Verify, Reject,
 * Mark Duplicate) can send just the verification status.
 */
class UpdateBugRequest extends FormRequest
{
    /**
     * Admin access is enforced by the route middleware.
     */
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
            'verification_status' => ['sometimes', 'required', Rule::enum(VerificationStatus::class)],
            'status' => ['sometimes', 'required', Rule::enum(BugStatus::class)],
            'severity' => ['sometimes', 'nullable', Rule::enum(BugSeverity::class)],
            'priority' => ['sometimes', 'nullable', Rule::enum(BugPriority::class)],
            'score' => ['sometimes', 'nullable', 'integer', 'between:1,10'],
            'admin_note' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ];
    }
}
