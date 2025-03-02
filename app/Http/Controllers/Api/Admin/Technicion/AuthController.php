<?php

namespace App\Http\Controllers\Api\Admin\Technicion;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthController extends Controller
{
    use ApiResponse;
    public function technicionLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'password' => 'required',
            'fcm_token' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse(400, $validator->errors()->first());
        }

        $credentials = $request->only('phone', 'password');

        if (!$token = auth()->guard('technicion')->attempt($credentials)) {
            return $this->errorResponse(401, 'Invalid credentials');
        }

        if (auth()->guard('technicion')->user()->status === 'inactive') {
            return $this->errorResponse(401, 'Your account is inactive');
        }

        $token = JWTAuth::claims(['exp' => Carbon::now()->addHours(100)->timestamp])->fromUser(auth()->guard('technicion')->user());
        $technicion = auth()->guard('technicion')->user();
        $technicion->fcm_token = $request->fcm_token;
        $technicion->save();
        return $this->successResponse(200, 'Login successful', [
            'token' => $token,
            'technicion' => auth()->guard('technicion')->user(),
        ]);
    }
}
