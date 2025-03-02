<?php

namespace App\Http\Controllers\Api\Admin\Technicion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RepairRequests;
use App\Models\Technicion;
use App\Models\Team;
use App\Traits\ApiResponse;

class TasksController extends Controller
{
    use ApiResponse;
    public function getAll()
    {
        $technician = auth()->guard('technicion')->user();
        if (!$technician) {
            return $this->errorResponse(401, 'Unauthorized');
        }

        $tasks = RepairRequests::with(['day', 'availableTime', 'address'])
            ->where('team_id',$technician->team_id)
            ->get();

        $formattedTasks = $tasks->map(function ($task) {
            return [
                'id'=>$task->id,
                'title' => 'Repair Request ' . $task->code,
                'body' => 'Repair request for ' . $task->user->username,
                'day' => $task->day->date ?? null,
                'time_available' => $task->availableTime->time ?? null,
                'address' => $task->address->address ?? 'Unknown Address',
            ];
        });

        return $this->successResponse(200, 'Tasks retrieved successfully', $formattedTasks);
    }



    public function techniciongrandGet($id)
    {
        $technicion = auth()->guard('technicion')->user();
        if (!$technicion) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        // Fetch the repair request only if it belongs to the technician's team
        $request = RepairRequests::with([
                'day:id,date',
                'availableTime:id,time',
                'address:id,address',
                'devices.device:id,device_name',
                'devices',
            ])
            ->where('team_id', $technicion->team_id)
            ->where('id', $id)
            ->first(); 
        if (!$request) {
            return $this->errorResponse(404, __('messages.request_not_found'));
        }

        // Format the response
        $formattedRequest = [
            'code' => $request->code,
            'number_of_devices' => $request->devices->count(),
            'day' => $request->day->date ?? null,
            'available_time' => $request->availableTime->time ?? null,
            'user_phone' => optional($request->user)->phone, 
            'user_address' => optional($request->address)->address ?? 'Unknown Address',
            'devices' => $request->devices->map(function ($device) {
                return [
                    'device_name' => optional($device->device)->device_name ?? 'Unknown Device',
                    'notes' => $device->notes ?? '',
                    'problem_parts' => $device->problemPartsList()->pluck('number'),
                ];
            }),
        ];

        return $this->successResponse(200, __('messages.grand_repair_request_data_retrieved_successfully'), $formattedRequest);
    }

    public function getTechnicianTeamItemsUsed(Request $request)
    {
        $technician = auth()->guard('technicion')->user();
    
        if (!$technician) {
            return $this->errorResponse(401, __('messages.technician_not_found'));
        }
    
        $team = Team::with(['technicianMaintenanceStores.maintenanceStore'])
                    ->find($technician->team_id);
    
        if (!$team) {
            return $this->errorResponse(404, __('messages.team_not_found'));
        }
    
        $search = $request->query('search');
        $itemsUsed = [];
    
        foreach ($team->technicianMaintenanceStores as $store) {
            $itemName = $store->maintenanceStore->name; 
            $quantity = $store->quantity;
    
            // Apply search filter if a search term is provided
            if ($search && stripos($itemName, $search) === false) {
                continue;
            }
    
            if (isset($itemsUsed[$itemName])) {
                $itemsUsed[$itemName] += $quantity;
            } else {
                $itemsUsed[$itemName] = $quantity;
            }
        }
    
        return $this->successResponse(200, __('messages.technician_team_items_returned_successfully'), [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'items_used' => $itemsUsed
        ]);
    }
    


}
