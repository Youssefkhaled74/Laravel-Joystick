<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Requests\Product\CreateProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Traits\ApiResponse;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('checkLoginAdmin')->except('index', 'show', 'filter');
    }

    public function index(Request $request)
    {
        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
    
        // Filter products where status is 'active'
        $query = Product::where('status', 'active');
    
        $product = paginate($query, ProductResource::class, $limit, $page);
    
        return $this->successResponse(200, __('messages.all_products'), $product);
    }

    public function indexAdmin(Request $request)
    {
        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
    
        // Get all products (both active & inactive)
        $query = Product::query();
    
        // Use the same custom paginate function as in index
        $product = paginate($query, ProductResource::class, $limit, $page);
    
        return $this->successResponse(200, __('messages.all_products'), $product);
    }
    
    
    

    public function store(CreateProductRequest $request)
    {
        $data = $request->all();
        $data['main_image'] = uploadImage($request, 'main_image', 'images/product');
        $data['images'] = uploadImages($request, 'images', 'images/product');
        
        $data['colors'] = json_encode($data['colors']); 
        
        $data['tags'] = json_encode($data['tags']);  
    
        $product = Product::create($data);
    
        if ($request->has('colors')) {
            foreach ($request->colors as $colorData) {
                $product->colors()->create([
                    'color' => $colorData['color'],
                    'quantity' => $colorData['quantity'],
                ]);
            }
        }
        
        return $this->successResponse(201, __('messages.product_created_successfully'), ProductResource::make($product));
    }

    public function show(string $id)
    {
        $product = Product::find($id);
        if (! $product) {
            return $this->errorResponse(404, __('messages.product_not_found'));
        }
        $relatedProducts = Product::where('category_id', $product->category_id)
        ->where('id', '!=', $product->id)
        ->inRandomOrder()
        ->take(5)
        ->get();
        $product->related_products = ProductResource::collection($relatedProducts);
        if (! $product) {
            return $this->errorResponse(404, __('messages.product_not_found'));
        }
        return $this->successResponse(200, __('messages.product_info'), ProductResource::make($product));
    }

    public function update(UpdateProductRequest $request, string $id)
    {
        $product = Product::findOrFail($id);
        $data = $request->all();
    
        if ($request->hasFile('main_image')) {
            deleteImage($product->main_image);
            $data['main_image'] = uploadImage($request, 'main_image', 'images/product');
        }
    
        if ($request->hasFile('images')) {
            $oldImages = $product->images ? explode(',', $product->images) : [];
            $data['images'] = updateImages($request, 'images', 'images/product', $oldImages);
        }
    
        if ($request->has('colors') && is_array($request->colors)) {
            $colors = $request->input('colors');
            $totalColorQuantity = collect($colors)->sum('quantity');
    
            if ($totalColorQuantity !== (int) $request->input('quantity')) {
                return $this->errorResponse(422, __('messages.validation_error'), __('messages.total_color_quantity_mismatch'));
            }
    
            $product->colors()->delete();
            foreach ($colors as $colorData) {
                $product->colors()->create([
                    'color' => $colorData['color'],
                    'quantity' => $colorData['quantity'],
                ]);
            }
            
            $data['colors'] = json_encode($colors);
        }
    
        // Convert tags to JSON
        if ($request->has('tags')) {
            $data['tags'] = json_encode($request->tags);
        }
    
        // Update product
        $product->update($data);
    
        return $this->successResponse(200, __('messages.product_updated_successfully'), ProductResource::make($product));
    }
    
    public function destroy(string $id)
    {
        $product = Product::find($id);
        if (! $product) {
            return $this->errorResponse(404, __('messages.product_not_found'));
        }
        deleteImage($product->main_image);
        deleteImages($product->images);
        // Delete related colors
        $product->colors()->delete();

        $product->delete();
        return $this->successResponse(200, __('messages.product_deleted_successfully'));
    }

    public function filter(Request $request)
    {
        $products = Product::query();
    
        if ($request->filled('name')) {
            $products->where('name', 'like', '%' . $request->name . '%');
        }
    
        if ($request->filled('price_from') && $request->filled('price_to')) {
            $priceFrom = (float) $request->price_from;
            $priceTo = (float) $request->price_to;
            $products->whereRaw("CAST(price AS DECIMAL(10,2)) BETWEEN ? AND ?", [$priceFrom, $priceTo]);
        }
        
    
        if ($request->filled('brand_id')) {
            $brandIds = explode(',', $request->brand_id);
            $products->whereIn('brand_id', $brandIds);
        }
    
        if ($request->filled('category_id')) {
            $categoryIds = explode(',', $request->category_id);
            $products->whereIn('category_id', $categoryIds);
        }
    
        if ($request->filled('tags')) {
            $tags = explode(',', $request->tags);
            $products->where(function ($query) use ($tags) {
                foreach ($tags as $tag) {
                    $query->orWhere('tags', 'LIKE', '%"' . $tag . '"%')
                          ->orWhere('tags', 'LIKE', '%,' . $tag . '%')
                          ->orWhere('tags', 'LIKE', '%[' . $tag . '%');
                }
            });
        }
        
        
    
        // Paginate results
        $products = $products->paginate(10);
        return $this->successResponse(200, __('messages.products_filtered_successfully'), ProductResource::collection($products));
    }
    

    public function toggleStatus($id)
    {
        $product = Product::find($id);
    
        if (!$product) {
            return $this->errorResponse(404, __('messages.product_not_found'));
        }
    
        $product->status = ($product->status === 'active') ? 'inactive' : 'active';
        $product->save();
    
        return $this->successResponse(200, __('messages.product_status_updated'));
    }
    
    public function randomProducts()
    {
        $products = Product::inRandomOrder()->limit(6)->get();
        return $this->successResponse(200, __('messages.random_products'), ProductResource::collection($products));
    }    

    

}
