<?php

namespace App\Http\Controllers\Api\Admin\Technicion;

use App\Models\Wallet;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\WalletHistory;
use App\Models\RepairRequests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    use ApiResponse;
    public function addMoney(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, 'Unauthorized');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'repair_request_id' => 'required|exists:repair_requests,id',
        ]);

        $repairRequest = RepairRequests::find($request->repair_request_id);
        if (!$repairRequest) {
            return $this->errorResponse(404, 'Repair request not found');
        }

        $teamWallet = $repairRequest->team->wallet;
        if (!$teamWallet) {
            return $this->errorResponse(404, 'Team wallet not found');
        }

        $teamWallet->increment('balance', $request->amount);

        return $this->successResponse(200, 'Money added successfully', $teamWallet);
    }
    public function resetWallet()
    {
        $technician = auth()->guard('technicion')->user();
        if (!$technician) {
            return $this->errorResponse(401, 'Unauthorized');
        }

        $teamWallet = $technician->team->wallet;
        if (!$teamWallet) {
            return $this->errorResponse(404, 'Team wallet not found');
        }

        $teamWallet->update(['balance' => 0]);

        return $this->successResponse(200, 'Wallet reset successfully', $teamWallet);
    }
    public function walletHistory(Request $request)
    {
        $technician = auth()->guard('technicion')->user();
        if (!$technician) {
            return $this->errorResponse(401, 'Unauthorized');
        }

        $request->validate([
            'date' => 'required|date',
        ]);

        $history = Wallet::where('team_id', $technician->team->id)
            ->whereDate('created_at', $request->date)
            ->get();

        return $this->successResponse(200, 'Wallet history retrieved successfully', $history);
    }
    public function walletHistoryTransactions(Request $request)
    {
        $technician = auth()->guard('technicion')->user();
        if (!$technician) {
            return $this->errorResponse(401, 'Unauthorized');
        }
    
        $query = WalletHistory::where('team_id', $technician->team->id)->with('user', 'team');
    
        if ($request->has('date')) {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
            ]);
            if ($validator->fails()) {
                return $this->errorResponse(400, $validator->errors()->first());
            }
            $query->whereDate('created_at', $request->date);
        }
    
        $history = $query->latest()->paginate(10);
    
        return $this->successResponse(200, 'Wallet history retrieved successfully', $history);
    }
    
}