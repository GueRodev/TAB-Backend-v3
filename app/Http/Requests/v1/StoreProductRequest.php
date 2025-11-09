<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:products',
            'category_id' => 'required|exists:categories,id',
            'brand' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'stock' => 'required|integer|min:0',
            'sku' => 'nullable|string|unique:products|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive,out_of_stock',
            'is_featured' => 'boolean',
        ];
    }
}