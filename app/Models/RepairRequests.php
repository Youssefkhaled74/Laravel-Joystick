<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RepairRequests extends Model
{
    protected $table = 'repair_requests';

    protected $fillable = ['user_id', 'address_id', 'day_id', 'available_time_id', 'code', 'status','type','team_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function day()
    {
        return $this->belongsTo(Days::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function availableTime()
    {
        return $this->belongsTo(AvailableTime::class);
    }
    public function orders()
    {
        return $this->hasMany(RepairRequestOrder::class, 'repair_request_id');
    }

    public function orderItems()
    {
        return $this->hasManyThrough(
            RepairRequestOrderItem::class,
            RepairRequestOrder::class,
            'repair_request_id', 
            'repair_request_order_id', 
            'id',  
            'id'   
        );
    }

    public function devices()
    {
        return $this->hasMany(RepairRequestDevices::class, 'repair_request_id');
    }

    public function invoices()
    {
        return $this->morphMany(Invoice::class, 'invoiceable');
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
