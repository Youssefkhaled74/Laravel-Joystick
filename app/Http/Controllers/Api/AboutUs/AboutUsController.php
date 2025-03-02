<?php

namespace App\Http\Controllers\Api\AboutUs;

use App\Models\AboutUs;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class AboutUsController extends Controller
{
    use ApiResponse;
    public function getSectionOne(): JsonResponse
    {
        $data = AboutUs::select('title', 'image', 'body')->where('key', 'Section1')->first();
        if(!$data){
            return $this->errorResponse(404, 'Section 1 data not found');
        }
        $imagePath = $data->image ? env('APP_URL') . '/public/' . $data->image : null;
        return $this->successResponse(200, 'Section 1 data retrieved successfully',
        ['image' => $imagePath, 'title' => $data->title, 'body' => $data->body]
    );
    }
    public function AddSectionOne(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'image' => 'required|file|mimes:jpg,jpeg,png|max:20480',
            'key'=>'required'
        ]);

        $imagePath = uploadImage($request, 'image', 'images/about-us');
        if (!$imagePath) {
            return $this->errorResponse(400, 'Image upload failed');
        }
        // $image = $imagePath;
        $imageUrl = $imagePath ? env('APP_URL') . '/public/' . $imagePath : null;
        $aboutUs = AboutUs::Where('key', $request->key)->first();
        if (!$aboutUs) {
            $aboutUs = new AboutUs();
        }
        $aboutUs->title = $request->title;
        $aboutUs->body = $request->body;
        $aboutUs->video = 'None';
        $aboutUs->key = $request->key;
        $aboutUs->image = $imagePath;
        $aboutUs->save();
        return $this->successResponse(200, 'Section 1 data added successfully', $aboutUs);
    }
    public function getSectionTwo(): JsonResponse
    {
        $data = AboutUs::select('title', 'body')->where('key', 'Section2')->first();
        if (!$data) {
            return $this->errorResponse(404, 'Section 2 data not found');
        }
        return $this->successResponse(200, 'Section 2 data retrieved successfully', $data);
    }
    public function AddSectionTwo(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'key'=>'required'
        ]);
        $aboutUs = AboutUs::Where('key', $request->key)->first();
        if (!$aboutUs) {
            $aboutUs = new AboutUs();
        }
        $aboutUs->title = $request->title;
        $aboutUs->body = $request->body;
        $aboutUs->key = $request->key;
        $aboutUs->video = 'None';
        $aboutUs->image = 'None';
        $aboutUs->save();
        return $this->successResponse(200, 'Section 2 data added successfully', $aboutUs);
    }
    public function getVideo(): JsonResponse
    {
        $data = AboutUs::select('video')->where('key', 'Section3')->first();
        if (!$data) {
            return $this->errorResponse(404, 'Video not found');
        }
        $videoUrl = $data->video ? env('APP_URL') . '/public/' . $data->video : null;
        return $this->successResponse(200, 'Video data retrieved successfully', ['video' => $videoUrl]);
    }
    public function addVideo(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,mkv,webm|max:20480',
            'key'=>'required'
        ]);
        $videoPath = uploadVideo($request, 'video', 'videos/about-us');
        if (!$videoPath) {
            return $this->errorResponse(400, 'Invalid video format.');
        }
        $aboutUs = AboutUs::find(3);
        if (!$aboutUs) {
            $aboutUs = new AboutUs();
        }
        $aboutUs->title = 'Video';
        $aboutUs->body = 'Video';
        $aboutUs->key = $request->key;
        $aboutUs->video = $videoPath;
        $aboutUs->save();
        return $this->successResponse(201, 'Video uploaded successfully', ['video' => $videoPath]);
    }
}
