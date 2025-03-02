<?php

namespace App\Http\Controllers\Api\Admin\Location;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Location;

class LocationController extends Controller
{
    use ApiResponse;
    public function __construct()
    {
        $this->middleware('checkLoginAdmin');
    }
    public function index(Request $request)
    {
        $limit = $request->query('limit', 10);

        $locations = Location::paginate($limit);

        $locations->getCollection()->transform(function ($location) {
            $location->coordinates = json_decode($location->coordinates);
            return $location;
        });

        return $this->successResponse(200, __('messages.all_locations'), $locations);
    }
 
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'team_id' => 'required|exists:teams,id',
            'coordinates' => 'required|array',
            'coordinates.*.lat' => 'required|numeric',
            'coordinates.*.long' => 'required|numeric',
        ]);

        $location = Location::create([
            'name' => $validated['name'],
            'team_id' => $validated['team_id'],
            'coordinates' => json_encode($validated['coordinates']), // Store as JSON
        ]);
        return $this->successResponse(201,  __('messages.Location_created_successfully'), $location);
    }

    public function show($id)
    {
        $location = Location::find($id);

        if (!$location) {
            return $this->errorResponse(404, __('messages.location_not_found'));
        }

        $location->coordinates = json_decode($location->coordinates);

        return $this->successResponse(200, __('messages.location_returned_successfully'), $location);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'team_id' => 'sometimes|exists:teams,id',
            'coordinates' => 'sometimes|array',
            'coordinates.*.lat' => 'required_with:coordinates|numeric',
            'coordinates.*.long' => 'required_with:coordinates|numeric',
        ]);

        $location = Location::find($id);

        if (!$location) {
            return $this->errorResponse(404, __('messages.location_not_found'));
        }

        if ($request->has('name')) $location->name = $validated['name'];
        if ($request->has('team_id')) $location->team_id = $validated['team_id'];
        if ($request->has('coordinates')) $location->coordinates = json_encode($validated['coordinates']);

        $location->save();

        return $this->successResponse(200, __('messages.location_updated_successfully'), $location);
    }

    public function destroy($id)
    {
        $location = Location::find($id);

        if (!$location) {
            return $this->errorResponse(404, __('messages.location_not_found'));
        }

        $location->delete();

        return $this->successResponse(200, __('messages.location_deleted_successfully'));
    }

    public function toggleStatus($id)
    {
        $location = Location::find($id);

        if (!$location) {
            return $this->errorResponse(404, __('messages.location_not_found'));
        }

        $location->status = ($location->status === 'active') ? 'inactive' : 'active';
        $location->save();

        return $this->successResponse(200, __('messages.status_updated_successfully'), $location);
    }
}
