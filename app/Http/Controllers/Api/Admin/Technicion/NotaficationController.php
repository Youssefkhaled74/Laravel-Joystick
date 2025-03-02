<?php

namespace App\Http\Controllers\Api\Admin\Technicion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotaficationController extends Controller
{
    use ApiResponse;

    public function getNotifications()
    {
        $user = auth()->guard('technicion')->user();
        if (!$user) {
            return $this->errorResponse(401, 'Unauthorized');
        }
        $notifications = $user->notifications()->latest()->get();
        // dd($notifications);
        return $this->successResponse(200, 'Notifications fetched successfully', $notifications);
    }

    public function markAsRead($id)
    {
        $user = auth()->guard('technicion')->user();
        if (!$user) {
            return $this->errorResponse(401, 'Unauthorized');
        }
        $notification = DatabaseNotification::where('id', $id)
            ->where('notifiable_id', $user->id)
            ->first();

        if (!$notification) {
            return $this->errorResponse(404, 'Notification not found');
        }
        $notification->update(['read_at' => now()]);
        return $this->successResponse(200, 'Notification marked as read successfully', $notification);
    }

}