<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Factory;

class FirebaseChannel
{
    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        // Check if the notification has a `toFirebase` method
        if (method_exists($notification, 'toFirebase')) {
            // Get the FCM token from the notifiable model
            $fcmToken = $notifiable->fcm_token;

            if ($fcmToken) {
                // Get the Firebase messaging instance
                $messaging = (new Factory)
                    ->withServiceAccount(config('firebase.credentials'))
                    ->createMessaging();

                // Get the Firebase message from the notification
                $message = $notification->toFirebase($notifiable);

                // Send the message
                $messaging->send($message);
            }
        }
    }
}