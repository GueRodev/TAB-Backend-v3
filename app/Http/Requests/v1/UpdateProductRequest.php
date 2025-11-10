<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Preparar datos para validación
     */
    protected function prepareForValidation()
    {
        // Convertir is_featured a booleano antes de validar
        if ($this->has('is_featured')) {
            $value = $this->is_featured;
            // Convertir string "0", "1", "true", "false" a booleano real
            $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            $this->merge([
                'is_featured' => $boolean ?? false,
            ]);
        }
    }

    public function rules(): array
    {
        $productId = $this->route('id');

        return [
            'name' => "required|string|max:255|unique:products,name,{$productId}",
            'category_id' => 'required|exists:categories,id',
            'brand' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'stock' => 'required|integer|min:0',
            'sku' => "nullable|string|unique:products,sku,{$productId}|max:50",
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive,out_of_stock',
            'is_featured' => 'nullable|boolean',
        ];
    }
}