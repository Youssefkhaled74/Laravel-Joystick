<?php

namespace App\Http\Controllers\Api\HomePage;

use App\Models\Banner;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\ProductResource;

class HomePageController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $perPage_product = $request->input('per_page_product', 10); 
        $page_product = $request->input('page_product', 1); 
        $products = Product::paginate($perPage_product, ['*'], 'page_product', $page_product);

        $perPage_banner = $request->input('per_page_banner', 10); 
        $page_banner = $request->input('page_banner', 1); 

        $banners = Banner::paginate($perPage_banner, ['*'], 'page_banner', $page_banner);
        
        if (!$products || !$banners) {
            return $this->errorResponse(404, 'No data found');
        }
        
        return $this->successResponse(200, 'Home page data retrieved successfully', [
            'products' => ProductResource::collection($products),
            'banners' => BannerResource::collection($banners)
        ]);

    }

}
