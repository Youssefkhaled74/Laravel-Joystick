<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\Address;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\AddressResource;
use App\Http\Requests\User\AddAddressRequest;
use App\Http\Requests\User\UpdateAddressRequest;

class AddressController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('CheckUserLogin');
    }

    public function addAddress(AddAddressRequest $request)
    {

        $user = auth()->guard('api')->user();
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $googleMapLink = "https://www.google.com/maps?q={$latitude},{$longitude}";
        $addressData = $request->address;
        $address = new Address();
        $address->user_id = $user->id;
        $address->is_main = 0;
        $address->address = $addressData;
        $address->apartment_number = $request->apartment_number;
        $address->building_number = $request->building_number;
        $address->floor_number = $request->floor_number;
        $address->key = $request->key;
        $address->address_link = $googleMapLink;
        $address->latitude = $latitude;
        $address->longitude = $longitude;
        $address->area_id = $request->area_id;
        $address->save();

        return $this->successResponse(201, __('messages.address_created_successfully'), new AddressResource($address));
    }
    public function updateAddress(AddAddressRequest $request, $addressId)
    {
        $user = auth()->guard('api')->user();
        $addressData = $request->address;
        $address = Address::where('user_id', $user->id)->where('id', $addressId)->first();

        if (!$address) {
            return $this->errorResponse(404, __('messages.address_not_found'));
        }
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $googleMapLink = "https://www.google.com/maps?q={$latitude},{$longitude}";
        $address->address = $addressData;
        $address->apartment_number = $request->apartment_number;
        $address->building_number = $request->building_number;
        $address->floor_number = $request->floor_number;
        $address->key = $request->key;
        $address->area_id = $request->area_id;
        $address->latitude = $latitude;
        $address->longitude = $longitude;
        $address->address_link = $googleMapLink;
        $address->save();

        return $this->successResponse(200, __('messages.address_updated_successfully'), new AddressResource($address));
    }

    public function deleteAddress($addressId)
    {
        $user = auth()->guard('api')->user();

        $address = Address::where('user_id', $user->id)->where('id', $addressId)->first();

        if (!$address) {
            return $this->errorResponse(404, __('messages.address_not_found'));
        }

        $address->delete();

        return $this->successResponse(200, __('messages.address_deleted_successfully'));
    }

    public function getAddresses(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
        $query = Address::where('user_id', $user->id); // Get addresses for authenticated user
        $addresses = paginate($query, AddressResource::class, $limit, $page);
        return $this->successResponse(200, __('messages.all_addresses'), $addresses);
    }
    
}
