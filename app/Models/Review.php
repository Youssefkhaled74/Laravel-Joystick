<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;
    protected $fillable = [
        'admin_id',
        'content',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
        public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
