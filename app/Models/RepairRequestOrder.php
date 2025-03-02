<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RepairRequestOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_request_id',
        'technicion_id',
        'item',
        'quantity',
        'notes',
        'status'
    ];

    public function items()
    {
        return $this->hasmany(RepairRequestOrderItem::class);
    }

    public function repairRequest()
    {
        return $this->belongsTo(RepairRequests::class);
    }

    public function technicion()
    {
        return $this->belongsTo(Technicion::class);
    }
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d M Y, h:i A'); 
    }
    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d M Y, h:i A'); 
    }
}
