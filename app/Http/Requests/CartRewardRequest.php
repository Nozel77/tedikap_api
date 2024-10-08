<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartRewardRequest extends FormRequest
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
            'reward_product_id' => 'required|integer',
            'temperatur' => 'required|string',
            'size' => 'required|string',
            'ice' => 'required|string',
            'sugar' => 'required|string',
            'note' => 'nullable|string',
            'quantity' => 'required|integer',
            'points' => 'numeric',
        ];
    }
}
