<?php

namespace App\Http\Controllers\Api\Admin\Notification;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    use ApiResponse;
    public function getNotifications()
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $notifications = $admin->notifications()->latest()->get();
        return $this->successResponse(200, __('message.notifications_fetched_successfully'), $notifications);
    }
    public function markAsRead($id)
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $notification = DatabaseNotification::where('id', $id)
            ->where('notifiable_id', $admin->id)
            ->first();

        if (!$notification) {
            return $this->errorResponse(404, __('messages.notification_not_found'));
        }

        $notification->update(['read_at' => now()]);

        return $this->successResponse(200, __('messages.notification_marked_as_read_successfully'), $notification);
    }
}
