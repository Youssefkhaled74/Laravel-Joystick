<?php

namespace App\Http\Controllers\Api\Days;

use App\Models\Days;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\AvailableTime;
use App\Http\Controllers\Controller;

class DayController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {

        $request->validate([
            'date' => 'required|date_format:Y-m-d|after_or_equal:today|unique:days,date',
            'times' => 'required|array',
            'times.*' => 'date_format:H:i',
        ]);

        $day = Days::create([
            'date' => $request->date,
        ]);

        foreach ($request->times as $time) {
            AvailableTime::create([
                'day_id' => $day->id,
                'time' => $time,
                'status' => 'available',
            ]);
        }

        return $this->successResponse(200, 'Day Add Succesfuly', $day);
    }
    public function index()
    {
        $day = Days::all();
        $days = $day->map(function ($day) {
            $day->availableTimes = AvailableTime::where('day_id', $day->id)->get();
            return $day;
        });
        return $this->successResponse(200, 'All days', $days);
    }
    public function show()
    {
        $availableDays = Days::get();
        return $this->successResponse(200, 'Available Days', $availableDays);

    }
}
