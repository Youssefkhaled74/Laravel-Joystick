<?php

namespace App\Http\Controllers\Api\Admin\Technicion;

use App\Models\StoreKeeper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\MaintenanceStore;
use App\Models\RepairRequestOrder;
use App\Http\Controllers\Controller;
use App\Models\RepairRequestOrderItem;
use App\Notifications\StoreKeeperHaveOrder;

class RepairRequestOrderController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {
        $technician = auth()->guard('technicion')->user();

        if (!$technician) {
            return $this->errorResponse(404, __('messages.technician_not_found'));
        }

        $request->validate([
            'repair_request_id' => 'required|exists:repair_requests,id',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:maintenance_stores,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $order = RepairRequestOrder::create([
            'repair_request_id' => $request->repair_request_id,
            'technicion_id' => $technician->id,
            'notes' => $request->notes,
        ]);

        $orderItems = [];
        foreach ($request->items as $item) {
            $maintenanceItem = MaintenanceStore::find($item['item_id']);

            if (!$maintenanceItem) {
                return $this->errorResponse(400, __('messages.item_not_found', ['item' => $item['item_id']]));
            }

            if ($maintenanceItem->quantity < $item['quantity']) {
                return $this->errorResponse(400, __('messages.insufficient_quantity_for_item', ['item' => $item['item_id']]));
            }

            $orderItem = RepairRequestOrderItem::create([
                'repair_request_order_id' => $order->id,
                'item' => $item['item_id'],
                'quantity' => $item['quantity'],
            ]);

            // Decrease the quantity in the maintenance store when the storekeeper approves this  
            // $maintenanceItem->decrement('quantity', $item['quantity']);

            $orderItems[] = $orderItem;
        }

        $order->load('items');

        $storekeepers = StoreKeeper::all();

        foreach ($storekeepers as $storekeeper) {
            $storekeeper->notify(new StoreKeeperHaveOrder($order));
        }

        return $this->successResponse(200, __('messages.order_for_repair_request_created'), [
            'order' => $order,
        ]);
    }
}
