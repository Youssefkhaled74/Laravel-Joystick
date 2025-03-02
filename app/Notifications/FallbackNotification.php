<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class UserWaitingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $conversationId;
    protected $userName;

    /**
     * Create a new notification instance.
     *
     * @param int $conversationId
     * @param string $userName
     */
    public function __construct($conversationId, $userName)
    {
        $this->conversationId = $conversationId;
        $this->userName = $userName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        // Use both 'database' and 'firebase' channels
        return ['database', 'firebase'];
    }

    /**
     * Get the array representation of the notification for the database.
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => 'مستخدم ينتظر الرد',
            'body'  => "المستخدم {$this->userName} لم يتم فهم رسالته وسيكون بانتظار الرد.",
            'conversation_id' => $this->conversationId,
            'date'  => now()->toDateTimeString(),
        ];
    }

    /**
     * Get the Firebase message representation of the notification.
     *
     * @param mixed $notifiable
     */
    public function toFirebase($notifiable)
    {
        $fcmToken = $notifiable->fcm_token; // Ensure the customer service agent has an `fcm_token` attribute

        if ($fcmToken) {
            $messaging = app('firebase.messaging');

            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(FirebaseNotification::create(
                    'مستخدم ينتظر الرد', 
                    "المستخدم {$this->userName} لم يتم فهم رسالته وسيكون بانتظار الرد."
                ))
                ->withData([
                    'conversation_id' => $this->conversationId,
                    'date' => now()->toDateTimeString(),
                ]);

            $messaging->send($message);
        }
    }
}
