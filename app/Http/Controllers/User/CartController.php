<?php

namespace App\Http\Controllers\User;

use App\Models\Cart;
use App\Models\User;
use App\Models\Product;
use App\Traits\ApiResponse;
use App\Models\ProductColor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Cart\AddToCartRequest;

class CartController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware(middleware: 'CheckUserLogin');
    }

    public function index()
    {
        $user = auth()->guard('api')->user();
        // dd($user);
        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthenticated'));
        }
        $limit = request()->get('limit', 10);
        $cartItems = Cart::where('user_id', $user->id)->with('product')->paginate($limit);
        return $this->successResponse(
            200,
            __('messages.product_added_to_cart_successfully'),
            CartResource::collection($cartItems)->response()->getData(true)
        );
    }


    // public function store(AddToCartRequest $request)
    // {
    //      $user = auth()->guard('api')->user();
    //     $product = Product::find($request->product_id);
    //     $productColor = ProductColor::where('product_id', $request->product_id)
    //         ->where('color', $request->color)
    //         ->first();

    //     if (!$productColor) {
    //         return $this->errorResponse(400, __('messages.validation_error'), __('messages.color_not_available'));
    //     }
    //     if ($request->quantity > $productColor->quantity) {
    //         return $this->errorResponse(400,  __('messages.quantity_exceeds_available_stock'));
    //     }

    //     $cartItem = Cart::where('user_id', $user->id)
    //         ->where('product_id', $request->product_id)
    //         ->where('color', $request->color)
    //         ->first();

    //     if ($cartItem) {
    //         $cartItem->quantity += $request->quantity;
    //         $cartItem->save();
    //         $productColor->quantity = $productColor->quantity - $request->quantity;
    //         $productColor->save();
    //         $product->quantity = $product->quantity - $request->quantity;
    //         $product->save();
    //     } else {
    //         Cart::create([
    //             'user_id' => $user->id,
    //             'product_id' => $product->id,
    //             'color' => $request->color,
    //             'quantity' => $request->quantity,
    //         ]);
    //         $productColor->quantity = $productColor->quantity - $request->quantity;
    //         $productColor->save();
    //         $product->quantity = $product->quantity - $request->quantity;
    //         $product->save();
    //     }
    //     return $this->successResponse(200, __('messages.product_added_to_cart'));
    // }
    public function store(AddToCartRequest $request)
    {
        $user = auth()->guard('api')->user();
        $product = Product::find($request->product_id);
        $productColor = ProductColor::where('product_id', $request->product_id)
            ->where('color', $request->color)
            ->first();

        if (!$productColor) {
            return $this->errorResponse(400, __('messages.validation_error'), __('messages.color_not_available'));
        }
        if ($request->quantity > $productColor->quantity) {
            return $this->errorResponse(400,  __('messages.quantity_exceeds_available_stock'));
        }

        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->where('color', $request->color)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'color' => $request->color,
                'quantity' => $request->quantity,
            ]);
        }
        return $this->successResponse(200, __('messages.product_added_to_cart'));
    }

    public function destroy(string $id)
    {
        $cartItem = Cart::find($id);
        if (!$cartItem) {
            return $this->errorResponse(404, __('messages.product_not_found_in_cart'));
        }
        $cartItem->delete();
        return $this->successResponse(200, __('messages.product_removed_from_cart_successfully'));
    }
    public function increaseQuantity(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
        ]);
        $cartItem = Cart::find($request->cart_id);
        $product = Product::find($cartItem->product_id);
        if ($cartItem->quantity + 1 > $product->quantity) {
            return $this->errorResponse(400, __('messages.validation_error'), __('messages.quantity_exceeds_available_stock'));
        }
        $cartItem->increment('quantity');
        return $this->successResponse(200, __('messages.quantity_increased_successfully'));
    }
    public function decreaseQuantity(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
        ]);
        $cartItem = Cart::find($request->cart_id);
        if ($cartItem->quantity <= 1) {
            return $this->errorResponse(400, __('messages.validation_error'), __('messages.quantity_must_be_at_least_one'));
        }
        $cartItem->decrement('quantity');
        return $this->successResponse(200, __('messages.quantity_decreased_successfully'));
    }
}
