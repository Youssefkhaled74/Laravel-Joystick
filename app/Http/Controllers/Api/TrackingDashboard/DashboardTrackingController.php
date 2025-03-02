<?php

namespace App\Http\Controllers\Api\TrackingDashboard;


use App\Http\Controllers\Controller;
use App\Models\Tracking;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Kreait\Firebase\Database;
use App\Service\FirebaseService;

class DashboardTrackingController extends Controller
{
    use ApiResponse;

    protected $database;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->database = $firebaseService->getDatabase();
    }
    // 1️⃣ بدء التتبع لأول مرة عند قبول الطلب
    public function startTracking(Request $request)
    {
        $technician = auth()->guard('technicion')->user();

        $request->validate([
            // 'team_id' => 'required|exists:teams,id',
            'repair_request_id' => 'required|exists:repair_requests,id',
            'team_latitude' => 'required|numeric',
            'team_longitude' => 'required|numeric',
        ]);

        $tracking = Tracking::create([
            'team_id' => $technician->team_id,
            'repair_request_id' => $request->repair_request_id,
            'team_latitude' => $request->team_latitude,
            'team_longitude' => $request->team_longitude,
            'status' => 'started',
        ]);

        // إرسال البيانات إلى Firebase
        $this->database->getReference('tracking/' . $tracking->team_id)
            ->set([
                'repair_request_id' => $tracking->repair_request_id,
                'latitude' => $tracking->team_latitude,
                'longitude' => $tracking->team_longitude,
                'status' => 'started'
            ]);
        return $this->successResponse(200, __('messages.Tracking_started'), $tracking);
    }

    // 2️⃣ تحديث موقع التكنيشن كل 10 ثواني
    public function updateLocation(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $tracking = Tracking::where('team_id', $request->team_id)
            ->where('status', 'started')
            ->first();

        if (!$tracking) {
            return $this->errorResponse(404, __('messages.Tracking_not_found'));
        }

        // تحديث الموقع في MySQL
        $tracking->update([
            'team_latitude' => $request->latitude,
            'team_longitude' => $request->longitude,
        ]);

        // تحديث البيانات في Firebase
        $this->database->getReference('tracking/' . $tracking->team_id)
            ->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
        return $this->successResponse(200, __('messages.location_updated_successfully'), $tracking);
    }

    // 3️⃣ إنهاء التتبع عند إتمام الطلب
    public function stopTracking(Request $request)
    {
        $technician = auth()->guard('technicion')->user();
        // $request->validate([
        //     'team_id' => 'required|exists:teams,id',
        // ]);

        $tracking = Tracking::where('team_id', $technician->team_id)
            ->where('status', 'started')
            ->first();

        if (!$tracking) {
            return $this->errorResponse(404, __('messages.Tracking_not_found'));
        }

        $tracking->update(['status' => 'completed']);

        // حذف البيانات من Firebase
        $this->database->getReference('tracking/' . $tracking->team_id)->remove();

        return $this->successResponse(200, __('messages.Tracking_stopped'));
    }
}
