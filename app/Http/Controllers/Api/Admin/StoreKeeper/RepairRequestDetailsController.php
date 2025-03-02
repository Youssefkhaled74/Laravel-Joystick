<?php

namespace App\Http\Controllers\Api\Admin\StoreKeeper;

use App\Models\Invoice;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\RepairRequests;
use App\Models\MaintenanceStore;
use App\Models\RepairRequestOrder;
use App\Http\Controllers\Controller;
use App\Models\TechnicianMaintenanceStore;
use App\Notifications\TechnicianOrderApprovedNotification;

class RepairRequestDetailsController extends Controller
{
    use ApiResponse;
    public function show($id)
    {
        $storekeeper = auth()->guard('storekeeper')->user();
        if (!$storekeeper) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $repairRequest = RepairRequests::with(['user', 'address', 'day', 'availableTime', 'devices', 'team', 'orders', 'orderItems'])
            ->where('id', $id)
            ->first();

        if (!$repairRequest) {
            return $this->errorResponse(404, __('messages.order_not_found'));
        }
        return $this->successResponse(200, __('messages.data_returned_successfully'), $repairRequest);

    }

    // public function approveOrder($id)
    // {
    //     $storekeeper = auth()->guard('storekeeper')->user();
    //     if (!$storekeeper) {
    //         return $this->errorResponse(401, __('messages.unauthorized'));
    //     }
    
    //     $order = RepairRequestOrder::findOrFail($id);
    
    //     if ($order->status == 'approved') {
    //         return $this->errorResponse(400, 'Order already approved');
    //     }
    
    //     $order->status = 'approved';
    //     $order->save();
    
    //     $serialNumber = 'INV-' . strtoupper(uniqid());
    
    //     $repairRequest = $order->repairRequest;
    
    //     $items = $order->items->map(function ($item) {
    //         $maintenanceItem = MaintenanceStore::where('name->' . app()->getLocale(), $item->item)->first();
    
    //         if (!$maintenanceItem) {
    //             return $this->errorResponse(400, __('messages.item_not_found', ['item' => $item->item]));
    //         }
    
    //         if ($maintenanceItem->quantity < $item->quantity) {
    //             return $this->errorResponse(400, __('messages.insufficient_quantity_for_item', ['item' => $item->item]));
    //         }
    
    //         $maintenanceItem->decrement('quantity', $item->quantity);
    
    //         return [
    //             'item_id' => $maintenanceItem->id,
    //             'item' => $maintenanceItem->name,
    //             'quantity' => $item->quantity,
    //             'price' => $maintenanceItem->price,
    //         ];
    //     });
    
    //     $totalPrice = $items->sum(function ($item) {
    //         return $item['quantity'] * $item['price'];
    //     });
    
    //     $invoice = Invoice::create([
    //         'serial_number' => $serialNumber,
    //         'date' => $repairRequest->day->date,
    //         'time' => $repairRequest->availableTime->time,
    //         'items' => $items,
    //         'total_price' => $totalPrice,
    //         'status' => 'pending',
    //         'invoiceable_id' => $repairRequest->id,
    //         'invoiceable_type' => RepairRequests::class,
    //     ]);
    
    //     return $this->successResponse(200, 'Order Approved', $invoice);
    // }

    public function approveOrder($id)
    {
        $storekeeper = auth()->guard('storekeeper')->user();
        if (!$storekeeper) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $order = RepairRequestOrder::find($id);

        if (!$order) {
            return $this->errorResponse(404, __('messages.order_not_found'));
        }

        if ($order->status == 'approved') {
            return $this->errorResponse(400, __('Order_already_approved'));
        }

        $order->status = 'approved';
        $order->save();

        $serialNumber = 'INV-' . strtoupper(uniqid());

        $repairRequest = $order->repairRequest;

        $items = $order->items->map(function ($item) use ($repairRequest, $order) {
            $maintenanceItem = MaintenanceStore::where('name->' . app()->getLocale(), $item->item)->first();

            if (!$maintenanceItem) {
                return $this->errorResponse(400, __('messages.item_not_found', ['item' => $item->item]));
            }

            if ($maintenanceItem->quantity < $item->quantity) {
                return $this->errorResponse(400, __('messages.insufficient_quantity_for_item', ['item' => $item->item]));
            }

            // Decrease the quantity in the maintenance store
            $maintenanceItem->decrement('quantity', $item->quantity);

            TechnicianMaintenanceStore::create([
                'repair_request_order_id' => $order->id,
                'maintenance_store_id' => $maintenanceItem->id,
                'quantity' => $item->quantity,
                'team_id' => $repairRequest->team_id,
                'repair_request_id'=> $order->repair_request_id,
            ]);

            return [
                'item_id' => $maintenanceItem->id,
                'item' => $maintenanceItem->name,
                'quantity' => $item->quantity,
                'price' => $maintenanceItem->price,
            ];
        });

        $totalPrice = $items->sum(function ($item) {
            return $item['quantity'] * $item['price'];
        });

        $invoice = Invoice::create([
            'serial_number' => $serialNumber,
            'date' => $repairRequest->day->date,
            'time' => $repairRequest->availableTime->time,
            'items' => $items,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'invoiceable_id' => $repairRequest->id,
            'invoiceable_type' => RepairRequests::class,
        ]);
        
        $technician = $repairRequest->technician;
        if ($technician) {
            $technician->notify(new TechnicianOrderApprovedNotification($order));
        }

        return $this->successResponse(200, 'Order Approved', $invoice);
    }
    public function allRepiarRequestsOrder(Request $request)
    {
        $storekeeper = auth()->guard('storekeeper')->user();
        if (!$storekeeper) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected',
        ]);
        if ($request->status) {
            $repairRequests = RepairRequestOrder::where('status', $request->status)->get();
            return $this->successResponse(200, __('messages.data_returned_successfully'), $repairRequests);
        }
        $repairRequests = RepairRequestOrder::all();
        return $this->successResponse(200, __('messages.data_returned_successfully'), $repairRequests);
    }
}
    