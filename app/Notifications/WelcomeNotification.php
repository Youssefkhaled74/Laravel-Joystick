<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        // No need for additional data in this case
    }

    /**
     * Get the notification's delivery channels.
     *
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
     * @return array<string, mixed>
     */
public function toDatabase($notifiable)
{
    $lang = request()->header('lang', 'en');

    $messages = [
        'en' => [
            'title' => 'Welcome to Joystick!',
            'body' => 'Welcome to Joystick! Repair your devices or buy new ones for PlayStation, headphones, and more.',
        ],
        'ar' => [
            'title' => 'مرحبًا بكم في Joystick!',
            'body' => 'مرحبًا بكم في Joystick! قم بإصلاح أجهزتك أو شراء أجهزة جديدة للبلايستيشن، السماعات، والمزيد.',
        ],
    ];

    return [
        'title' => $messages[$lang]['title'],
        'body' => $messages[$lang]['body'],
        'date' => now()->toDateTimeString(),
    ];
}

    /**
     * Get the Firebase message representation of the notification.
     */
    public function toFirebase($notifiable)
    {
        // Get the language from the request headers (default to English if not provided)
        $lang = request()->header('lang', 'en');
    
        // Define the messages in both Arabic and English
        $messages = [
            'en' => [
                'title' => 'Welcome to Joystick!',
                'body' => 'Welcome to Joystick! Repair your devices or buy new ones for PlayStation, headphones, and more.',
            ],
            'ar' => [
                'title' => 'مرحبًا بكم في Joystick!',
                'body' => 'مرحبًا بكم في Joystick! قم بإصلاح أجهزتك أو شراء أجهزة جديدة للبلايستيشن، السماعات، والمزيد.',
            ],
        ];
    
        // Send the FCM notification
        $fcmToken = $notifiable->fcm_token; // Ensure your notifiable model has an `fcm_token` column
    
        if ($fcmToken) {
            $messaging = app('firebase.messaging');
    
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(FirebaseNotification::create(
                    $messages[$lang]['title'], // Notification title
                    $messages[$lang]['body'] // Notification body
                ))
                ->withData([
                    'date' => now()->toDateTimeString(), // Custom data
                ]);
    
            $messaging->send($message);
        }
    }
}
