<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:categories',
            'parent_id' => 'nullable|exists:categories,id',
            'level' => 'required|integer|min:0|max:3',
            'description' => 'nullable|string|max:1000',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->parent_id) {
                $parent = \App\Models\Category::find($this->parent_id);
                if ($parent && $parent->level >= $this->level) {
                    $validator->errors()->add('parent_id', 
                        'Parent category must have a lower level');
                }
            }
        });
    }
}
