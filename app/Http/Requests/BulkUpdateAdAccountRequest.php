<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateAdAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_ids' => 'required|array|min:1|max:500',
            'account_ids.*' => 'integer|exists:ad_accounts,id',
            'industry' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,paused,archived',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
        ];
    }

    public function messages(): array
    {
        return [
            'account_ids.max' => 'Cannot bulk update more than 500 accounts at once.',
        ];
    }
}
