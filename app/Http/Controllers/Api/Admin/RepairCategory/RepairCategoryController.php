<?php

namespace App\Http\Controllers\Api\Admin\RepairCategory;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\RepairCategoryResource;
use App\Http\Requests\RepairCategory\CreateRepairCategoryRequest;
use App\Http\Requests\RepairCategory\UpdateRepairCategoryRequest;
use App\Models\RepairCategory;

class RepairCategoryController extends Controller
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
        $query = RepairCategory::where('status', 'active');
        $category = paginate($query,RepairCategoryResource::class, $limit, $page);
        return $this->successResponse(200, __('messages.all_categories'), $category);
    }

    public function store(CreateRepairCategoryRequest $request)
    {
        $data = $request->all();
        $data['tags'] = implode(',', $data['tags']);
        $data['image'] = uploadImage($request, 'image', 'images/RepairCategory');
        $category = RepairCategory::create($data);
        return $this->successResponse(201,__('messages.category_created_successfully'),RepairCategoryResource::make($category));
    }

    public function show(string $id)
    {
        $category = RepairCategory::find($id);
        if(! $category){
            return $this->errorResponse(404,'Category Not Found');
        }
        return $this->successResponse(200,__('messages.category_info'),RepairCategoryResource::make($category));
    }

    public function update(UpdateRepairCategoryRequest $request, string $id)
    {
        $category = RepairCategory::find($id);
        if(! $category){
            return $this->errorResponse(404,__('messages.category_not_found'));
        }
        $data = $request->all();
        if(isset($data['tags'])){
            $data['tags'] = implode(',', $data['tags']) ;
        }
        if ($request->hasFile('image')) {
            deleteImage($category->image);
            $data['image'] = uploadImage($request, 'image', 'images/RepairCategory');
        }
        $category->update($data);
        return $this->successResponse(200,__('messages.category_updated_successfully'),RepairCategoryResource::make($category));
    }

    public function destroy(string $id)
    {
        $category = RepairCategory::find($id);
        
        if (!$category) {
            return $this->errorResponse(404, __('messages.category_not_found'));
        }

        // Check if the category has any associated products
        // if ($category->products()->exists()) {
        //     return $this->errorResponse(400, __('messages.category_has_products'));
        // }

        deleteImage($category->image);
        $category->delete();

        return $this->successResponse(200, __('messages.category_deleted_successfully'));
    }
    public function toggleStatus($id)
    {
        $category = RepairCategory::find($id);
    
        if (!$category) {
            return $this->errorResponse(404, __('messages.category_not_found'));
        }
    
        $category->status = ($category->status === 'active') ? 'inactive' : 'active';
        $category->save();
    
        return $this->successResponse(200, __('messages.category_status_updated'));
    }

}
