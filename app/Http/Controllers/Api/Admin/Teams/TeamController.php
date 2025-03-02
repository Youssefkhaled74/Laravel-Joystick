<?php

namespace App\Http\Controllers\Api\Admin\Teams;

use App\Models\Team;
use App\Models\Technicion;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TeamController extends Controller
{
    use ApiResponse;
    public function __construct()
    {
        $this->middleware('checkLoginAdmin');
    }

    public function index()
    {
        $teams = Team::with(['technicions', 'location', 'technicianMaintenanceStores.maintenanceStore'])
            ->get()
            ->map(function ($team) {
                $itemsUsed = [];

                // Loop through the technician_maintenance_store records related to the team
                foreach ($team->technicianMaintenanceStores as $store) {
                    $itemName = $store->maintenanceStore->name; 
                    $quantity = $store->quantity;

                    // If the item already exists in the array, add the quantity to it
                    if (isset($itemsUsed[$itemName])) {
                        $itemsUsed[$itemName] += $quantity;
                    } else {
                        $itemsUsed[$itemName] = $quantity;
                    }
                }

                // Return the team data along with the items used and their quantities
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'employees_count' => $team->technicions->count(),
                    'location' => optional($team->location)->name ?? 'No Location Assigned',
                    'created_at' => $team->created_at,
                    'items_used' => $itemsUsed, // Include the items used by the team
                ];
            });

        return $this->successResponse(200, 'teams_returned_successfully', $teams);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'technicion_ids' => 'required|array',
            'technicion_ids.*' => 'exists:technicions,id',
        ]);
    
        $team = Team::create([
            'name' => $request->name,
        ]);
    
        foreach ($request->technicion_ids as $technicion_id) {
            $technicion = Technicion::find($technicion_id);
            if ($technicion->team) {
                return $this->errorResponse(400, 'Technician_with_Name ' . $technicion->username . ' already_has_a_team');
            }
            $technicion->team()->associate($team);
            $technicion->save();
        }
    
        $team->load('technicions');
    
        return $this->successResponse(200, 'Team_created_successfully_and_technicians_assigned', $team);
    }

    public function show($id)
    {
        $team = Team::findOrFail($id);
        if (!$team) {
            return $this->errorResponse(404, __('messages.team_not_found'));
        }
        return $this->successResponse(200,'team_returned_successfully',$team);
    }

    public function update(Request $request, $id)
    {
        $team = Team::findOrFail($id);
    
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'add_technicion_ids' => 'sometimes|array',
            'add_technicion_ids.*' => 'exists:technicions,id',
            'remove_technicion_ids' => 'sometimes|array',
            'remove_technicion_ids.*' => 'exists:technicions,id',
        ]);
    
        if ($request->has('name')) {
            $team->name = $request->name;
        }
    
        $team->save();
    
        if ($request->has('add_technicion_ids')) {
            foreach ($request->add_technicion_ids as $technicion_id) {
                $technicion = Technicion::find($technicion_id);
                if ($technicion->team && $technicion->team->id !== $team->id) {
                    return $this->errorResponse(400, 'Technician_with_Name ' . $technicion->username . ' already_belongs_to_another_team');
                }
                $technicion->team()->associate($team);
                $technicion->save();
            }
        }
    
        if ($request->has('remove_technicion_ids')) {
            foreach ($request->remove_technicion_ids as $technicion_id) {
                $technicion = Technicion::find($technicion_id);
                if ($technicion->team && $technicion->team->id === $team->id) {
                    $technicion->team()->dissociate();
                    $technicion->save();
                }
            }
        }

        $team->load('technicions');
        return $this->successResponse(200, 'Team_updated_successfully', $team);
    }


    public function destroy($id)
    {
        $team = Team::findOrFail($id);
    
        if ($team->technicions()->count() > 0) {
            return $this->errorResponse(400, 'You_should_remove_all_technicians_before_deleting_the_team');
        }
    
        $team->delete();
        
        return $this->successResponse(200, 'Team_deleted_successfully');
    }

    public function toggleStatus($id)
    {
        $team = Team::find($id);
    
        if (!$team) {
            return $this->errorResponse(404, __('messages.team_not_found'));
        }
    
        $team->status = ($team->status === 'active') ? 'inactive' : 'active';
        $team->save();
    
        return $this->successResponse(200, __('messages.status_updated_successfully'));
    }
}
