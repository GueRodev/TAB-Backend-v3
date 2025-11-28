<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;

class NotificationService
{
    /**
     * Notify all admin users (Super Admin and Moderador).
     */
    public static function notifyAllAdmins($type, $title, $message, $data = [])
    {
        // Obtener todos los usuarios con rol Super Admin o Moderador
        $admins = User::role(['Super Admin', 'Moderador'])->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);
        }
    }

    /**
     * Notify about a new order.
     */
    public static function notifyNewOrder(Order $order)
    {
        $userName = $order->user->name ?? $order->customer_name ?? 'Cliente';

        self::notifyAllAdmins(
            'order',
            'Nuevo pedido recibido',
            "Pedido #{$order->id} de {$userName} por un total de ₡" . number_format($order->total, 2),
            [
                'order_id' => $order->id,
                'link' => '/admin/orders',
            ]
        );
    }

    /**
     * Notify about a completed order.
     * COMENTADO: Solo se notifica cuando se crea el pedido desde el carrito
     */
    // public static function notifyOrderCompleted(Order $order)
    // {
    //     self::notifyAllAdmins(
    //         'order',
    //         'Pedido completado',
    //         "El pedido #{$order->id} ha sido marcado como completado",
    //         [
    //             'order_id' => $order->id,
    //             'link' => '/admin/orders',
    //         ]
    //     );
    // }

    /**
     * Notify about a cancelled order.
     * COMENTADO: Solo se notifica cuando se crea el pedido desde el carrito
     */
    // public static function notifyOrderCancelled(Order $order)
    // {
    //     self::notifyAllAdmins(
    //         'order',
    //         'Pedido cancelado',
    //         "El pedido #{$order->id} ha sido cancelado",
    //         [
    //             'order_id' => $order->id,
    //             'link' => '/admin/orders',
    //         ]
    //     );
    // }

    /**
     * Notify about a deleted order.
     * COMENTADO: Solo se notifica cuando se crea el pedido desde el carrito
     */
    // public static function notifyOrderDeleted(Order $order)
    // {
    //     self::notifyAllAdmins(
    //         'order',
    //         'Pedido eliminado',
    //         "El pedido #{$order->id} ha sido eliminado",
    //         [
    //             'order_id' => $order->id,
    //             'link' => '/admin/orders-trashed',
    //         ]
    //     );
    // }

    /**
     * Notify about low stock.
     * COMENTADO: No se implementará por el momento
     * Puede implementarse en el futuro si es necesario
     */
    // public static function notifyLowStock(Product $product, $threshold = 3)
    // {
    //     if ($product->stock <= $threshold) {
    //         self::notifyAllAdmins(
    //             'stock',
    //             'Stock bajo',
    //             "El producto '{$product->name}' tiene solo {$product->stock} unidades disponibles",
    //             [
    //                 'product_id' => $product->id,
    //                 'stock' => $product->stock,
    //                 'link' => '/admin/products',
    //             ]
    //         );
    //     }
    // }

    /**
     * Notify about a new user registration.
     * COMENTADO: Solo se notifica cuando se crea el pedido desde el carrito
     */
    // public static function notifyNewUser(User $user)
    // {
    //     self::notifyAllAdmins(
    //         'user',
    //         'Nuevo usuario registrado',
    //         "{$user->name} se ha registrado como cliente",
    //         [
    //             'user_id' => $user->id,
    //             'email' => $user->email,
    //             'link' => '/admin/users',
    //         ]
    //     );
    // }

    /**
     * Notify about a new product.
     * COMENTADO: Solo se notifica cuando se crea el pedido desde el carrito
     */
    // public static function notifyNewProduct(Product $product)
    // {
    //     self::notifyAllAdmins(
    //         'product',
    //         'Nuevo producto creado',
    //         "Se ha agregado el producto '{$product->name}' al inventario",
    //         [
    //             'product_id' => $product->id,
    //             'link' => '/admin/products',
    //         ]
    //     );
    // }

    /**
     * Mark notification as read.
     */
    public static function markAsRead($notificationId, $userId)
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            return $notification;
        }

        return null;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public static function markAllAsRead($userId)
    {
        Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get notifications for a user.
     */
    public static function getUserNotifications($userId, $unreadOnly = false)
    {
        $query = Notification::where('user_id', $userId)
            ->latest();

        if ($unreadOnly) {
            $query->unread();
        }

        return $query->get();
    }

    /**
     * Get unread count for a user.
     */
    public static function getUnreadCount($userId)
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->count();
    }
}
