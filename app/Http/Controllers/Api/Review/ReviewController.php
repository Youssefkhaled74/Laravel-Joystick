<?php

namespace App\Http\Controllers\Api\Review;

use App\Models\Admin;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    use ApiResponse;
    public function __construct()
    {
        $this->middleware('checkLoginAdmin')->except(['index', 'store']);
    }
    public function store(Request $request)
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $review = Review::create([
            'admin_id' => $admin->id,
            'content' => $request->content,
        ]);

        return $this->successResponse(200, __('messages.review_created'), $review);
    }
    public function index(Request $request)
    {
        $limit = $request->limit ?? 10;
        $reviews = paginateWithoutResource(Review::query(), $limit);
        return $this->successResponse(200, 'All reviews', $reviews);
    }
    public function destroy(Review $review)
    {
        $review->delete();
        return $this->successResponse(200, 'Review deleted successfully');
    }
}
