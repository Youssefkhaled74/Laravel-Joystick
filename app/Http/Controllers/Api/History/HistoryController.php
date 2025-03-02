<?php
namespace App\Http\Controllers\Api\History;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\RepairRequests;
use App\Http\Resources\HistoryOrderResource;
use App\Http\Resources\HistoryRepairRequestResource;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

class HistoryController extends Controller
{
    use ApiResponse;
    public function getHistory(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, 'Unauthorized');
        }

        $type = $request->query('type'); 

        if ($type === 'store') {
            $orders = Order::where('user_id', $user->id)
                ->with('orderDetails.product')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse(200, 'Store orders retrieved successfully', HistoryOrderResource::collection($orders));
        } elseif ($type === 'repair') {
            $repairRequests = RepairRequests::where('user_id', $user->id)
                ->with('devices.device')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse(200, 'Repair requests retrieved successfully', HistoryRepairRequestResource::collection($repairRequests));
        } else {
            return $this->errorResponse(400, 'Invalid type specified. Use "store" or "repair".');
        }
    }
}
