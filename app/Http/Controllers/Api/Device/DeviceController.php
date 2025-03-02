<?php

namespace App\Http\Controllers\Api\Device;

use App\Models\Device;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

class DeviceController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $request->validate([
            'device_name' => 'string',
            'serial_number' => 'required|string|unique:devices',
            'purchase_date' => 'required|before:today|date_format:Y-m-d',
            'status' => 'required|in:new,old,used',
        ]);
        $device = new Device();
        $device->user_id = $user->id;
        $device->device_name = $request->device_name;
        $device->serial_number = $request->serial_number;
        $device->status = $request->status;
        $device->purchase_date = $request->purchase_date;
        $device->save();
        return $this->successResponse(200, __('messages.device_added'), $device);
    }
    public function index()
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $devices = $user->devices;
        return $this->successResponse(200, __('messages.all_devices'), $devices);
    }
    public function delete($id){
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $device = Device::find($id);
        if (!$device) {
            return $this->errorResponse(404, __('messages.device_not_found'));
        }
        if ($device->user_id !== $user->id) {
            return $this->errorResponse(403, __('messages.forbidden'));
        }
        $device->delete();
        return $this->successResponse(200, __('messages.device_deleted'));
    }
}
