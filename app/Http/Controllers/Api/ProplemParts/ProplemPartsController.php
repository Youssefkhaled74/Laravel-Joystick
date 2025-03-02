<?php

namespace App\Http\Controllers\Api\ProplemParts;

use App\Http\Controllers\Controller;
use App\Models\Proplems_Parts;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ProplemPartsController extends Controller
{
    use ApiResponse;
    public function __construct()
    {
        $this->middleware('checkLoginAdmin');
    }
    // Get all problem parts
    public function index()
    {
        $problemParts = Proplems_Parts::all();
        return $this->successResponse(200 , __('messages.problems_parts') , $problemParts); 
    }

    // Add a new problem part
    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|string|unique:problem_parts,number',
        ]);
        $problemPart = Proplems_Parts::create([
            'number' => $request->number,
        ]);
        return $this->successResponse(200 , __('messages.problems_parts_stored_successfully') , $problemPart);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'number' => 'required|string|unique:problem_parts,number,' . $id,
        ]);
        $problemPart = Proplems_Parts::findOrFail($id);
        $problemPart->update([
            'number' => $request->number,
        ]);
        return $this->successResponse(200 , __('messages.problems_parts_updated_successfully') , $problemPart);
    }
    // Delete a problem part
    public function destroy($id)
    {
        $problemPart = Proplems_Parts::findOrFail($id);
        $problemPart->delete();
        return $this->successResponse(204 , __('message.problem_parts_deleted_successfully'));
    }
}
