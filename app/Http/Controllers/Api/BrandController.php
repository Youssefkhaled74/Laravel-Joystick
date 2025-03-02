<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\CreateBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class BrandController extends Controller
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
        $query = Brand::query();
        $brands = paginate($query, BrandResource::class, $limit, $page);
        return $this->successResponse(200, __('messages.all_brands'), $brands);
    }
    

    public function store(CreateBrandRequest $request)
    {
        $brand = Brand::create($request->validated());
        return $this->successResponse(200, __('messages.brand_created_successfully'), BrandResource::make($brand));
    }

    public function show(string $id)
    {
        $brand = Brand::find($id);
        return $this->successResponse(200, __('messages.brand_info'), BrandResource::make($brand));
    }

    public function update(UpdateBrandRequest $request, string $id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return $this->errorResponse(404, __('messages.brand_not_found'));
        }
        $name = $request->input('name', []);
        $data = [
            'name' => [
                'ar' => $name['ar'] ?? $brand->getTranslation('name', 'ar'),
                'en' => $name['en'] ?? $brand->getTranslation('name', 'en'),
            ],
        ];
        $brand->update($data);
        return $this->successResponse(200, __('messages.brand_updated_successfully'), BrandResource::make($brand));
    }

    public function destroy(string $id)
    {
        $brand = Brand::find($id);
        if(! $brand){
            return $this->errorResponse(404,__('messages.brand_not_found'));
        }
        $brand->delete();
        return $this->successResponse(200, __('messages.brand_deleted_successfully'));
    }
}
