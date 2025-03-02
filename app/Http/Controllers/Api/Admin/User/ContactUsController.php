<?php

namespace App\Http\Controllers\Api\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use App\Traits\ApiResponse;
use GPBMetadata\Google\Protobuf\Api;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'    => 'required|string|max:255',
                'email'   => 'required|email|max:255',
                'phone' => 'required|digits_between:8,20',
                'message' => 'nullable|string|max:1000',
            ]);
            $contact = ContactUs::create($request->only(['name', 'email', 'phone', 'message']));
            return $this->successResponse(200, __('messages.contact_form_submitted'), $contact);
        } catch (\Exception $e) {
            return $this->errorResponse(500, __('messages.server_error'));
        }
    }

    public function index()
    {
        $limit = request()->get('limit', 10);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 10;
        $messages = ContactUs::latest()->paginate($limit);
        if ($messages->isEmpty()) {
            return $this->successResponse(200, __('messages.no_records_found'), []);
        }        
        return $this->successResponse(200, __('messages.success'), $messages);
    }
}
