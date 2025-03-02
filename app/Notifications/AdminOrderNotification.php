<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use App\Models\Order;

class AdminOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database', 'firebase'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' =>  "New Order Placed #". $this->order->order_number,
            'order_id' => $this->order->id,
            'user' => $this->order->user->username,
            'user_phone' => $this->order->user->phone,
            'total' => $this->order->total,
            'payment_method' => $this->order->payment_method,
            'status' => $this->order->status,
        ];
    }

    public function toFirebase($notifiable)
    {
        $messaging = app('firebase.messaging');

        $message = CloudMessage::withTarget('token', $notifiable->fcm_token)
            ->withNotification(FirebaseNotification::create(
                'New Order Placed',
                "Order #" . $this->order->order_number . " has been created."
            ))
            ->withData([
                'order_id' => $this->order->id,
                'total' => $this->order->total,
            ]);

        $messaging->send($message);
    }
}
