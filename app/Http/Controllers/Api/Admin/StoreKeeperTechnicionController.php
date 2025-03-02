<?php

namespace App\Http\Controllers\Api\Admin;

use Carbon\Carbon;
use App\Models\Technicion;
use App\Models\StoreKeeper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\AdminResource;
use App\Http\Requests\Admin\LoginRequest;
use Illuminate\Support\Facades\Validator;

class StoreKeeperTechnicionController extends Controller
{
    use ApiResponse;
    public function __construct()
    {
        $this->middleware('checkLoginAdmin');
    }

    public function index(Request $request)
    {
        $limit = $request->query('limit', 10);

        // Fetch storekeepers and technicians separately
        $storekeepers = StoreKeeper::all()->map(function ($item) {
            $item->type = 'StoreKeeper';
            return $item;
        });

        $technicians = Technicion::all()->map(function ($item) {
            $item->type = 'Technicion';
            return $item;
        });

        $models = $storekeepers->merge($technicians);

        $baseUrl = url('/');
        $models->transform(function ($model) use ($baseUrl) {
            $model->profile_picture = $baseUrl . '/' . $model->profile_picture;
            return $model;
        });

        $pagedData = new \Illuminate\Pagination\LengthAwarePaginator(
            $models->forPage($request->query('page', 1), $limit),
            $models->count(),
            $limit,
            $request->query('page', 1),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $this->successResponse(200, __('messages.employees_returned_successfully'), $pagedData);
    }

    

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:storekeeper,technicion',
            'username' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:storekeepers,email|unique:technicions,email',
            'phone' => 'nullable|string|max:255|unique:storekeepers,phone|unique:technicions,phone',
            'password' => 'required|string|min:8',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Determine the model based on type
        $model = $request->type === 'storekeeper' ? new StoreKeeper() : new Technicion();

        $model->username = $request->username;
        $model->email = $request->email;
        $model->phone = $request->phone;
        $model->password = Hash::make($request->password);

        if ($request->hasFile('profile_picture')) {
            $folder = 'images/' . $request->type; // Example: 'images/storekeeper' or 'images/technicion'
            $model->profile_picture = uploadImage($request, 'profile_picture', $folder);
        }

        $model->save();

        return $this->successResponse(201,  __('messages.employee_created_successfully'), $model);
    }

    public function show($id, Request $request)
    {
        $request->validate([
            'type' => 'required|in:storekeeper,technicion',
        ]);

        $model = $request->type === 'storekeeper' ? StoreKeeper::find($id) : Technicion::find($id);

        if (!$model) {
            return $this->errorResponse(404, __('messages.employee_not_found'));
        }

        $baseUrl = url('/');
        $model->profile_picture = $baseUrl . '/' . $model->profile_picture;

        return $this->successResponse(200, __('messages.employee_returned_successfully'), $model);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:storekeeper,technicion',
            'username' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:storekeepers,email,' . $id . '|unique:technicions,email,' . $id,
            'phone' => 'sometimes|string|max:255|unique:storekeepers,phone,' . $id . '|unique:technicions,phone,' . $id,
            'password' => 'sometimes|string|min:6',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $model = $request->type === 'storekeeper' ? StoreKeeper::find($id) : Technicion::find($id);

        if (!$model) {
            return $this->errorResponse(404, __('messages.employee_not_found'));
        }

        // Update fields if provided
        if ($request->has('username')) $model->username = $request->username;
        if ($request->has('email')) $model->email = $request->email;
        if ($request->has('phone')) $model->phone = $request->phone;
        if ($request->has('password')) $model->password = Hash::make($request->password);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $folder = 'images/' . $request->type;
            $model->profile_picture = uploadImage($request, 'profile_picture', $folder);
        }

        $model->save();
        return $this->successResponse(200,  __('messages.employee_updated_successfully'), $model);
    }

    public function destroy(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:storekeeper,technicion',
        ]);

        $model = $request->type === 'storekeeper' ? StoreKeeper::find($id) : Technicion::find($id);

        if (!$model) {
            return $this->errorResponse(404, __('messages.employee_not_found'));
        }

        if ($model->profile_picture) {
            $imagePath = public_path($model->profile_picture);

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $model->delete();

        return $this->successResponse(200, __('messages.employee_deleted_successfully'));
    }


    public function toggleStatus(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:storekeeper,technicion',
        ]);

        $model = $request->type === 'storekeeper' ? StoreKeeper::find($id) : Technicion::find($id);

        if (!$model) {
            return $this->errorResponse(404, __('messages.employee_not_found'));
        }
        $model->status = ($model->status === 'active') ? 'inactive' : 'active';
        $model->save();

        return $this->successResponse(200,  __('messages.employee_updated_successfully'), $model);
    }

}
