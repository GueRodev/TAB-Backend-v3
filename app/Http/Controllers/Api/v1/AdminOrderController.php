<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\StoreInStoreOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class AdminOrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Listar todos los pedidos (Super Admin)
     * Incluye pedidos online (desde carrito) e in_store (creados por admin)
     *
     * Ruta: routes/v1/admin_order.php
     *
     * Filtros disponibles:
     * - status: pending, in_progress, completed, cancelled, archived
     * - order_type: online, in_store
     * - delivery_option: pickup, delivery
     * - payment_method: cash, card, transfer, sinpe
     * - customer_email: búsqueda por email del cliente
     * - order_number: búsqueda por número de pedido
     * - per_page: cantidad de resultados por página (default: 15)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Order::with(['items', 'shippingAddress', 'user'])
                // Filtro por estado del pedido
                ->when($request->filled('status'), function ($q) use ($request) {
                    $q->where('status', $request->status);
                })
                // Filtro por tipo de pedido
                ->when($request->filled('order_type'), function ($q) use ($request) {
                    $q->where('order_type', $request->order_type);
                })
                // Filtro por opción de entrega
                ->when($request->filled('delivery_option'), function ($q) use ($request) {
                    $q->where('delivery_option', $request->delivery_option);
                })
                // Filtro por método de pago
                ->when($request->filled('payment_method'), function ($q) use ($request) {
                    $q->where('payment_method', $request->payment_method);
                })
                // Búsqueda por email del cliente
                ->when($request->filled('customer_email'), function ($q) use ($request) {
                    $q->where('customer_email', 'LIKE', '%' . $request->customer_email . '%');
                })
                // Búsqueda por número de pedido
                ->when($request->filled('order_number'), function ($q) use ($request) {
                    $q->where('order_number', 'LIKE', '%' . $request->order_number . '%');
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
     * Crear un pedido en tienda física (Super Admin)
     * Ruta: routes/v1/admin_order.php
     */
    public function store(StoreInStoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createInStoreOrder(
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Pedido en tienda creado exitosamente',
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
     * Ver detalles de cualquier pedido (Super Admin)
     * Ruta: routes/v1/admin_order.php
     */
    public function show(string $id): JsonResponse
    {
        try {
            $order = Order::with(['items', 'shippingAddress', 'user', 'stockMovements'])
                ->findOrFail($id);

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

    /**
     * Marcar pedido como en progreso
     * Aplica a pedidos online e in_store
     * Ruta: routes/v1/admin_order.php
     */
    public function markInProgress(string $id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);
            $updatedOrder = $this->orderService->markAsInProgress($order);

            return response()->json([
                'success' => true,
                'message' => 'Pedido marcado como en progreso',
                'data' => $updatedOrder,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pedido',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Completar un pedido (confirma stock y envía email)
     * Aplica a pedidos online e in_store
     * Ruta: routes/v1/admin_order.php
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);
            $updatedOrder = $this->orderService->completeOrder($order, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Pedido completado exitosamente',
                'data' => $updatedOrder,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al completar el pedido',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancelar un pedido (libera stock reservado)
     * Aplica a pedidos online e in_store
     * Ruta: routes/v1/admin_order.php
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);
            $updatedOrder = $this->orderService->cancelOrder($order, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado exitosamente',
                'data' => $updatedOrder,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar el pedido',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Archivar un pedido completado
     * Ruta: routes/v1/admin_order.php
     */
    public function archive(string $id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);
            $updatedOrder = $this->orderService->archiveOrder($order);

            return response()->json([
                'success' => true,
                'message' => 'Pedido archivado exitosamente',
                'data' => $updatedOrder,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al archivar el pedido',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Desarchivar un pedido (devolver a completado)
     * Ruta: routes/v1/admin_order.php
     */
    public function unarchive(string $id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);
            $updatedOrder = $this->orderService->unarchiveOrder($order);

            return response()->json([
                'success' => true,
                'message' => 'Pedido desarchivado exitosamente',
                'data' => $updatedOrder,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desarchivar el pedido',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Eliminar un pedido (soft delete)
     * Ruta: routes/v1/admin_order.php
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);
            $this->orderService->deleteOrder($order, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Pedido eliminado exitosamente',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el pedido',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Listar pedidos eliminados (soft deleted)
     * Ruta: routes/v1/admin_order.php
     */
    public function trashed(Request $request): JsonResponse
    {
        try {
            $query = Order::onlyTrashed()
                ->with(['items', 'shippingAddress', 'user'])
                ->when($request->filled('order_type'), function ($q) use ($request) {
                    $q->where('order_type', $request->order_type);
                })
                ->when($request->filled('customer_email'), function ($q) use ($request) {
                    $q->where('customer_email', 'LIKE', '%' . $request->customer_email . '%');
                })
                ->when($request->filled('order_number'), function ($q) use ($request) {
                    $q->where('order_number', 'LIKE', '%' . $request->order_number . '%');
                })
                ->orderBy('deleted_at', 'desc');

            $perPage = $request->input('per_page', 15);
            $orders = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pedidos eliminados',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restaurar un pedido eliminado
     * Ruta: routes/v1/admin_order.php
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $order = Order::onlyTrashed()->findOrFail($id);
            $order->restore();

            return response()->json([
                'success' => true,
                'message' => 'Pedido restaurado exitosamente',
                'data' => $order->load(['items', 'shippingAddress', 'user']),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el pedido',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
