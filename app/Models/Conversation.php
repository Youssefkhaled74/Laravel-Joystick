<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [ 'user_id' , 'customer_service_id' , 'status'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customerService()
    {
        return $this->belongsTo(User::class, 'customer_service_id');
    }

    public function messages()
    {
        return $this->hasMany(Chat::class);
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
