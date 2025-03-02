<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name','status'];

    public function technicions()
    {
        return $this->hasMany(Technicion::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function repairRequests()
    {
        return $this->hasMany(RepairRequests::class);
    }
    
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d M Y, h:i A');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d M Y, h:i A');
    }

    public function location()
    {
        return $this->hasOne(Location::class, 'team_id');
    }
    
    public function technicianMaintenanceStores()
    {
        return $this->hasMany(TechnicianMaintenanceStore::class, 'team_id');
    }
}