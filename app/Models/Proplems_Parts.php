<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Proplems_Parts extends Model
{
    use HasFactory;
    protected $table = 'problem_parts';

    protected $fillable = ['number', 'note'];

    public function repairRequestDevices()
    {
        return $this->hasMany(RepairRequestDevices::class);
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
