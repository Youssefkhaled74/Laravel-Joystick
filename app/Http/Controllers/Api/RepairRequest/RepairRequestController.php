<?php

namespace App\Http\Controllers\Api\RepairRequest;

use App\Models\Admin;
use App\Models\Address;
use App\Models\Location;
use App\Models\Technicion;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\AvailableTime;
use App\Models\Proplems_Parts;
use App\Models\RepairRequests;
use App\Http\Controllers\Controller;
use App\Models\RepairRequestDevices;
use App\Services\NotificationService;
use App\Notifications\RepairRequestNotification;
use App\Notifications\AdminRepairRequestNotification;
use App\Notifications\TechnicianRepairRequestNotification;

class RepairRequestController extends Controller
{
    use ApiResponse;
    public function history()
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $requests = RepairRequests::where('user_id', $user->id)
            ->with(['day', 'availableTime'])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'day_id', 'available_time_id', 'code', 'status', 'created_at']);

        return $this->successResponse(200, 'message.repair_request_history_retrieved_successfully', $requests);
    }
    public function grandGet()
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $requests = RepairRequests::where('user_id', $user->id)
            ->with([
                'day:id,date',
                'availableTime:id,time',
                'address:id,address',
                'devices.device:id,device_name',
                'devices',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedRequests = $requests->map(function ($request) {
            return [
                'code' => $request->code,
                'number_of_devices' => $request->devices->count(),
                'day' => $request->day->date ?? null,
                'available_time' => $request->availableTime->time ?? null,
                'user_phone' => $request->user->phone,
                'user_address' => $request->address->address ?? null,
                'devices' => $request->devices->map(function ($device) {
                    return [
                        'device_name' => $device->device->device_name ?? 'Unknown Device',
                        'notes' => $device->notes ?? '',
                        'problem_parts' => $device->problemPartsList()->pluck('number'),
                    ];
                }),
            ];
        });

        return $this->successResponse(200, 'messages.grand_repair_request_data_retrieved_successfully', $formattedRequests);
    }

    public function store(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $request->validate([
            'type' => 'required|in:personal,playstationCafe',
            'address_id' => 'required|exists:addresses,id',
            'day_id' => 'required|exists:days,id',
            'available_time_id' => 'required|exists:available_times,id',
            'devices' => 'required|array',
            'devices.*.device_id' => 'required|exists:devices,id',
            'devices.*.Problems_Parts' => 'required|array',
            'devices.*.Problems_Parts.*' => 'integer',
            'devices.*.notes' => 'nullable|string',
        ]);

        if (RepairRequests::where('user_id', $user->id)
            ->where('day_id', $request->day_id)
            ->where('available_time_id', $request->available_time_id)
            ->exists()) {
            return $this->errorResponse(400, __('messages.you_have_already_made_a_repair_request_for_the_selected_day_and_available_time'));
        }

        $availableTime = AvailableTime::find($request->available_time_id);
        if (!$availableTime || $availableTime->status !== 'available') {
            return $this->errorResponse(400, __('messages.the_selected_available_time_is_not_available'));
        }

        // استدعاء دالة تعيين الفريق المناسب
        $teamId = $this->assignTeamToRepairRequest($request->address_id);
        if (!$teamId) {
            return $this->errorResponse(400, __('messages.no_team_available_for_this_location'));
        }

        $availableTime->update(['status' => 'booked']);

        $code = '#' . rand(1111111, 9999999);
        while (RepairRequests::where('code', $code)->exists()) {
            $code = '#' . rand(1111111, 9999999);
        }

        $repairRequest = RepairRequests::create([
            'type' => $request->type,
            'user_id' => $user->id,
            'address_id' => $request->address_id,
            'day_id' => $request->day_id,
            'available_time_id' => $request->available_time_id,
            'status' => 'pending',
            'code' => $code,
            'team_id' => $teamId,
        ]);

        foreach ($request->devices as $device) {
            RepairRequestDevices::create([
                'repair_request_id' => $repairRequest->id,
                'device_id' => $device['device_id'],
                'problem_parts' => json_encode($device['Problems_Parts']),
                'notes' => $device['notes'] ?? null,
            ]);
        }

        $user->notify(new RepairRequestNotification($repairRequest));

        $admins = Admin::all();
        foreach ($admins as $admin) {
            $admin->notify(new AdminRepairRequestNotification($repairRequest));
        }

        $technicians = Technicion::where('team_id', $teamId)->get();
        foreach ($technicians as $technician) {
            $technician->notify(new TechnicianRepairRequestNotification($repairRequest, $user, Address::find($request->address_id)));
        }

        return $this->successResponse(201, __('messages.repair_request_created_successfully'), $repairRequest);
    }

    public function assignTeamToRepairRequest($addressId)
    {
        $address = Address::find($addressId);
        if (!$address) {
            return null; 
        }

        $earthRadius = 6371; // Earth's radius in kilometers

        // البحث عن أقرب موقع لموقع المستخدم
        $nearestLocation = Location::select('id', 'team_id', 'coordinates')
            ->with('team') 
            ->get()
            ->map(function ($location) use ($address, $earthRadius) {
                $coords = json_decode($location->coordinates, true);
                $distances = [];

                foreach ($coords as $coord) {
                    $lat1 = deg2rad($address->latitude);
                    $long1 = deg2rad($address->longitude);
                    $lat2 = deg2rad($coord['lat']);
                    $long2 = deg2rad($coord['long']);

                    $dlat = $lat2 - $lat1;
                    $dlong = $long2 - $long1;

                    $a = sin($dlat / 2) * sin($dlat / 2) +
                        cos($lat1) * cos($lat2) *
                        sin($dlong / 2) * sin($dlong / 2);

                    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

                    $distance = $earthRadius * $c; // Distance in kilometers

                    $distances[] = $distance;
                }

                return [
                    'location_id' => $location->id,
                    'team_id' => $location->team_id,
                    'distance' => min($distances),
                    'team_status' => $location->team->status ?? 'inactive', // Check team status
                ];
            })
            ->filter(function ($location) {
                // Filter out inactive teams
                return $location['team_status'] === 'active';
            })
            ->sortBy('distance')
            ->first();
        return $nearestLocation ? $nearestLocation['team_id'] : null;
    }

}
