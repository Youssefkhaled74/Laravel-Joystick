<?php

namespace App\Service;

use Illuminate\Support\Facades\Log;
namespace App\Service;

use Kreait\Firebase\Database;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use log;
class FirebaseService
{

    protected $messaging;
    protected $database;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(config('firebase.credentials.file')) 
            ->withDatabaseUri('https://joystick-3da1f-default-rtdb.firebaseio.com'); 

        $this->database = $factory->createDatabase();
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    // public function __construct()
    // {
    //     $filePath = env('FIREBASE_CREDENTIALS');
    //     $absolutePath = base_path($filePath);

    //     try {
    //         $firebase = (new Factory)->withServiceAccount($absolutePath);
    //         $this->messaging = $firebase->createMessaging();
    //         $this->database = $firebase->createDatabase();
    //     } catch (\Exception $e) {
    //         dd('Error initializing Firebase: ' . $e->getMessage());
    //     }

    //     // $firebase = (new Factory)->withServiceAccount('C:\\laragon\\www\\joy-stick-backend\\joystick-3da1f-firebase-adminsdk-fbsvc-5dc94fbcdf.json');
    //     // $this->messaging = $firebase->createMessaging();
    //     // $this->database = $firebase->createDatabase();
    // }

    public function sendNotification($token, $title, $body, $data = [])
    {
        // Validate the FCM token format
        if (empty($token) || $token === 'faketoken123456789') {
            \Log::warning("Invalid FCM token: $token");
            return false; // Skip sending notification
        }

        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                ])
                ->withData($data);

            $this->messaging->send($message);
        } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
            \Log::error("Notification failed: " . $e->getMessage());
            throw $e; // Re-throw if you want to log this higher up
        } catch (\Exception $e) {
            \Log::error("Unexpected error: " . $e->getMessage());
            throw $e; // Handle other exceptions
        }
    }



    public function sendNotificationChat($token, $title, $body, $data = [])
    {
        // Validate the FCM token format
        if (empty($token) || $token === 'faketoken123456789') {
            \Log::warning("Invalid FCM token: $token");
            return false; // Skip sending notification
        }

        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);
        } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
            \Log::error("Notification failed: " . $e->getMessage());
            throw $e; // Re-throw if you want to log this higher up
        } catch (\Exception $e) {
            \Log::error("Unexpected error: " . $e->getMessage());
            throw $e; // Handle other exceptions
        }
    }
    public function sendMessage($chatId, $messageData)
    {
        try {
            $this->database->getReference("chats/{$chatId}")
                ->push($messageData);
            \Log::info("Message sent to Firebase: ", $messageData);
        } catch (\Exception $e) {
            \Log::error("Error sending message to Firebase: " . $e->getMessage());
        }
    }

    public function getMessages($chatId)
    {
        return $this->database->getReference("chats/{$chatId}")->getValue();
    }



    public function sendPushNotification($token, $title, $body, $data)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $serverKey = env('FIREBASE_CREDENTIALS');;

        $fields = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => $data,
        ];

        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $response = curl_exec($ch);

        if ($response === FALSE) {
            die('FCM send failed: ' . curl_error($ch));
        }

        curl_close($ch);

        return $response;
    }
}
