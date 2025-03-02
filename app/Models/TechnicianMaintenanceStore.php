<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianMaintenanceStore extends Model
{
    // use HasFactory;
    protected $table = 'technician_maintenance_store';

    protected $fillable = [
        'repair_request_order_id',
        'maintenance_store_id',
        'quantity',
        'team_id',
        'repair_request_id',
    ];

    public function repairRequestOrder()
    {
        return $this->belongsTo(RepairRequestOrder::class, 'repair_request_order_id');
    }

    public function maintenanceStore()
    {
        return $this->belongsTo(MaintenanceStore::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function repairRequest()
    {
        return $this->belongsTo(RepairRequests::class,'repair_request_id');
    }
}
