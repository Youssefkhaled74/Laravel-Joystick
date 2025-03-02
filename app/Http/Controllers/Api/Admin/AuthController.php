<?php

namespace App\Http\Controllers\Api\Admin;

use Carbon\Carbon;
use App\Models\Admin;
use App\Models\StoreKeeper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\AdminResource;
use App\Http\Requests\Admin\LoginRequest;

class AuthController extends Controller
{
    use ApiResponse;
    public function __construct()
    {
        $this->middleware('checkLoginAdmin')->except('login');
    }

    public function login(LoginRequest $request)
    {
        $type = $request->input('type');
        $credentials = $request->only('email', 'password');
        if ($type === 'storekeeper') {
            $storekeeper = StoreKeeper::where('email', $request->email)->first();
            if (!$storekeeper) {
                return $this->errorResponse(404, __('messages.storekeeper_not_found'));
            }
            if (!Hash::check($request->password, $storekeeper->password)) {
                return $this->errorResponse(401, __('messages.invalid_password'));
            }
            $token = auth()->guard('storekeeper')->claims(['exp' => Carbon::now()->addHours(100)->timestamp])->fromUser($storekeeper);
            $storekeeper->fcm_token = $request->fcm_token;
            $storekeeper->save();
            $storekeeper->token = $token;
            return $this->successResponse(200, __('messages.storekeeper_logged_in_successfully'), $storekeeper);
        } elseif ($type === 'admin') {
            $admin = Admin::where('email', $request->email)->first();
            if (!$admin) {
                return $this->errorResponse(404, __('messages.admin_not_found'));
            }
            if (!Hash::check($request->password, $admin->password)) {
                return $this->errorResponse(401, __('messages.invalid_password'));
            }
            $token = auth()->guard('admin')->claims(['exp' => Carbon::now()->addHours(100)->timestamp])->fromUser($admin);
            $admin->fcm_token = $request->fcm_token;
            $admin->save();
            $admin->token = $token;
            return $this->successResponse(200, __('messages.admin_logged_in_successfully'), AdminResource::make($admin));
        } else {
            return $this->errorResponse(400, __('messages.invalid_type'));
        }
    }

    public function logout(Request $request)
    {

        if (auth()->guard('admin')->check()) {
            auth()->guard('admin')->logout();
            return $this->successResponse(200, __('messages.admin_logged_out_successfully'));
        } else {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
    }
}
