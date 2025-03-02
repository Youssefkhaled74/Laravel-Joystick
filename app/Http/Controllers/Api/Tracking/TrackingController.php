<?php

namespace App\Http\Controllers\Api\Tracking;

use App\Models\Order;
use App\Models\Address;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\RepairRequests;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use Google\Api\ResourceDescriptor\History;
use App\Http\Resources\HistoryRepairRequestResource;
use App\Http\Resources\HistoryRepairRequestResourceRepairRequestResource;

class TrackingController extends Controller
{
    use ApiResponse;
    public function track(Request $request, $id)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, 'Unauthorized');
        }

        $type = $request->query('type');

        if ($type === 'order') {

            $order = Order::where('user_id', $user->id)
                ->where('id', $id)
                ->with(['orderDetails.product', 'address'])
                ->first();

            if (!$order) {
                return $this->errorResponse(404, 'Order not found');
            }

            $address = $order->address;

            if (!$address) {
                return $this->errorResponse(404, 'Address not found');
            }

            $distance = $this->calculateDistance(
                $order->latitude,
                $order->longitude,
                $address->latitude,
                $address->longitude
            );


            $maxDistance = 200; 
            $percentage = max(0, min(100, 100 - ($distance / $maxDistance * 100)));
            
            return $this->successResponse(200, 'Order tracked successfully', [
                'order' => new OrderResource($order),
                'tracking' => [
                    'distance_km' => $distance,
                    'percentage' => $percentage. ' % ',
                    'current_location' => [
                        'latitude' => $order->latitude,
                        'longitude' => $order->longitude,
                    ],
                    'delivery_address' => [
                        'latitude' => $address->latitude,
                        'longitude' => $address->longitude,
                    ],
                ],
                'payment_time' => now()->toDateTimeString(),
                'total_price' => $order->total,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'products' => $order->orderDetails->map(function ($detail) {
                    $detail->product->map(function ($product) {
                        return [
                            'product_name' => $product->name,
                            'quantity' => $product->quantity,
                            'price' => $product->price,
                        ];
                    });
                }),
            ]);
            
        } elseif ($type === 'repair') {

            $repairRequest = RepairRequests::where('user_id', $user->id)
                ->where('id', $id)
                ->with('devices.device')
                ->first();

            if (!$repairRequest) {
                return $this->errorResponse(404, 'Repair request not found');
            }

            return $this->successResponse(200, 'Repair request tracked successfully', [
                'repair_request' => new HistoryRepairRequestResource($repairRequest),
                'devices' => $repairRequest->devices->map(function ($device) {
                    return [
                        'device_name' => $device->device->name,
                        'problem_parts' => json_decode($device->problem_parts),
                        'notes' => $device->notes,
                    ];
                }),
            ]);
        } else {
            return $this->errorResponse(400, 'Invalid type specified. Use "order" or "repair".');
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }
}
