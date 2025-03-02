<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class RepairRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $repairRequest;

    public function __construct($repairRequest)
    {
        $this->repairRequest = $repairRequest;
    }

    public function via($notifiable)
    {
        // Use both 'database' and 'firebase' channels
        return ['database', 'firebase'];
    }

    public function toDatabase($notifiable)
    {
        // Store the notification in the database
        return [
            'message' => 'Your repair request has been created successfully.',
            'code' => $this->repairRequest->code,
            'date' => now()->toDateTimeString(),
        ];
    }

    public function toFirebase($notifiable)
    {
        // Send the FCM notification
        $fcmToken = $notifiable->fcm_token; // Ensure your notifiable model has an `fcm_token` column

        if ($fcmToken) {
            $messaging = app('firebase.messaging');

            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(FirebaseNotification::create(
                    'Repair Request Created', // Notification title
                    'Your repair request has been created successfully.' // Notification body
                ))
                ->withData([
                    'code' => $this->repairRequest->code, // Custom data
                    'date' => now()->toDateTimeString(),
                ]);

            $messaging->send($message);
        }
    }
}