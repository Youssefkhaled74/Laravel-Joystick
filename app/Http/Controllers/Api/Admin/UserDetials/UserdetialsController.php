<?php

namespace App\Http\Controllers\Api\Admin\UserDetials;

use App\Models\User;
use App\Models\Order;
use App\Models\Invoice;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\RepairRequests;

class UserdetialsController extends Controller
{
    use ApiResponse;
    public function getUserDetails($id)
    {
        $admin = auth()->guard('admin')->user();

        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $user = User::with('addresses')->findOrFail($id);
        if (!$user) {
            return $this->errorResponse(404, __('messages.user_not_found'));
        }


        $userData = [
            'name' => $user->username,
            'image' => env('APP_URL') . '/public/' . $user->profile_picture,
            'email' => $user->email,
            'phone' => $user->phone,
            'addresses' => $user->addresses->map(function ($address) {
                return [
                    'address' => $address->address,
                    'area' => $address->area->name ?? null,
                    'apartment_number' => $address->apartment_number,
                    'building_number' => $address->building_number,
                    'floor_number' => $address->floor_number,
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                    'address_link' => $address->address_link,
                ];
            }),
        ];

        return $this->successResponse(200, __('messages.user_details'), $userData);
    }
    public function getOrderInvoices($id)
    {
        $admin = auth()->guard('admin')->user();
    
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
    
        $user = User::find($id);
    
        if (!$user) {
            return $this->errorResponse(404, __('messages.user_not_found'));
        }
    
        $invoices = Invoice::whereHas('invoiceable', function ($query) use ($id) {
            $query->where('user_id', $id);
        })->where('invoiceable_type', Order::class)->get();
    
        $invoices->transform(function($invoice) {
            $invoice->items = json_decode($invoice->items, true);
            return $invoice;
        });
    
        return $this->successResponse(200, __('messages.invoices'), $invoices);
    }

    public function getRepairRequestInvoices($id)
    {
        $admin = auth()->guard('admin')->user();
    
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
    
        $user = User::find($id);
    
        if (!$user) {
            return $this->errorResponse(404, __('messages.user_not_found'));
        }
    
        $invoices = Invoice::whereHas('invoiceable', function ($query) use ($id) {
            $query->where('user_id', $id);
        })->where('invoiceable_type', RepairRequests::class)->get();
    
        $invoices->transform(function($invoice) {
            if (is_string($invoice->items)) {
                $invoice->items = json_decode($invoice->items, true);
            }
            return $invoice;
        });
        
    
        return $this->successResponse(200, __('messages.invoices'), $invoices);
    }

    public function getDevicesUser($id)
    {
        $admin = auth()->guard('admin')->user();
    
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
    
        $user = User::find($id);
    
        if (!$user) {
            return $this->errorResponse(404, __('messages.user_not_found'));
        }
    
        $devices = $user->devices;
    
        return $this->successResponse(200, __('messages.devices'), $devices);
    }
    
}
