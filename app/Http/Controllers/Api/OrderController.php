<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Address;
use App\Models\Invoice;
use App\Models\Product;
use App\Traits\ApiResponse;
use App\Models\OrderDetails;
use App\Models\ProductColor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Notifications\AdminOrderNotification;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Requests\Order\ChangeStatusRequest;

class OrderController extends Controller
{
    use ApiResponse;


    public function __construct()
    {
        $this->middleware('auth:api')->except(['index', 'show', 'changeStatus']);
    }

    public function index(Request $request)
    {
        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
        $query = Order::query();
        $order = paginate($query, OrderResource::class, $limit, $page);
        return $this->successResponse(200, __('messages.all_orders'), $order);
    }

    public function store(CreateOrderRequest $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthenticated'));
        }
        $cartItems = Cart::where('user_id', $user->id)->get();
        if ($cartItems->isEmpty()) {
            return $this->errorResponse(400, __('messages.cart_empty'), __('messages.no_items_in_cart'));
        }
        $address = Address::find($request->address_id);

        if (!$address || $address->user_id !== $user->id) {
            return $this->errorResponse(404, __('messages.address_not_found'));
        }
        //total without coupon
        $total = $cartItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        $serial_number = 'ORD-' . strtoupper(uniqid());

        while (Order::where('serial_number', $serial_number)->exists()) {
            $serial_number = 'ORD-' . strtoupper(uniqid());
        }

        $order = Order::create([
            'user_id' => $user->id,
            'address_id' => $request->address_id,
            'phone' => $user->phone,
            'total' => $request->final_price,
            'first_name' => $user->username,
            'payment_method' => $request->payment_method,
            'serial_number' => $serial_number,
            'status' => 'pending',
            'order_number' => '#' . rand(100000, 999999),
        ]);

        $items = [];
        foreach ($cartItems as $item) {
            $totalp = $item->product->price * $item->quantity;
            OrderDetails::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'total' => $request->final_price,
                'quantity' => $item->quantity,
                'price' => $totalp,
            ]);

            // Decrement the product quantity
            $item->product->decrement('quantity', $item->quantity);

            // Decrement the product color quantity
            $productColor = ProductColor::where('product_id', $item->product_id)
                ->where('color', $item->color)
                ->first();
            if ($productColor) {
                $productColor->decrement('quantity', $item->quantity);
            }

            $items[] = [
                'item_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->price,
            ];
        }

        $totalPrice = array_sum(array_map(function ($item) {
            return $item['quantity'] * $item['price'];
        }, $items));

        $invoice = Invoice::create([
            'serial_number' => 'INV-' . strtoupper(uniqid()),
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'items' => json_encode($items),
            'total_price' => $totalPrice,
            'status' => 'pending',
            'payment_method' => $request->payment_method,
            'invoiceable_id' => $order->id,
            'invoiceable_type' => Order::class,
        ]);

        Cart::where('user_id', $user->id)->delete();

        $admins = Admin::all();
        foreach ($admins as $admin) {
            $admin->notify(new AdminOrderNotification($order));
        }

        return $this->successResponse(201, __('messages.order_created_successfully'), [
            'order' => new OrderResource($order),
            'invoice' => $invoice,
        ]);
    }



    public function changeStatus(ChangeStatusRequest $request)
    {
        $order = Order::find($request->order_id);
        $order->update(['status' => $request->status]);
        return $this->successResponse(200, __('messages.order_status_updated_successfully'), new OrderResource($order));
    }

    public function show(string $id)
    {
        $order = Order::with('items')->find($id);
        if (!$order) {
            return $this->errorResponse(404, __('messages.order_not_found'));
        }
        return $this->successResponse(200, __('messages.order_info'), new OrderResource($order));
    }

    public function update(UpdateOrderRequest $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return $this->errorResponse(404, __('messages.order_not_found'));
        }
        foreach ($order->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->quantity += $item->quantity;
                $product->save();
            }
        }
        $order->items()->delete();
        $order->delete();
        return $this->successResponse(200, __('messages.order_deleted_successfully'));
    }

    public function returnShipping(Request $request, $id)
    {
        if (!$id) {
            return $this->errorResponse(404, __('messages.send_address_to_calc_the_shipping'));
        }
        $address = Address::find($id);
        if (!$address) {
            return $this->errorResponse(404, __('messages.address_not_found'));
        }
        $shippingCost = 20;
        return $this->successResponse(200, __('messages.shipping_cost'), $shippingCost);
    }
}
