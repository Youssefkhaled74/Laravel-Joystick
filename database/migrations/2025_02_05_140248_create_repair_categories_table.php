<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('repair_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('tags')->nullable();
            $table->string('image')->nullable();
            $table->string('parent_id')->nullable()->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_categories');
    }
};
