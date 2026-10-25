<?php

namespace App\Controllers;

use App\Models\Notification;

class NotificationsController extends \Controller
{
    public function index()
    {
        try {
            $user = auth()->user();
            $isRead = request()->get('is_read');
            $page = request()->get('page', 1);
            $limit = request()->get('limit', 20);

            $query = Notification::where('user_id', $user->id);

            if ($isRead !== null) {
                $query->where('is_read', $isRead);
            }

            $total = $query->count();
            $notifications = $query->orderBy('created_at', 'DESC')
                                  ->limit($limit)
                                  ->offset(($page - 1) * $limit)
                                  ->get();

            return response()->json([
                'status' => 'success',
                'data' => $notifications,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function markAsRead($id)
    {
        try {
            $notification = Notification::findOrFail($id);

            if ($notification->user_id !== auth()->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }

            $notification->update([
                'is_read' => 1,
                'read_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Notification marked as read',
                'data' => $notification,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark notification as read',
            ], 400);
        }
    }

    public function markAllAsRead()
    {
        try {
            $user = auth()->user();

            Notification::where('user_id', $user->id)
                       ->where('is_read', 0)
                       ->update([
                           'is_read' => 1,
                           'read_at' => now(),
                       ]);

            return response()->json([
                'status' => 'success',
                'message' => 'All notifications marked as read',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark notifications as read',
            ], 400);
        }
    }

    public function delete($id)
    {
        try {
            $notification = Notification::findOrFail($id);

            if ($notification->user_id !== auth()->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }

            $notification->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Notification deleted',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete notification',
            ], 400);
        }
    }

    public function getUnreadCount()
    {
        try {
            $user = auth()->user();
            $count = Notification::where('user_id', $user->id)
                                ->where('is_read', 0)
                                ->count();

            return response()->json([
                'status' => 'success',
                'data' => ['unread_count' => $count],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch unread count',
            ], 400);
        }
    }
}
