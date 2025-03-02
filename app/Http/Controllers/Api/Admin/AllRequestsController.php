<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RepairRequests;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class AllRequestsController extends Controller
{
    use ApiResponse;
    public function getAllOrdersByDay(Request $request)
    {
        $admin = auth()->guard('admin')->user();

        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $request->validate([
            'day' => 'required|date',
        ]);

        $day = $request->day;

        $orders = Order::whereDate('created_at', $day)->get();

        $orderData = $orders->map(function ($order) {
            return [
                'order_id' => $order->id,
                'username' => $order->user->username,
                'user_profile' => env('APP_URL') . '/public/' . $order->user->profile_picture,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'payment_method' => $order->payment_method,
                'total_price' => $order->total,
            ];
        });

        return $this->successResponse(200, 'Orders retrieved successfully', $orderData);
    }
    public function getOrderById($id)
    {
        $admin = auth()->guard('admin')->user();
    
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
    
        $order = Order::with(['user', 'address', 'items.product', 'invoices'])
                      ->find($id);
    
        if (!$order) {
            return $this->errorResponse(404, __('messages.order_not_found'));
        }
    
        $orderDetails = $order->items->map(function ($item) {
            return [
                'product' => [
                    'image' => env('APP_URL') . '/public/' . $item?->product?->images,
                    'name' => $item?->product?->name,
                    'serial_number' => $item?->product?->serial_number,
                    'quantity' => $item?->quantity,
                    'price' => $item?->price,
                    'total_price' => $item?->quantity * $item?->price,
                ],
            ];
        });
    
        $invoice = $order->invoices->first();
    
        $orderData = [
            'order' => [
                'order_code'      => $order->order_number,
                'created_at'      => $order->created_at,
                'payment_method'  => $order->payment_method,
                'total_price'     => $order->total,
                'status'          => $order->status,
                'user_profile'    => env('APP_URL') . '/public/' . $order->user->profile_picture,
                'user_name'       => $order->user->username,
            ],
            'order_details' => $orderDetails,
            'user_information' => [
                'user_name' => $order->user->username,
                'email'     => $order->user->email,
                'phone'     => $order->user->phone,
            ],
            'documentary' => [
                'invoice_serial_number' => $invoice ? $invoice->serial_number : 'N/A',
                'shipping_serial_number' => 'SHIP-' . strtoupper(uniqid()),
            ],
            'address' => [
                'title' => optional($order->address)->address,
            ],
            'order_status' => [
                'order_status'     => $order->status,
                'order_created_at' => $order->created_at,
            ],
        ];
    
        return $this->successResponse(200, 'Orders retrieved successfully', $orderData);
    }
    public function getRepiarRequestsByDay(Request $request)
    {
        $admin = auth()->guard('admin')->user();
    
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
    
        $request->validate([
            'day' => 'nullable|date',
        ]);
    
        if ($request->day == null) {
            $ordersPendig = RepairRequests::where('status', 'pending')->get();
            $ordersStart = RepairRequests::where('status', 'start')->get();
            $ordersFinished = RepairRequests::where('status', 'paid')->get();
        } else {
            $day = $request->day;
    
            $ordersPendig = RepairRequests::whereDate('created_at', $day)->where('status', 'pending')->get();
            $ordersStart = RepairRequests::whereDate('created_at', $day)->where('status', 'start')->get();
            $ordersFinished = RepairRequests::whereDate('created_at', $day)->where('status', 'paid')->get();
        }
    
        $orderData['pending'] = $ordersPendig->map(function ($order) {
            $invoice = $order->invoices->first();
            $orderTechnician = $order->orders->first();
            return [
                'order_id' => $order->id,
                'username' => $order->user->username,
                'user_profile' => env('APP_URL') . '/public/' . $order->user->profile_picture,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'payment_method' => $invoice?->payment_method,
                'total_price' => $invoice?->total_price,
                'notes' => $orderTechnician?->notes,
            ];
        });
    
        $orderData['start'] = $ordersStart->map(function ($order) {
            $invoice = $order->invoices->first();
            $orderTechnician = $order->orders->first();
            return [
                'order_id' => $order->id,
                'username' => $order->user->username,
                'user_profile' => env('APP_URL') . '/public/' . $order->user->profile_picture,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'payment_method' => $invoice?->payment_method,
                'total_price' => $invoice?->total_price,
                'notes' => $orderTechnician?->notes,
            ];
        });
    
        $orderData['paid'] = $ordersFinished->map(function ($order) {
            $invoice = $order->invoices->first();
            $orderTechnician = $order->orders->first();
            return [
                'order_id' => $order->id,
                'username' => $order->user->username,
                'user_profile' => env('APP_URL') . '/public/' . $order->user->profile_picture,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'payment_method' => $invoice?->payment_method,
                'total_price' => $invoice?->total_price,
                'notes' => $orderTechnician?->notes,
            ];
        });
    
        // Add the count of each status
        $orderData['pending_count'] = $ordersPendig->count();
        $orderData['start_count'] = $ordersStart->count();
        $orderData['paid_count'] = $ordersFinished->count();
    
        return $this->successResponse(200, __('messages.repiar_requests_retrieved_successfully'), $orderData);
    }
    public function getRepairRequestById($id)
    {

        $repairRequest = RepairRequests::with(['user', 'address', 'orders.items', 'invoices', 'orders.technicion', 'devices.device'])
            ->find($id);

        if (!$repairRequest) {
            return $this->errorResponse(404, __('messages.repair_request_not_found'));
        }

        $orderDetails = $repairRequest->orders->map(function ($order) {
            return [
                'Items' => [
                    'name' => $order->item,
                    'serial_number' => $order->item,
                    'quantity' => $order->quantity,
                    'price' => $order->quantity * 10,  // Assuming a fixed price for simplicity
                        'total_price' => $order->quantity * 10,
                    ],
                ];
            });
    
        $technician = $repairRequest->orders->first()->technicion ?? null;
    
        $invoices = $repairRequest->invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'serial_number' => $invoice->serial_number,
                'date' => $invoice->date,
                'time' => $invoice->time,
                'items' => is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items, 
                'total_price' => $invoice->total_price,
                'status' => $invoice->status,
                'payment_method' => $invoice->payment_method,
                'created_at' => $invoice->created_at,
                'updated_at' => $invoice->updated_at,
                'invoiceable_type' => $invoice->invoiceable_type,
                'invoiceable_id' => $invoice->invoiceable_id,
            ];
        });
    
        $deviceDetails = $repairRequest->devices->map(function ($device) {
            return [
                'device_id' => $device->device_id,
                'device_name' => $device->device->device_name,
                'problem_parts' => $device->problem_parts,
                'notes' => $device->notes,
            ];
        });
    
        $orderData = [
            'order' => [
                'created_at' => $repairRequest->created_at,
                'payment_method' => optional($repairRequest->invoices->first())->payment_method ?? 'N/A',
                'total_price' => optional($repairRequest->invoices->first())->total_price ?? 0,
            ],
            'user_information' => [
                'user_name' => $repairRequest->user->username,
                'email' => $repairRequest->user->email,
                'phone' => $repairRequest->user->phone,
            ],
            'documentary' => [
                'invoices' => $invoices,
                'technician_order' => $repairRequest->orders,
                'repair_request' => [
                    'id' => $repairRequest->id,
                    'code' => $repairRequest->code,
                    'user_id' => $repairRequest->user_id,
                    'address_id' => $repairRequest->address_id,
                    'day_id' => $repairRequest->day_id,
                    'available_time_id' => $repairRequest->available_time_id,
                    'status' => $repairRequest->status,
                    'created_at' => $repairRequest->created_at,
                    'updated_at' => $repairRequest->updated_at,
                    'team_id' => $repairRequest->team_id,
                    'type' => $repairRequest->type,
                    'user' => [
                        'id' => $repairRequest->user->id,
                        'username' => $repairRequest->user->username,
                        'email' => $repairRequest->user->email,
                        'phone' => $repairRequest->user->phone,
                        'profile_pricture' => env('APP_URL') . '/public/' . $repairRequest->user->profile_picture,
                        'status' => $repairRequest->user->status,
                        'created_at' => $repairRequest->user->created_at,
                        'updated_at' => $repairRequest->user->updated_at,
                    ],
                    'devices' => $deviceDetails,
                ],
                'technician_name' => optional($technician)->username ?? 'N/A',
            ],
            'address' => [
                'title' => $repairRequest->address->address,
            ],
            'order_status' => [
                'order_status' => $repairRequest->status,
                'order_created_at' => $repairRequest->created_at,
            ],
            'order_details' => $orderDetails,
        ];
    
        return $this->successResponse(200, 'Orders retrieved successfully', $orderData);
    }
}
