<?php

namespace App\Http\Controllers\Api\Admin\User;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\Area\CreateAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Models\User;

class UserDashboardController extends Controller
{
    use ApiResponse;
    public function __construct()
    {
        $this->middleware('checkLoginAdmin');
    }
    public function index(Request $request)
    {
        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
        $query = User::query();
        $users = paginate($query, UserResource::class, $limit, $page);
        return $this->successResponse(200, __('messages.all_users'), $users);
    }


    public function show(string $id)
    {
        $user = User::find($id);
        if (! $user) {
            return $this->errorResponse(404, __('messages.user_not_found'));
        }
        return $this->successResponse(200, __('messages.user_info'), UserResource::make($user));
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
        $user = User::find($id);
        if (! $user) {
            return $this->errorResponse(404, __('messages.user_not_found'));
        }
        $user->delete();
        return $this->successResponse(200, __('messages.user_deleted_successfally'));
    }
    public function toggleStatus(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse(404, __('messages.user_not_found'));
        }

        $user->status = ($user->status === 'active') ? 'inactive' : 'active';
        $user->save();

        return $this->successResponse(200, __('messages.user_status_updated'), [
            'user' => $user
        ]);
    }

}
