<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class RepairCategory extends Model
{
    // use HasFactory;
    use HasTranslations;

    protected $table = 'repair_categories';

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

    // MaintenanceStore relationship
    public function MaintenanceStore()
    {
        return $this->hasMany(MaintenanceStore::class);
    }

}
