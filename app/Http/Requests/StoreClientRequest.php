<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:255',
            'vertical' => 'nullable|string|max:255',
            'subscription_tier' => 'nullable|string|in:basic,professional,enterprise',
            'monthly_budget' => 'nullable|numeric|min:0',
            'billing_email' => 'nullable|email|max:255',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
            'status' => 'nullable|string|in:active,inactive,suspended',
            'notes' => 'nullable|string|max:5000',
        ];
    }
}
