<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:threshold,anomaly,trend',
            'objective' => 'nullable|string|max:100',
            'conditions' => 'required|array',
            'conditions.metric' => 'required|string',
            'conditions.operator' => 'required|string|in:>,<,>=,<=,==',
            'conditions.value' => 'required|numeric',
            'notification_channels' => 'nullable|array',
            'notification_channels.*' => 'string|in:email,slack,in_app',
            'is_active' => 'boolean',
        ];
    }
}
