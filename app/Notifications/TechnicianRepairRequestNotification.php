<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\RepairRequests;
use App\Models\User;
use App\Models\Address;

class TechnicianRepairRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $repairRequest;
    protected $user;
    protected $address;

    public function __construct(RepairRequests $repairRequest, User $user, Address $address)
    {
        $this->repairRequest = $repairRequest;
        $this->user = $user;
        $this->address = $address;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $lang = request()->header('lang', 'en');

        $messages = [
            'en' => [
                'message' => 'A new repair request has been assigned to you.',
                'repair_request_code' => 'Repair Request Code: ' . $this->repairRequest->code,
                'user_name' => 'User Name: ' . $this->user->username,
                'user_phone' => 'User Phone: ' . $this->user->phone,
                'address' => 'Address: ' . $this->address->address,
            ],
            'ar' => [
                'message' => 'تم تعيين طلب إصلاح جديد لك.',
                'repair_request_code' => 'كود طلب الإصلاح: ' . $this->repairRequest->code,
                'user_name' => 'اسم المستخدم: ' . $this->user->username,
                'user_phone' => 'هاتف المستخدم: ' . $this->user->phone,
                'address' => 'العنوان: ' . $this->address->address,
            ],
        ];

        return [
            'message' => $messages[$lang]['message'],
            'repair_request_code' => $messages[$lang]['repair_request_code'],
            'user_name' => $messages[$lang]['user_name'],
            'user_phone' => $messages[$lang]['user_phone'],
            'address' => $messages[$lang]['address'],
        ];
    }
}