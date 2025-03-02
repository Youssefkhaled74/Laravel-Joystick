<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tracking extends Model
{
    // use HasFactory;

    protected $table = 'tracking';

    protected $fillable = [
        'team_latitude',
        'team_longitude',
        'team_id',
        'repair_request_id',
        'status',
    ];
    
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function repairRequest()
    {
        return $this->belongsTo(RepairRequests::class,'repair_request_id');
    }
}
