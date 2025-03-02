<?php

namespace App\Http\Controllers\Api\Notification;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    use ApiResponse;
    public function getNotifications()
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, __('message.unauthorized'));
        }
        $notifications = $user->notifications()->latest()->get();
        return $this->successResponse(200, __('message.notifications_fetched_successfully'), $notifications);
    }
    public function markAsRead($id)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, __('message.unauthorized'));
        }
        $notification = DatabaseNotification::where('id', $id)
            ->where('notifiable_id', $user->id)
            ->first();

        if (!$notification) {
            return $this->errorResponse(404, __('message.notification_not_found'));
        }

        $notification->update(['read_at' => now()]);

        return $this->successResponse(200, __('message.notification_marked_as_read_successfully') , $notification);
    }
}