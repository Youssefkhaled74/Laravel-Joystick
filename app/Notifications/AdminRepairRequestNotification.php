<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use App\Models\RepairRequests;

class AdminRepairRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $repairRequest;

    public function __construct(RepairRequests $repairRequest)
    {
        $this->repairRequest = $repairRequest;
    }

    public function via($notifiable)
    {
        return ['database', 'firebase'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "New Repair Request Created: " . $this->repairRequest->code,
            'repair_request_id' => $this->repairRequest->id,
            'code' => $this->repairRequest->code,
            'customer_name' => $this->repairRequest->user->username,
            'status' => $this->repairRequest->status,
            'team_id' => $this->repairRequest->team_id,
        ];
    }

    public function toFirebase($notifiable)
    {
        $messaging = app('firebase.messaging');

        $message = CloudMessage::withTarget('token', $notifiable->fcm_token)
            ->withNotification(FirebaseNotification::create(
                'New Repair Request',
                "Repair Request " . $this->repairRequest->code . " has been created."
            ))
            ->withData([
                'repair_request_id' => $this->repairRequest->id,
                'team_id' => $this->repairRequest->team_id,
            ]);

        $messaging->send($message);
    }
}
