<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class TechnicianOrderApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database', 'firebase'];
    }

    public function toDatabase($notifiable)
    {
        // Get the language from the request headers (default to English if not provided)
        $lang = request()->header('lang', 'en');

        // Define the messages in both Arabic and English
        $messages = [
            'en' => [
                'message' => 'Your order has been approved.',
                'order_code' => 'Order Code: ' . $this->order->code,
                'status' => 'Status: ' . $this->order->status,
            ],
            'ar' => [
                'message' => 'تمت الموافقة على طلبك.',
                'order_code' => 'كود الطلب: ' . $this->order->code,
                'status' => 'الحالة: ' . $this->order->status,
            ],
        ];

        // Return the message based on the user's language
        return [
            'message' => $messages[$lang]['message'],
            'order_code' => $messages[$lang]['order_code'],
            'status' => $messages[$lang]['status'],
        ];
    }

    public function toFirebase($notifiable)
    {
        // Get the language from the request headers (default to English if not provided)
        $lang = request()->header('lang', 'en');

        // Define the messages in both Arabic and English
        $messages = [
            'en' => [
                'title' => 'Order Approved',
                'body' => 'Your order has been approved.',
            ],
            'ar' => [
                'title' => 'تمت الموافقة على الطلب',
                'body' => 'تمت الموافقة على طلبك.',
            ],
        ];

        // Send the FCM notification
        $fcmToken = $notifiable->fcm_token;

        if ($fcmToken) {
            $messaging = app('firebase.messaging');

            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(FirebaseNotification::create(
                    $messages[$lang]['title'], // Notification title
                    $messages[$lang]['body'] // Notification body
                ))
                ->withData([
                    'order_code' => $this->order->code,
                    'status' => $this->order->status,
                ]);

            $messaging->send($message);
        }
    }
}