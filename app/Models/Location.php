<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Location extends Model
{
    protected $table = 'locations';
    protected $fillable = ['name', 'team_id', 'coordinates','status'];

    protected $casts = [
        'coordinates' => 'array',
    ];

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
}
