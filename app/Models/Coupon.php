<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;
    protected $fillable = ['code','price','status','min_price','user_id','type','expire_date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
