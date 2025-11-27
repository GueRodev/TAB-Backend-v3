<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user.
     * GET /api/v1/notifications
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 50);

        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->paginate($perPage);

        return response()->json($notifications);
    }

    /**
     * Get unread count for authenticated user.
     * GET /api/v1/notifications/unread-count
     */
    public function unreadCount()
    {
        $count = NotificationService::getUnreadCount(auth()->id());

        return response()->json([
            'count' => $count
        ]);
    }

    /**
     * Mark a specific notification as read.
     * PUT /api/v1/notifications/{id}/read
     */
    public function markAsRead($id)
    {
        $notification = NotificationService::markAsRead($id, auth()->id());

        if (!$notification) {
            return response()->json([
                'message' => 'Notificación no encontrada'
            ], 404);
        }

        return response()->json([
            'message' => 'Notificación marcada como leída',
            'data' => $notification
        ]);
    }

    /**
     * Mark all notifications as read for authenticated user.
     * PUT /api/v1/notifications/read-all
     */
    public function markAllAsRead()
    {
        NotificationService::markAllAsRead(auth()->id());

        return response()->json([
            'message' => 'Todas las notificaciones marcadas como leídas'
        ]);
    }

    /**
     * Delete a specific notification.
     * DELETE /api/v1/notifications/{id}
     */
    public function destroy($id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notificación no encontrada'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notificación eliminada exitosamente'
        ]);
    }
}
