<?php

namespace App\Http\Controllers\Api\Admin\MaintenanceStore;

use App\Models\MaintenanceStore;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\MaintenanceStoreResource;
use App\Http\Requests\MaintenanceStore\CreateMaintenanceProductRequest;
use App\Http\Requests\MaintenanceStore\UpdateMaintenanceProductRequest;
use App\Traits\ApiResponse;

class MaintenanceStoreController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('checkLoginAdmin')->except('index', 'show', 'filter');
    }

    public function index(Request $request)
    {
        $limit = $request->query('limit', 10);

        // Ensure pagination is applied to the query before retrieving results
        $query = MaintenanceStore::query();

        $stores = $query->paginate($limit);

        return $this->successResponse(200, __('messages.all_maintenance_products'), MaintenanceStoreResource::collection($stores));
    }



    public function store(CreateMaintenanceProductRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = uploadImage($request, 'image', 'images/maintenance_stores');
        }

        if ($request->has('tags')) {
            $data['tags'] = json_encode($request->tags);
        }

        $store = MaintenanceStore::create($data);

        return $this->successResponse(201, __('messages.maintenance_product_created_successfully'), MaintenanceStoreResource::make($store));
    }

    public function show(string $id)
    {
        $store = MaintenanceStore::find($id);

        if (!$store) {
            return $this->errorResponse(404, __('messages.maintenance_product_not_found'));
        }

        return $this->successResponse(200, __('messages.maintenance_product_info'), MaintenanceStoreResource::make($store));
    }

    public function update(UpdateMaintenanceProductRequest $request, string $id)
    {
        $store = MaintenanceStore::findOrFail($id);
        $data = $request->validated();

        if ($request->hasFile('image')) {
            deleteImage($store->image);
            $data['image'] = uploadImage($request, 'image', 'images/maintenance_stores');
        }

        // Convert tags to JSON
        if ($request->has('tags')) {
            $data['tags'] = json_encode($request->tags);
        }

        $store->update($data);

        return $this->successResponse(200, __('messages.maintenance_product_updated_successfully'), MaintenanceStoreResource::make($store));
    }

    public function destroy(string $id)
    {
        $store = MaintenanceStore::find($id);

        if (!$store) {
            return $this->errorResponse(404, __('messages.maintenance_product_not_found'));
        }

        deleteImage($store->image);

        $store->delete();
        return $this->successResponse(200, __('messages.maintenance_product_deleted_successfully'));
    }

    public function toggleStatus($id)
    {
        $store = MaintenanceStore::find($id);

        if (!$store) {
            return $this->errorResponse(404, __('messages.maintenance_product_not_found'));
        }

        $store->status = ($store->status === 'active') ? 'inactive' : 'active';
        $store->save();

        return $this->successResponse(200, __('messages.status_updated_successfully'));
    }

}
