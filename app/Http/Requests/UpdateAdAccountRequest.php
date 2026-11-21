<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'industry' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive,paused,archived,pending,removed',
        ];
    }
}
