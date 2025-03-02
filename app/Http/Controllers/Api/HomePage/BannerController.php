<?php

namespace App\Http\Controllers\Api\HomePage;

use App\Models\Banner;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Banner\StoreBannerRequest;
use App\Http\Resources\BannerResource;

class BannerController extends Controller
{
    //store banner 
    use ApiResponse;

    public function store(StoreBannerRequest $request){
        $data = $request->all();
        $data['banner'] = uploadImage($request, 'banner', 'images/Banners');
        $banner = Banner::create($data);
        return $this->successResponse(201, __('messages.product_created_successfully'), BannerResource::make($banner));
    }
}
