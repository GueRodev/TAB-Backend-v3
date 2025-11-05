<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\StoreCategoryRequest;
use App\Http\Requests\v1\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Mostrar listado de categorías.
     * GET /api/v1/categories
     */
    public function index()
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->active()
            ->ordered()
            ->withCount('products')
            ->get();
            
        return response()->json($categories);
    }
    
    /**
     * Mostrar una categoría específica.
     * GET /api/v1/categories/{id}
     */
    public function show($id)
    {
        $category = Category::with(['children', 'products'])
            ->withCount('products')
            ->findOrFail($id);
            
        return response()->json($category);
    }
    
    /**
     * Crear una nueva categoría.
     * POST /api/v1/categories
     */
    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        
        $category = Category::create($data);
        
        return response()->json([
            'message' => 'Categoría creada exitosamente',
            'category' => $category
        ], 201);
    }
    
    /**
     * Actualizar una categoría existente.
     * PUT /api/v1/categories/{id}
     */
    public function update($id, UpdateCategoryRequest $request)
    {
        $category = Category::findOrFail($id);
        
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        
        $category->update($data);
        
        return response()->json([
            'message' => 'Categoría actualizada exitosamente',
            'category' => $category
        ]);
    }
    
    /**
     * Reordenar múltiples categorías.
     * PUT /api/v1/categories/reorder
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.order' => 'required|integer|min:0',
        ]);
        
        foreach ($request->categories as $item) {
            Category::where('id', $item['id'])
                ->update(['order' => $item['order']]);
        }
        
        return response()->json([
            'message' => 'Orden actualizado exitosamente'
        ]);
    }
    
    /**
     * Eliminar una categoría (soft delete).
     * DELETE /api/v1/categories/{id}
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        // Prevenir eliminación de categoría protegida
        if ($category->is_protected) {
            return response()->json([
                'message' => 'No se puede eliminar una categoría protegida'
            ], 403);
        }
        
        // Validar que existe categoría "Otros"
        $otherCategory = Category::where('is_protected', true)->first();
        
        if (!$otherCategory) {
            return response()->json([
                'message' => 'Categoría protegida no encontrada. Error del sistema.',
                'error' => 'PROTECTED_CATEGORY_MISSING'
            ], 500);
        }
        
        // Contar productos antes de reasignar
        $productsCount = $category->products()->count();
        
        // REASIGNAR productos a "Otros" ANTES de eliminar
        if ($productsCount > 0) {
            $category->products()->update([
                'category_id' => $otherCategory->id
            ]);
        }
        
        // Eliminar categoría (soft delete)
        $category->delete();
        
        return response()->json([
            'message' => 'Categoría eliminada exitosamente',
            'productos_reasignados' => $productsCount
        ]);
    }
    
    /**
     * Eliminar permanentemente una categoría.
     * DELETE /api/v1/categories/{id}/force
     */
    public function forceDelete($id)
    {
        $category = Category::withTrashed()->findOrFail($id);
        
        if ($category->is_protected) {
            return response()->json([
                'message' => 'No se puede eliminar una categoría protegida'
            ], 403);
        }
        
        // Validar que existe categoría "Otros"
        $otherCategory = Category::where('is_protected', true)->first();
        
        if (!$otherCategory) {
            return response()->json([
                'message' => 'Categoría protegida no encontrada. Error del sistema.',
                'error' => 'PROTECTED_CATEGORY_MISSING'
            ], 500);
        }
        //Descomentar cuando se implementen los productos
        // REASIGNAR productos si aún tiene
       // $productsCount = $category->products()->count();
        
       // if ($productsCount > 0) {
       //     $category->products()->update([
       //         'category_id' => $otherCategory->id
       //     ]);
       // }
        
        $category->forceDelete();
        
        return response()->json([
            'message' => 'Categoría eliminada permanentemente de forma exitosa',
            //'productos_reasignados' => $productsCount
        ]);
    }
    
    /**
     * Restaurar una categoría eliminada (soft delete).
     * POST /api/v1/categories/{id}/restore
     */
    public function restore($id)
    {
        $category = Category::withTrashed()->findOrFail($id);
        $category->restore();
        
        return response()->json([
            'message' => 'Categoría restaurada exitosamente',
            'category' => $category
        ]);
    }
}