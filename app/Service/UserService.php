// app/Services/UserService.php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserService
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'phone' => 'required|unique:users',
            'addresses' => 'required',
            'addresses.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        
        $user = User::create([  
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        foreach ($request->addresses as $address) {
            Address::create([
                'user_id' => $user->id,
                'address' => $address,
            ]);
        }

        // Assuming the Otp class has a generate method
        $this->otp->generate($user->phone);

        return response()->json([
            'message' => 'User registered successfully. OTP sent for phone verification.',
            'user' => $user,
        ]);
    }
}