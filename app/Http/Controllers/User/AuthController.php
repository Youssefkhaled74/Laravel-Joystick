<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use App\Models\User;
use Ichtrojan\Otp\Otp;
use App\Models\Address;
use App\Models\Product;
use App\Mail\SendOtpMail;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\AddressResource;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Artisan;
use App\Http\Requests\User\LoginRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\User\SendOtpRequest;
use App\Notifications\WelcomeNotification;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\VerifyOtpEmailRequest;
use App\Http\Requests\User\VerifyOtpPhoneRequest;
use App\Http\Requests\User\ConfirmPasswordRequest;
use App\Http\Requests\User\UpdateUserEmailRequest;
use App\Http\Requests\User\UpdateUserPhoneRequest;
use App\Http\Requests\User\OtpForgotPasswordRequest;
use App\Http\Requests\User\UpdateUserProfileRequest;

class AuthController extends Controller
{
    use ApiResponse;
    private $otp;

    public function __construct(Otp $otp)
    {
        $this->otp = $otp;
        $this->middleware('CheckUserLogin')->except(['register', 'sendOtp', 'verifyOtpPhone', 'login', 'sendOtpForgotPassword', 'otpForgotPassword', 'resetPassword']);
    }
    public function register(RegisterRequest $request)
    {
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $googleMapLink = "https://www.google.com/maps?q={$latitude},{$longitude}";
    
        $user = User::create([
            'username' => $request->username,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            // 'email' => $request->email,
            'status' => 'inactive',
            'fcm_token' => $request->fcm_token
        ]);
    
        if ($request->addresses || $latitude || $longitude || $request->apartment_number || $request->building_number || $request->floor_number) {
            Address::create([
                'user_id' => $user->id,
                'address' => $request->addresses,
                'apartment_number' => $request->apartment_number,
                'building_number' => $request->building_number,
                'floor_number' => $request->floor_number,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address_link' => $googleMapLink,
                'key' => 'Home',
                'is_main' => 1,
            ]);
        }
    
        $otpService = new Otp();
        $phone = $user->phone;
        $otpService->generate($phone, 'numeric', 6, 10);
    
        return $this->successResponse(200, __('messages.otp_sent_successfully_to_your_phone'));
    }
    
    public function sendOtp(SendOtpRequest $request)
    {
        if (!$request->has('email') && !$request->has('phone')) {
            return $this->errorResponse(400, __('messages.provide_email_or_phone'));
        }

        $otpService = new Otp();

        if ($request->has('phone')) {
            $phone = $request->phone;
            $otpResponse = $otpService->generate($phone, 'numeric', 6, 10);
            return $this->successResponse(200, __('messages.otp_sent_successfully_to_your_phone'), [
                'phone' => $phone,
            ]);
        }
        if ($request->has('email')) {
            $email = $request->email;
            $otpResponse = $otpService->generate($email, 'numeric', 6, 10);
            Mail::to($email)->send(new SendOtpMail($otpResponse->token));
            return $this->successResponse(200, __('messages.otp_sent_successfully_to_your_email'), [
                'email' => $email,
            ]);
        }

        return $this->errorResponse(400, __('messages.provide_email_or_phone'));
    }
    public function verifyOtpPhone(VerifyOtpPhoneRequest $request)
    {
        $otpService = new Otp();
    
        $phone = $request->phone;
        $otp = $request->otp;
        $otpVerification = $otpService->validate($phone, $otp);
    
        if (!$otpVerification->status) {
            return $this->errorResponse(400, $otpVerification->message);
        }
    
        $user = User::where('phone', $phone)->first();
    
        if (!$user) {
            return $this->errorResponse(404, __('messages.user_not_found'));
        }
    
        // Update status to 'active' if it was 'inactive'
        if ($user->status === 'inactive') {
            $user->status = 'active';
            $user->save();
            Artisan::call('otp:clean');
            // Send welcome notification
            // $user->notify(new WelcomeNotification());

        }
    
        $token = JWTAuth::claims(['exp' => Carbon::now()->addYears(100)->timestamp])->fromUser($user);
        $user->token = $token;
    
        return $this->successResponse(200, __('messages.phone_verified_successfully'), new UserResource($user));
    }
    
    public function login(LoginRequest $request)
    {
        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return $this->errorResponse(404, __('messages.user_not_found'));
        }
        
        if ($user->status === 'inactive') {
            return $this->errorResponse(403, __('messages.account_inactive'));
        }

        if (!Hash::check($request->password, $user->password)) {
            return $this->errorResponse(401, __('messages.password_does_not_match_the_stored_password'));
        }
        
        $user->fcm_token = $request->fcm_token;
        $user->save();
        $token = JWTAuth::claims(['exp' => Carbon::now()->addYears(100)->timestamp])->fromUser($user);
        $user->token = $token;
        return $this->successResponse(200, __('messages.user_logged_in_successfully'),  new UserResource($user));
    }
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken());
            return $this->successResponse(200, __('messages.user_logged_out_successfully'));
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return $this->errorResponse(401, 'Token is already invalid');
        } catch (\Exception $e) {
            return $this->errorResponse(500, 'An error occurred while trying to log out');
        }
    }
    public function sendOtpForgotPassword(OtpForgotPasswordRequest $request)
    {
        $phone = $request->phone;
        $otp = rand(100000, 999999);
        (new Otp)->generate($phone, 'numeric', 4, 10);
        return $this->successResponse(200, __('messages.otp_sent_successfully_to_your_phone'), [
            'phone' => $phone,
        ]);
    }
    public function otpForgotPassword(OtpForgotPasswordRequest $request)
    {
        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return $this->errorResponse(404, __('messages.user_not_found'));
        }

        $otp = new Otp();

        $vali = $otp->validate($user->phone, $request->otp);

        if (!$vali->status) {
            return $this->errorResponse(400, __('messages.invalid_otp'));
        }

        return $this->successResponse(200, __('messages.otp_verified'));
    }
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:11',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(400, $validator->errors()->first());
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return $this->errorResponse(404, __('messages.user_not_found'));
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return $this->successResponse(200, __('messages.password_updated_successfully'));
    }
    public function confirmPassword(ConfirmPasswordRequest $request)
    {
        $user = auth()->guard('api')->user();
        if (!Hash::check($request->password, $user->password)) {
            return $this->errorResponse(401, __('messages.password_does_not_match_the_stored_password'));
        }

        return $this->successResponse(200, __('messages.password confirmed'));
    }
    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = User::find(Auth::id());

        if (!Hash::check($request->old_password, $user->password)) {
            return $this->errorResponse(401, __('messages.password_does_not_match_the_stored_password'));
        }

        if ($request->old_password == $request->new_password) {
            return $this->errorResponse(400, __('messages.password_does_not_match_the_stored_password'));
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->successResponse(200, __('messages.password_updated_successfully'), UserResource::make($user));
    }
    public function update(UpdateUserProfileRequest $request)
    {
        $user = User::find(Auth::id());
        if ($request->hasFile('profile_picture')) {
            $imagePath = uploadImage($request, 'profile_picture', 'images/user');
            $user->profile_picture = $imagePath;
            $user->save();
        }

        if ($request->filled('username')) {
            $user->username = $request->username;
            $user->save();
        }

        return $this->successResponse(200, __('messages.profile_updated_successfully'), new UserResource($user));
    }
    public function updateUserEmail(UpdateUserEmailRequest $request)
    {
        $email = $request->email;

        $otps = (new Otp)->generate($email, 'numeric', 6, 10);

        $otp = $otps->token;

        Mail::to($email)->send(new SendOtpMail($otp));

        return $this->successResponse(200, __('messages.otp_sent_successfully_to_your_phone'), [
            'email' => $email,
            'otp' => $otp,
        ]);
    }
    public function verifyOtpEmail(VerifyOtpEmailRequest $request)
    {
        $email = $request->email;
        $otp = $request->otp;

        $otpService = new Otp();
        $otpVerification = $otpService->validate($email, $otp);

        if ($otpVerification->status) {
            return $this->errorResponse(400, $otpVerification->message);
        }
        $user = User::find(Auth::guard('api')->id());
        $user->email_verified_at = now();
        $user->email = $email;
        $user->save();
        return $this->successResponse(200, __('messages.email_verified_successfully'), new UserResource($user));
    }
    public function updateUserPhone(UpdateUserPhoneRequest $request)
    {
        $user = User::find(Auth::id());

        if (!$user) {
            return $this->errorResponse(404, __('messages.user_not_found'));
        }

        if ($request->phone !== $user->phone) {
            $phone = $request->phone;
            (new Otp)->generate($phone, 'numeric', 4, 10);

            return $this->successResponse(200, __('messages.otp_sent_successfully_to_your_phone'), [
                'phone' => $request->phone,
            ]);
        }
        return $this->errorResponse(400, __('messages.the_same_phone'));
    }
    public function verifyOtpUpdatePhone(VerifyOtpPhoneRequest $request)
    {
        $phone = $request->phone;
        $otp = $request->otp;

        $otpService = new Otp();
        $otpVerification = $otpService->validate($phone, $otp);
        Artisan::call('otp:clean');
        if (!$otpVerification->status) {
            return $this->errorResponse(400, $otpVerification->message);
        }
        $user = User::find(Auth::id());
        $user->phone = $phone;
        $user->save();
        return $this->successResponse(200,__('auth.'.'phone_updated_successfully'), new UserResource($user));
    }
    public function toggleFavorite($productId)
    {
        $user = User::find(Auth::id());

        $product = Product::find($productId);

        if (!$product) {
            return $this->errorResponse(404, __('messages.product_not_found'));
        }

        if ($user->favoriteProducts()->where('product_id', $product->id)->exists()) {
            $user->favoriteProducts()->detach($product->id);
            return $this->successResponse(200, __('messages.product_removed_from_favorites'));
        }

        $user->favoriteProducts()->attach($product->id);
        return $this->successResponse(200, __('messages.product_added_to_favorites'));
    }
    public function getfavoritedProducts(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
        $query = Product::query();
        $query->whereHas('favoritedBy', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
        $products = paginate($query, ProductResource::class, $limit, $page);
        return $this->successResponse(200, __('messages.all_favorited_products'), $products);
    }
    public function getUserDetails()
    {
        $user = auth()->guard('api')->user();
        
        $totalQuantity = $user->cart->sum('quantity');
    
        $userData = array_merge(
            (new UserResource($user))->toArray(request()),
            ['total_quantity' => $totalQuantity]
        );
    
        return $this->successResponse(
            200,
            __('messages.user_details_fetched_successfully'),
            $userData
        );
    }
    public function sendOtpToActiveUser(Request $request)
    {
        $user = auth()->guard('api')->user();

        if (!$user) {
            return $this->errorResponse(404, __('messages.user_not_found'));
        }

        if ($request->has('email')) {
            $email = $request->email;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->errorResponse(400, __('invalid email format'));
            }

            $userWithEmail = User::where('email', $email)->first();
            if (!$userWithEmail) {
                return $this->errorResponse(404, __('email not found'));
            }

            $otpService = new Otp();
            $otpResponse = $otpService->generate($email, 'numeric', 4, 10);

            Mail::to($email)->send(new SendOtpMail($otpResponse->token));

            return $this->successResponse(200, __('otp sent successfully to your email'), [
                'email' => $email,
            ]);
        } else {
            return $this->errorResponse(400, __('email required'));
        }
    }
    //return all ids for all product
    public function getAllProductIds()
    {
        //get all product ids just is favorited with this user authenticated 
        $user = auth()->guard('api')->user();
        $products = $user->favoriteProducts()->pluck('product_id');
        return $this->successResponse(200, 'success', $products);
    }
}
