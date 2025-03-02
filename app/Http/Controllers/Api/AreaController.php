<?php

namespace App\Http\Controllers\Api;

use App\Models\Area;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\AreaResource;
use App\Http\Requests\Area\CreateAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;

class AreaController extends Controller
{
    use ApiResponse;
    public function __construct()
    {
        $this->middleware('checkLoginAdmin')->except(['index', 'show']);
    }
    public function index(Request $request)
    {
        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
        $query = Area::query();
        $areas = paginate($query, AreaResource::class, $limit, $page);
        return $this->successResponse(200, __('messages.all_areas'), $areas);
    }

    public function store(CreateAreaRequest $request)
    {
        $data = $request->all();
        $area = Area::create($data);
        return $this->successResponse(201, __('messages.area_created_successfally'), AreaResource::make($area));
    }

    public function show(string $id)
    {
        $area = Area::find($id);
        if (! $area) {
            return $this->errorResponse(404, __('messages.area_not_found'));
        }
        return $this->successResponse(200, __('messages.area_info'), AreaResource::make($area));
    }

    public function update(UpdateAreaRequest $request, string $id)
    {
        $area = Area::find($id);
        $data = $request->all();
        $area->update($data);
        return $this->successResponse(200, __('messages.area_updated_successfally'), AreaResource::make($area));
    }

    public function destroy(string $id)
    {
        $area = Area::find($id);
        if (! $area) {
            return $this->errorResponse(404, __('messages.area_not_found'));
        }
        $area->delete();
        return $this->successResponse(200, __('messages.area_deleted_successfally'));
    }
}
