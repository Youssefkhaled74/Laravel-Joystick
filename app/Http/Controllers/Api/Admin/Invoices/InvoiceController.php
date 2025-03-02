<?php

namespace App\Http\Controllers\Api\Admin\Invoices;

use App\Models\Order;
use App\Models\Wallet;
use App\Models\Invoice;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\WalletHistory;
use App\Models\RepairRequests;
use App\Models\MaintenanceStore;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class InvoiceController extends Controller
{
    use ApiResponse;
    public function index()
    {
        $invoices = Invoice::all();
        return $this->successResponse(200, __('messages.all_invoices'), $invoices);
    }
    public function show($id)
    {
        $invoice = Invoice::find($id);
        return $this->successResponse(200, __('messages.invoice_details'), $invoice);
    }
    public function update(Request $request, $id)
    {
        $technician = auth()->guard('technicion')->user();
        if (!$technician) {
            return $this->errorResponse(404, __('messages.technician_not_found'));
        }

        $invoice = Invoice::find($id);
        if (!$invoice) {
            return $this->errorResponse(404, __('messages.invoice_not_found'));
        }

        if ($invoice->invoiceable_type !== RepairRequests::class) {
            return $this->errorResponse(403, __('messages.unauthorized_action'));
        }

        $totalPrice = 0;

        $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1|max:1',
        ]);

        $invoiceItems = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;

        $updated = false;

        foreach ($request->items as $updatedItem) {
            foreach ($invoiceItems as &$invoiceItem) {
                if ($invoiceItem['item_id'] == $updatedItem['item_id']) { // Match by item_id
                    if ($invoiceItem['quantity'] >= $updatedItem['quantity']) {
                        $invoiceItem['quantity'] -= $updatedItem['quantity'];
                        $updated = true;
                    } else {
                        return $this->errorResponse(400, __('messages.insufficient_quantity'), ['item_id' => $updatedItem['item_id']]);
                    }
                }
            }
        }

        if (!$updated) {
            return $this->errorResponse(400, __('messages.no_matching_item_found'));
        }

        foreach ($invoiceItems as $invoiceItem) {
            $totalPrice += $invoiceItem['price'] * $invoiceItem['quantity'];
        }

        $invoice->items = json_encode($invoiceItems);
        $invoice->total_price = $totalPrice;
        $invoice->save();

        $detailedItems = [];
        foreach ($invoiceItems as $item) {
            $maintenanceItem = MaintenanceStore::find($item['item_id']);
            if ($maintenanceItem) {
                $detailedItems[] = [
                    'item_id' => $item['item_id'],
                    'name' => $maintenanceItem->name,
                    'description' => $maintenanceItem->description,
                    'image' => env('APP_URL') . '/public/' . $maintenanceItem->image,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                ];
            }
        }

        return $this->successResponse(200, __('messages.invoice_updated_successfully'), [
            'id' => $invoice->id,
            'serial_number' => $invoice->serial_number,
            'invoiceable_id' => $invoice->invoiceable_id,
            'invoiceable_type' => $invoice->invoiceable_type,
            'date' => $invoice->date,
            'time' => $invoice->time,
            'items' => $detailedItems,
            'total_price' => $invoice->total_price,
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->updated_at,
        ]);
    }
    public function ApproveByUser(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:cashOnDelivery,bankTransfer',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse(400, $validator->errors()->first());
        }

        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $repairRequest = RepairRequests::where('user_id', $user->id)->where('status', 'pending')->first();
        if (!$repairRequest) {
            return $this->errorResponse(404, __('messages.repair_request_not_found'));
        }


        $invoice = Invoice::where('invoiceable_id', $repairRequest->id)
        ->where('status', 'pending')
        ->where('invoiceable_type', 'App\Models\RepairRequests')
        ->find($id);

        if (!$invoice) {
            return $this->errorResponse(404, __('messages.invoice_not_found'));
        }

        $invoice->status = 'approved';
        $invoice->payment_method = $request->payment_method;
        $invoice->save();

        if ($invoice->status == 'approved') {
            $ExistTeam = $repairRequest->team_id;
            $wallet = Wallet::where('team_id', $ExistTeam)->first();
            if ($wallet) {
                $wallet->balance += $invoice->total_price;
                $wallet->save();
            }
            $walletHistory = new WalletHistory();
            $walletHistory->user_id = $user->id;
            $walletHistory->team_id = $ExistTeam;
            $walletHistory->amount = $invoice->total_price;
            $walletHistory->save();
            $repairRequest->status = 'paid';
            $repairRequest->save();
        }
        $invoiceItems = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
        $formattedItems = [];

        foreach ($invoiceItems as $item) {
            $maintenanceItem = MaintenanceStore::find($item['item_id']);
            $formattedItems[] = [
                'id' => $item['item_id'],
                'name' => $maintenanceItem ? $maintenanceItem->name : 'Unknown Item',
                'price' => $item['price'],
                'quantity' => $item['quantity'],
            ];
        }

        return $this->successResponse(200, __('messages.invoice_status_updated_successfully'), [
            'id' => $invoice->id,
            'serial_number' => $invoice->serial_number,
            'repair_request_id' => $invoice->repair_request_id,
            'date' => $invoice->date,
            'time' => $invoice->time,
            'items' => $formattedItems, 
            'total_price' => $invoice->total_price,
            'payment_method' => $invoice->payment_method,
            'status' => $invoice->status,
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->updated_at,
        ]);
    }
    public function getInvoiceByRepairRequest()
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $repairRequest = RepairRequests::where('user_id', $user->id)->first();
        if (!$repairRequest) {
            return $this->errorResponse(404, __('messages.repair_request_not_found'));
        }

        $invoice = Invoice::where('invoiceable_id', $repairRequest->id)
            ->where('invoiceable_type', RepairRequests::class)
            ->first();

        if (!$invoice) {
            return $this->errorResponse(404, __('messages.invoice_not_found'));
        }

        $invoiceItems = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;

        $detailedItems = [];
        foreach ($invoiceItems as $item) {
            $maintenanceItem = MaintenanceStore::find($item['item_id']);

            if ($maintenanceItem) {
                $detailedItems[] = [
                    'item_id' => $item['item_id'],
                    'name' => $maintenanceItem->name,
                    'description' => $maintenanceItem->description,
                    'image' => env('APP_URL') . '/storage/' . $maintenanceItem->image,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                ];
            }
        }

        $invoiceData = [
            'id' => $invoice->id,
            'serial_number' => $invoice->serial_number,
            'invoiceable_id' => $invoice->invoiceable_id,
            'invoiceable_type' => $invoice->invoiceable_type,
            'date' => $invoice->date,
            'time' => $invoice->time,
            'items' => $detailedItems,
            'total_price' => $invoice->total_price,
            'payment_method' => $invoice->payment_method,
            'status' => $invoice->status,
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->updated_at,
        ];

        return $this->successResponse(200, __('messages.invoice_details'), $invoiceData);
    }
    public function getInvoiceByOrder()
    {
        $user = auth()->guard('api')->user();

        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $order = Order::where('user_id', $user->id)->first();

        if (!$order) {
            return $this->errorResponse(404, __('messages.order_not_found'));
        }

        $invoice = Invoice::where('invoiceable_id', $order->id)
            ->where('invoiceable_type', Order::class)
            ->first();

        if (!$invoice) {
            return $this->errorResponse(404, __('messages.invoice_not_found'));
        }

        $invoiceItems = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;

        $detailedItems = [];
        foreach ($invoiceItems as $item) {
            $product = Product::find($item['item_id']);

            if ($product) {
                $detailedItems[] = [
                    'item_id' => $item['item_id'],
                    'name' => $product->name,
                    'description' => $product->description,
                    'image' => env('APP_URL') . '/storage/' . $product->image,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                ];
            }
        }

        $invoiceData = [
            'id' => $invoice->id,
            'serial_number' => $invoice->serial_number,
            'invoiceable_id' => $invoice->invoiceable_id,
            'invoiceable_type' => $invoice->invoiceable_type,
            'date' => $invoice->date,
            'time' => $invoice->time,
            'items' => $detailedItems, // Now includes full item details
            'total_price' => $invoice->total_price,
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->updated_at,
        ];

        return $this->successResponse(200, __('messages.invoice_details'), $invoiceData);
    }
    public function getOrderInvoices(Request $request)
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(404, __('messages.admin_not_found'));
        }
    
        $limit = $request->limit ?? 10;
        $page = $request->page ?? 1;
    
        $query = Invoice::where('invoiceable_type', Order::class);
    
        $paginatedInvoices = paginateWithoutResource($query, $limit, $page);
    
        $paginatedInvoices['data'] = collect($paginatedInvoices['data'])->map(function ($invoice) {
            $decodedItems = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
    
            $itemsWithDetails = collect($decodedItems)->map(function ($item) {
                return [
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total_price' => $item['quantity'] * $item['price'],
                ];
            });
    
            $order = Order::find($invoice->invoiceable_id);
    
            return [
                'id' => $invoice->id,
                'username' => $order->user->username,
                'serial_number' => $invoice->serial_number,
                'date' => $invoice->date,
                'time' => $invoice->time,
                'items' => $itemsWithDetails,
                'total_price' => $invoice->total_price,
                'status' => $invoice->status,
                'payment_method' => $invoice->payment_method,
                'created_at' => $invoice->created_at,
                'updated_at' => $invoice->updated_at,
                'invoiceable_type' => $invoice->invoiceable_type,
                'invoiceable_id' => $invoice->invoiceable_id,
            ];
        });
    
        return $this->successResponse(200, __('messages.all_invoices'), $paginatedInvoices);
    }
    
    public function getRepairRequestInvoices(Request $request)
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(404, __('messages.admin_not_found'));
        }
    
        $limit = $request->limit ?? 10;
        $page = $request->page ?? 1;
    
        $query = Invoice::where('invoiceable_type', RepairRequests::class);
    
        $paginatedInvoices = paginateWithoutResource($query, $limit, $page);
    
        // Transform the data
        $paginatedInvoices['data'] = collect($paginatedInvoices['data'])->map(function ($invoice) {
            $decodedItems = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
    
            $itemsWithDetails = collect($decodedItems)->map(function ($item) {
                return [
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total_price' => $item['quantity'] * $item['price'],
                ];
            });
    
            $repair = RepairRequests::find($invoice->invoiceable_id);
    
            return [
                'id' => $invoice->id,
                'username' => $repair->user->username,
                'serial_number' => $invoice->serial_number,
                'date' => $invoice->date,
                'time' => $invoice->time,
                'items' => $itemsWithDetails,
                'total_price' => $invoice->total_price,
                'status' => $invoice->status,
                'payment_method' => $invoice->payment_method,
                'created_at' => $invoice->created_at,
                'updated_at' => $invoice->updated_at,
                'invoiceable_type' => $invoice->invoiceable_type,
                'invoiceable_id' => $invoice->invoiceable_id,
            ];
        });
    
        return $this->successResponse(200, __('messages.all_invoices'), $paginatedInvoices);
    }
    
    public function getOrderInvoiceDetails($id)
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $invoice = Invoice::with('invoiceable.user', 'invoiceable.items.product')->findOrFail($id);

        if ($invoice->invoiceable_type !== Order::class) {
            return $this->errorResponse(400, __('messages.invalid_invoice_type'));
        }

        $order = $invoice->invoiceable;
        $user = $order->user;

        $invoiceItems = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;

        $detailedItems = [];
        foreach ($invoiceItems as $item) {
            $product = Product::find($item['item_id']);

            if ($product) {
                $detailedItems[] = [
                    'serial_number' => $product->serial_number,
                    'name' => $product->name,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total_price' => $item['quantity'] * $item['price'],
                ];
            }
        }

        $totalPrice = array_sum(array_column($detailedItems, 'total_price'));

        $invoiceData = [
            'user_name' => $user->username,
            'serial_number' => $invoice->serial_number,
            'date' => $invoice->created_at,
            'products' => $detailedItems,
            'total_price' => $totalPrice,
        ];

        return $this->successResponse(200, __('messages.invoice_details'), $invoiceData);
    }
    public function getRepairRequestInvoiceDetails($id)
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $invoice = Invoice::with('invoiceable.user', 'invoiceable')->findOrFail($id);

        $invoiceable = $invoice->invoiceable;
        $user = $invoiceable->user;

        $invoiceItems = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;

        $detailedItems = [];
        foreach ($invoiceItems as $item) {
            if ($invoice->invoiceable_type === Order::class) {
                $product = Product::find($item['item_id']);
            } else {
                $product = MaintenanceStore::find($item['item_id']);
            }

            if ($product) {
                $detailedItems[] = [
                    'serial_number' => $product->serial_number ?? $product->uuid,
                    'name' => $product->name,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total_price' => $item['quantity'] * $item['price'],
                ];
            }
        }

        $totalPrice = array_sum(array_column($detailedItems, 'total_price'));

        $invoiceData = [
            'user_name' => $user->username,
            'serial_number' => $invoice->serial_number,
            'date' => $invoice->created_at,
            'products' => $detailedItems,
            'total_price' => $totalPrice,
        ];

        return $this->successResponse(200, __('messages.invoice_details'), $invoiceData);
    }
}
