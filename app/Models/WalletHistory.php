<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'amount',
        'isCollected',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d M Y, h:i A'); 
    }
        
    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d M Y, h:i A'); 
    }
    public function getCollectedRevenue()
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $collectedOrderRevenue = WalletHistory::where('isCollected', true)
            ->whereHas('team', function ($query) {
                $query->whereHas('repairRequests', function ($query) {
                    $query->where('invoiceable_type', Order::class);
                });
            })
            ->sum('amount');

        $collectedRepairRequestRevenue = WalletHistory::where('isCollected', true)
            ->whereHas('team', function ($query) {
                $query->whereHas('repairRequests', function ($query) {
                    $query->where('invoiceable_type', RepairRequests::class);
                });
            })
            ->sum('amount');

        return $this->successResponse(200, __('messages.collected_revenue_retrieved'), [
            'collected_order_revenue' => $collectedOrderRevenue,
            'collected_repair_request_revenue' => $collectedRepairRequestRevenue,
        ]);
    }

    
}
