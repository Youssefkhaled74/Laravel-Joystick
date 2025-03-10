<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['name'];

    protected $guarded = [];

    // Parent relationship
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
    
    // Tags relationship
    public function tags(){

        return $this->belongsTo(Tags::class,'tags');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    
}
