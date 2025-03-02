<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RepairRequestDevices extends Model
{
    use HasFactory;
    protected $fillable = ['repair_request_id', 'device_id', 'problem_parts', 'notes'];
    public function repairRequest()
    {
        return $this->belongsTo(RepairRequests::class);
    }
    public function device()
    {
        return $this->belongsTo(Device::class);
    }
    public function problemPartsList()
    {
        return Proplems_Parts::whereIn('number', json_decode($this->problem_parts, true))->get();
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
