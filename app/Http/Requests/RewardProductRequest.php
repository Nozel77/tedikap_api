<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RewardProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'regular_price' => 'required|integer',
            'large_price' => 'required|integer',
            'category' => 'required|string',
        ];
    }
}
