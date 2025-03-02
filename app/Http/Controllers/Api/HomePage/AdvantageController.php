<?php

namespace App\Http\Controllers\Api\HomePage;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Advantage;
use App\Http\Requests\Advantage\CreateAdvantageRequest;
use App\Http\Requests\Advantage\UpdateAdvantageRequest;
use App\Http\Resources\AdvantageResource;
class AdvantageController extends Controller
{
    //store banner 
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('checkLoginAdmin')->except(['getAllAdvantages']);
    }

    public function getAllAdvantages(Request $request)
    {
        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
        $query = Advantage::query();
        $advantages = paginate($query, AdvantageResource::class, $limit, $page);
        return $this->successResponse(200, __('messages.all_advantages'), $advantages);
    }

    public function storeAdvantages(CreateAdvantageRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('img')) {
            $data['img'] = uploadImage($request, 'img', 'advantages');
        }
        $advantage = Advantage::create($data);
        return $this->successResponse(200, __('messages.advantage_created_successfully'), AdvantageResource::make($advantage));
    }

    public function showAdvantages(string $id)
    {
        $advantage = Advantage::find($id);
        if (!$advantage) {
            return $this->errorResponse(404, __('messages.advantage_not_found'));
        }
        return $this->successResponse(200, __('messages.advantage_info'), AdvantageResource::make($advantage));
    }

    public function updateAdvantages(Request $request, string $id)
    {
        $advantage = Advantage::find($id);
        if (!$advantage) {
            return $this->errorResponse(404, __('messages.advantage_not_found'));
        }

        $data = $request->validated();

        if ($request->hasFile('img')) {
            $data['img'] = $this->uploadImage($request, 'img', 'advantages');
        }

        $advantage->update($data);
        return $this->successResponse(200, __('messages.advantage_updated_successfully'), AdvantageResource::make($advantage));
    }

    public function destroyAdvantages(string $id)
    {
        $advantage = Advantage::find($id);
        if (!$advantage) {
            return $this->errorResponse(404, __('messages.advantage_not_found'));
        }
        $advantage->delete();
        return $this->successResponse(200, __('messages.advantage_deleted_successfully'));
    }

}
