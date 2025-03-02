<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class StoreKeeperHaveOrder extends Notification implements ShouldQueue
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
        // Get language from request headers (default: English)
        $lang = request()->header('lang', 'en');

        // Messages in both English and Arabic
        $messages = [
            'en' => [
                'message' => 'A new repair order has been created by a technician.',
                'order_notes' =>  $this->order->notes,
                'technician' =>  $this->order->technicion->username,
                'status' =>  $this->order->status,
            ],
            'ar' => [
                'message' => 'تم إنشاء طلب إصلاح جديد بواسطة الفني.',
                'order_notes' =>  $this->order->notes,
                'technician' =>  $this->order->technicion->username,
                'status' =>  $this->order->status,
            ],
        ];

        return [
            'message' => $messages[$lang]['message'],
            'order_notes' => $messages[$lang]['order_notes'],
            'technician' => $messages[$lang]['technician'],
            'status' => $messages[$lang]['status'],
        ];
    }

    public function toFirebase($notifiable)
    {
        // Get language from request headers (default: English)
        $lang = request()->header('lang', 'en');

        // Messages in both English and Arabic
        $messages = [
            'en' => [
                'title' => 'New Repair Order',
                'body' => 'A technician has created a new repair order.',
            ],
            'ar' => [
                'title' => 'طلب إصلاح جديد',
                'body' => 'قام فني بإنشاء طلب إصلاح جديد.',
            ],
        ];

        $fcmToken = $notifiable->fcm_token;

        if ($fcmToken) {
            $messaging = app('firebase.messaging');

            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(FirebaseNotification::create(
                    $messages[$lang]['title'],
                    $messages[$lang]['body']
                ))
                ->withData([
                    'order_notes' => $this->order->notes,
                    'technician' => $this->order->technicion->username,
                    'status' => $this->order->status,
                ]);

            $messaging->send($message);
        }
    }
}
