<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tags\CreateTagsRequest;
use App\Http\Requests\Tags\UpdateTagsRequest;
use App\Models\Tags;
use App\Http\Resources\TagsResource;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class TagsController extends Controller
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
        $query = Tags::query();
        $tags = paginate($query, TagsResource::class, $limit, $page);
        return $this->successResponse(200, __('messages.all_tags'), $tags);
    }
    

    public function store(CreateTagsRequest $request)
    {
        $tags = Tags::create($request->validated());
        return $this->successResponse(200, __('messages.tag_created_successfully'), TagsResource::make($tags));
    }

    public function show(string $id)
    {
        $tags = Tags::find($id);
        return $this->successResponse(200, __('messages.tag_info'), TagsResource::make($tags));
    }

    public function update(UpdateTagsRequest $request, string $id)
    {
        $tags = Tags::find($id);
        $name = $request->input('name', []);
        $data = [
            'name' => [
                'ar' => $name['ar'] ?? $tags->getTranslation('name', 'ar'),
                'en' => $name['en'] ?? $tags->getTranslation('name', 'en'),
            ],
        ];
        $tags->update($data);
        return $this->successResponse(200, __('messages.tag_updated_successfully'), TagsResource::make($tags));
    }

    public function destroy(string $id)
    {
        $tags = Tags::find($id);
        if (! $tags) {
            return $this->errorResponse(404, __('messages.tag_not_found'));
        }
        $tags->delete();
        return $this->successResponse(200, __('messages.tag_deleted_successfully'));
    }
}
