<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    public $translatable = ['name', 'description', 'small_description'];

    // protected $guarded = [];

    protected $fillable = [
        'name',
        'description',
        'small_description',
        'price',
        'quantity',
        'category_id',
        'brand_id',
        'tags',
        'colors',
        'product_code',
        'main_image',
        'images',
        'status'
    ];

    protected $dates = ['deleted_at']; 

    // Category relationship
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'user_product_favorites');
    }

    // Tags relationship
    public function tags()
    {
        return $this->belongsToMany(Tags::class);
    }

    // Brand relationship
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }
    public function colors(){
        return $this->hasMany(ProductColor::class);
    }
}
