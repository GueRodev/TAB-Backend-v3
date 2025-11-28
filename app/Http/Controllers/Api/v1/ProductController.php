<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\StoreProductRequest;
use App\Http\Requests\v1\UpdateProductRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\ProductImageService;
// COMENTADO: No utilizamos notificaciones de nuevos productos por el momento
// use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected ProductImageService $imageService;

    public function __construct(ProductImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Mostrar listado de productos con filtros.
     * GET /api/v1/products
     */
    public function index(Request $request)
    {
        $query = Product::with('category')->withCount('stockMovements');
        
        // Aplicar filtros con when()
        $query->when($request->category_id, fn($q, $value) => $q->byCategory($value))
              ->when($request->brand, fn($q, $value) => $q->byBrand($value))
              ->when($request->status, fn($q, $value) => $q->where('status', $value))
              ->when($request->featured, fn($q) => $q->featured())
              ->when($request->search, fn($q, $value) => $q->search($value))
              ->when($request->in_stock, fn($q) => $q->inStock())
              ->when($request->min_price, fn($q, $value) => $q->where('price', '>=', $value))
              ->when($request->max_price, fn($q, $value) => $q->where('price', '<=', $value));
        
        // Ordenamiento con whitelist
        // Prevenir ataques de inyeccion de codigo SQL
             $allowedSorts = ['created_at', 'name', 'price', 'stock', 'brand'];
             $sort = in_array($request->sort, $allowedSorts) ? $request->sort : 'created_at';
             $order = in_array($request->order, ['asc', 'desc']) ? $request->order : 'desc';
             $query->orderBy($sort, $order);
             return $query->paginate(15);
    }
    
    /**
     * Mostrar productos destacados.
     * GET /api/v1/products/featured
     */
    public function featured()
    {
        $products = Product::with('category')
            ->featured()
            ->active()
            ->limit(12)
            ->get();
            
        return response()->json($products);
    }
    
    /**
     * Mostrar un producto específico.
     * GET /api/v1/products/{id}
     */
    public function show($id)
    {
        $product = Product::with(['category', 'stockMovements' => function($q) {
            $q->with('user:id,name,email')->latest()->limit(10);
        }])->findOrFail($id);
        
        return response()->json($product);
    }
    
    /**
     * Crear un nuevo producto.
     * POST /api/v1/products
     */
    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        
        // Remover 'image' del array (se maneja aparte)
        unset($data['image']);
        
        $product = Product::create($data);
        
        // Guardar imagen si existe
        $this->handleImageUpload($request, $product);
        
        // Registrar stock inicial
        $this->createInitialStockMovement($product);

        // Notificar a los administradores sobre el nuevo producto
        // COMENTADO: Solo se notifica cuando se crea un pedido desde el carrito
        // NotificationService::notifyNewProduct($product);

        return response()->json([
            'message' => 'Producto creado exitosamente',
            'product' => $product->fresh()
        ], 201);
    }
    
    /**
     * Actualizar un producto.
     * PUT /api/v1/products/{id}
     */
    public function update($id, UpdateProductRequest $request)
    {
        $product = Product::findOrFail($id);
        $oldStock = $product->stock;
        $oldCategoryId = $product->category_id;

        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        // Remover 'image' del array
        unset($data['image']);

        // Si se cambió la categoría manualmente, limpiar original_category_id
        if (isset($data['category_id']) && $data['category_id'] != $oldCategoryId) {
            $data['original_category_id'] = null;
        }

        $product->update($data);

        // Actualizar imagen si viene nueva
        $this->handleImageUpdate($request, $product);

        // Registrar movimiento de stock si cambió
        if ($oldStock != $product->stock) {
            $this->createStockMovement($product, 'ajuste',
                $product->stock - $oldStock, $oldStock, 'Actualización manual');
        }

        return response()->json([
            'message' => 'Producto actualizado exitosamente',
            'product' => $product->fresh()
        ]);
    }
    
    /**
     * Eliminar producto (soft delete).
     * DELETE /api/v1/products/{id}
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        // NO eliminar imagen en soft delete (puede restaurarse)
        $product->delete();
        
        return response()->json([
            'message' => 'Producto eliminado exitosamente'
        ]);
    }
    
    /**
     * Eliminar permanentemente un producto.
     * DELETE /api/v1/products/{id}/force
     */
    public function forceDelete($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        
        // Eliminar imagen antes de borrar producto
        if ($product->image_url) {
            $this->imageService->deleteImage($product->image_url);
        }
        
        $product->forceDelete(); // Stock movements se eliminan por cascade
        
        return response()->json([
            'message' => 'Producto eliminado permanentemente de forma exitosa'
        ]);
    }
    
    /**
     * Restaurar producto eliminado.
     * POST /api/v1/products/{id}/restore
     */
    public function restore($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();

        return response()->json([
            'message' => 'Producto restaurado exitosamente',
            'product' => $product
        ]);
    }

    /**
     * Obtener productos eliminados (recycle bin).
     * GET /api/v1/products/recycle-bin
     */
    public function recycleBin()
    {
        $deletedProducts = Product::onlyTrashed()
            ->with(['category' => function($query) {
                $query->withTrashed(); // Incluir categoría aunque esté eliminada
            }])
            ->orderBy('deleted_at', 'desc')
            ->get();

        return response()->json($deletedProducts);
    }

    /**
     * Obtener historial de movimientos de stock de un producto.
     * GET /api/v1/products/{id}/stock-movements
     */
    public function stockMovements($id)
    {
        $product = Product::findOrFail($id);

        $movements = $product->stockMovements()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($movements);
    }
    
    /**
     * Toggle featured status.
     * PATCH /api/v1/products/{id}/featured
     */
    public function toggleFeatured($id, Request $request)
    {
        $request->validate([
            'is_featured' => 'required|boolean',
        ]);

        $product = Product::findOrFail($id);
        $product->update(['is_featured' => $request->is_featured]);

        return response()->json([
            'message' => $request->is_featured
                ? 'Producto marcado como destacado'
                : 'Producto desmarcado como destacado',
            'product' => $product->fresh()
        ]);
    }

    /**
     * Ajustar stock de producto.
     * POST /api/v1/products/{id}/stock
     */
    public function adjustStock($id, Request $request)
    {
        $request->validate([
            'type' => 'required|in:entrada,salida,ajuste',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);
        
        $product = Product::findOrFail($id);
        $oldStock = $product->stock;
        
        // Validar stock insuficiente para salidas
        if ($request->type === 'salida' && $request->quantity > $oldStock) {
            return response()->json([
                'message' => 'Stock insuficiente',
                'disponible' => $oldStock,
                'solicitado' => $request->quantity
            ], 400);
        }
        
        // Calcular nuevo stock
        $newStock = match($request->type) {
            'entrada' => $oldStock + $request->quantity,
            'salida' => max(0, $oldStock - $request->quantity),
            'ajuste' => $request->quantity,
        };
        
        $product->update(['stock' => $newStock]);
        
        // Registrar movimiento
        $this->createStockMovement($product, $request->type, 
            $request->quantity, $oldStock, $request->reason);
        
        return response()->json([
            'message' => 'Stock ajustado exitosamente',
            'product' => $product->fresh(),
        ]);
    }
    
    // ========================================
    // MÉTODOS PRIVADOS
    // ========================================
    
    /**
     * Manejar subida de imagen al crear producto
     */
    private function handleImageUpload(Request $request, Product $product): void
    {
        if ($request->hasFile('image')) {
            $url = $this->imageService->saveImage($request->file('image'), $product->id);
            $product->update(['image_url' => $url]);
        }
    }
    
    /**
     * Manejar actualización de imagen
     */
    private function handleImageUpdate(Request $request, Product $product): void
    {
        if ($request->hasFile('image')) {
            // Eliminar imagen anterior
            if ($product->image_url) {
                $this->imageService->deleteImage($product->image_url);
            }
            
            // Guardar nueva imagen
            $url = $this->imageService->saveImage($request->file('image'), $product->id);
            $product->update(['image_url' => $url]);
        }
    }
    
    /**
     * Crear movimiento de stock inicial
     */
    private function createInitialStockMovement(Product $product): void
    {
        if ($product->stock > 0) {
            $this->createStockMovement($product, 'entrada', 
                $product->stock, 0, 'Stock inicial');
        }
    }
    
    /**
     * Crear registro de movimiento de stock
     */
    private function createStockMovement(
        Product $product, 
        string $type, 
        int $quantity, 
        int $stockBefore, 
        ?string $reason
    ): void {
        StockMovement::create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $product->stock,
            'reason' => $reason,
            'user_id' => auth()->id(),
        ]);
    }
}