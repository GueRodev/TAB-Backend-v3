<?php

namespace App\Http\Requests\v1;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
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
        $categoryId = $this->route('id');

        return [
            'name' => "required|string|max:255|unique:categories,name,{$categoryId}",
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
            $categoryId = $this->route('id');
            $category = Category::find($categoryId);
            
            // Validar si categoría es protegida
            if ($category && $category->is_protected) {
                $validator->errors()->add('is_protected', 
                    'Cannot modify protected category');
                return;
            }
            
            // Validar nivel del parent
            if ($this->parent_id) {
                $parent = Category::find($this->parent_id);
                if ($parent && $parent->level >= $this->level) {
                    $validator->errors()->add('parent_id', 
                        'Parent category must have a lower level');
                }
                
                // Validar NO ciclos
                $this->validateNoCircularReference($categoryId, $this->parent_id, $validator);
            }
        });
    }

    private function validateNoCircularReference($categoryId, $parentId, $validator)
    {
        $visited = [];
        $current = $parentId;
        
        while ($current) {
            if ($current == $categoryId) {
                $validator->errors()->add('parent_id', 
                    'Circular reference detected. A category cannot be its own ancestor.');
                return;
            }
            
            if (in_array($current, $visited)) break;
            
            $visited[] = $current;
            $parent = Category::find($current);
            $current = $parent ? $parent->parent_id : null;
        }
    }
}
