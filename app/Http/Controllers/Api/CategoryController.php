<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CreateCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Helper;

class CategoryController extends Controller
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
        $query = Category::where('status', 'active');
        $category = paginate($query,CategoryResource::class, $limit, $page);
        return $this->successResponse(200, __('messages.all_categories'), $category);
    }
    public function indexAdminCategory(Request $request)
    {
        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
    
        // Get all products (both active & inactive)
        $query = Category::query();
    
        // Use the same custom paginate function as in index
        $category = paginate($query, CategoryResource::class, $limit, $page);
    
        return $this->successResponse(200, __('messages.all_categories'), $category);
    }

    public function store(CreateCategoryRequest $request)
    {
        $data = $request->all();
        $data['tags'] = implode(',', $data['tags']);
        $data['image'] = uploadImage($request, 'image', 'images/category');
        $category = Category::create($data);
        return $this->successResponse(201,__('messages.category_created_successfully'),CategoryResource::make($category));
    }

    public function show(string $id)
    {
        $category = Category::find($id);
        if(! $category){
            return $this->errorResponse(404,'Category Not Found');
        }
        return $this->successResponse(200,__('messages.category_info'),CategoryResource::make($category));
    }

    public function update(UpdateCategoryRequest $request, string $id)
    {
        $category = Category::find($id);
        if(! $category){
            return $this->errorResponse(404,__('messages.category_not_found'));
        }
        $data = $request->all();
        if(isset($data['tags'])){
            $data['tags'] = implode(',', $data['tags']) ;
        }
        if ($request->hasFile('image')) {
            deleteImage($category->image);
            $data['image'] = uploadImage($request, 'image', 'images/category');
        }
        $category->update($data);
        return $this->successResponse(200,__('messages.category_updated_successfully'),CategoryResource::make($category));
    }

    public function destroy(string $id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return $this->errorResponse(404, __('messages.category_not_found'));
        }

        // Check if the category has any associated products
        if ($category->products()->exists()) {
            return $this->errorResponse(400, __('messages.category_has_products'));
        }

        deleteImage($category->image);
        $category->delete();

        return $this->successResponse(200, __('messages.category_deleted_successfully'));
    }
    public function toggleStatus($id)
    {
        $category = Category::find($id);
    
        if (!$category) {
            return $this->errorResponse(404, __('messages.category_not_found'));
        }
    
        $category->status = ($category->status === 'active') ? 'inactive' : 'active';
        $category->save();
    
        return $this->successResponse(200, __('messages.category_status_updated'));
    }

}
