<?php

namespace App\Http\Controllers\Api\AvailableTime;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\AvailableTime;
use App\Http\Controllers\Controller;

class AvailableTimeController extends Controller
{
    use ApiResponse;
    public function show($dayId)
    {

        $availableTimes = AvailableTime::where('day_id', $dayId)
            ->where('status', 'available')
            ->get();
        return $this->successResponse(200, 'Available Times', $availableTimes);

    }
}
