<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class MaintenanceStore extends Model
{
    use HasTranslations;

    protected $table = 'maintenance_stores';

    public $translatable = ['name','description'];

    protected $fillable = [
        'name',
        'description',
        'price',
        'quantity',
        'repair_category_id',
        'tags',
        'image',
        'uuid',
        'status'
    ];

   // RepairCategory relationship
   public function RepairCategory()
   {
       return $this->belongsTo(RepairCategory::class);
   }

   // Tags relationship
   public function tags()
   {
       return $this->belongsToMany(Tags::class);
   }

}
