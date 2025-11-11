<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\StoreOnlineOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ClientOrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Listar pedidos del cliente autenticado
     *
     * Ruta: routes/v1/orders.php
     *
     * Filtros disponibles:
     * - status: pending, in_progress, completed, cancelled, archived
     * - order_type: online, in_store
     * - per_page: cantidad de resultados por página (default: 15)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $query = Order::with(['items', 'shippingAddress'])
                ->where('user_id', $user->id)
                // Filtro por estado del pedido
                ->when($request->filled('status'), function ($q) use ($request) {
                    $q->where('status', $request->status);
                })
                // Filtro por tipo de pedido
                ->when($request->filled('order_type'), function ($q) use ($request) {
                    $q->where('order_type', $request->order_type);
                })
                ->orderBy('created_at', 'desc');

            $perPage = $request->input('per_page', 15);
            $orders = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pedidos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un pedido online (desde carrito)
     * Ruta: routes/v1/orders.php
     */
    public function store(StoreOnlineOrderRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $order = $this->orderService->createOnlineOrder(
                $request->validated(),
                $user ? $user->id : null
            );

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'data' => $order,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Ver detalles de un pedido específico del cliente
     * Ruta: routes/v1/orders.php
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();

            $order = Order::with(['items', 'shippingAddress', 'stockMovements'])
                ->findOrFail($id);

            // Verificar que el pedido pertenece al usuario autenticado
            if ($order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver este pedido',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el pedido',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
