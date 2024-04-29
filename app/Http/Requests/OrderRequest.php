<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
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
            'user_id' => 'required|numeric',
            'product_id' => 'required|numeric',
            'promo_id' => 'nullable|numeric',
            'temperatur' => 'required',
            'size' => 'required',
            'ice' => 'required',
            'sugar' => 'required',
            'note' => 'required',
            'quantity' => 'required|numeric',
            'total' => 'required|numeric',
        ];
    }
}
