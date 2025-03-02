<?php

namespace App\Http\Controllers\Api;

use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\ApplayCouponRequest;
use App\Http\Requests\Coupon\CreateCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('checkLoginAdmin')->except(['index', 'show','applyCoupon']);
        $this->middleware('CheckUserLogin')->only('applyCoupon');
    }

    public function index(Request $request)
    {
        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
        $query = Coupon::query();
        $coupons = paginate($query, CouponResource::class, $limit, $page);
        return $this->successResponse(200, __('messages.all_coupons'), $coupons);
    }

    public function store(CreateCouponRequest $request)
    {
        $user_ids = $request->user_id;
        foreach ($user_ids as $user_id) {
            $data = $request->validated();
            $data['code'] = $this->generateUniqueCode();
            $data['status'] = 0;
            $data['user_id'] = $user_id;
            Coupon::create($data);
        }
        return $this->successResponse(200, __('messages.coupon_created_successfully'));
    }

    public function show(string $id)
    {
        $coupon = Coupon::find($id);
        if (! $coupon) {
            return $this->errorResponse(404, 'Coupon Not Found');
        }
        return $this->successResponse(200, __('messages.coupon_info'), CouponResource::make($coupon));
    }

    public function update(UpdateCouponRequest $request, string $id)
    {
        $coupon = Coupon::find($id);
        if (! $coupon) {
            return $this->errorResponse(404, __('messages.coupon_not_found'));
        }
        $data = $request->validated();
        $coupon->update($data);
        return $this->successResponse(200, __('messages.coupon_updated_successfully'), CouponResource::make($coupon));
    }

    public function destroy(string $id)
    {
        $coupon = Coupon::find($id);
        if(! $coupon){
            return $this->errorResponse(404, __('messages.coupon_not_found'));
        }
        $coupon->delete();
        return $this->successResponse(200, __('messages.coupon_deleted_successfully'));
    }

    public function applyCoupon(ApplayCouponRequest $request)
    {
        $coupon = Coupon::where('code', $request->code)->first();
        if ($coupon->expire_date < now()) {
            return $this->errorResponse(400, __('messages.coupon_is_expired'));
        }
        if ($coupon->user_id != auth()->guard('api')->id()) {
            return $this->errorResponse(400, __('messages.coupon_not_for_you'));
        }
        if ($coupon->status) {
            return $this->errorResponse(400, __('messages.coupon_already_used'));
        }
        if($request->total_price < $coupon->min_price){
            return $this->errorResponse(400, __('messages.min_price_must_be: ').$coupon->min_price);
        }
        $price = $request->total_price;
        if ($coupon->type == 1) {
            $price -= $coupon->price;
        } elseif ($coupon->type == 2) {
            $price -= ($request->total_price * $coupon->price / 100);
        }
        $coupon->update(['status' => 1]);
        return $this->successResponse(200, __('messages.coupon_applied_successfully'), $price);
    }

    private function generateUniqueCode()
    {
        do {
            $code = Str::random(8);
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }

}
