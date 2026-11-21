<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIndustryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:industries,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
