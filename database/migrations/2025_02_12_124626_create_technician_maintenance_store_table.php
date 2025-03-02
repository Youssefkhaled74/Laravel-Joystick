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
        Schema::create('technician_maintenance_store', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_request_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('maintenance_store_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_maintenance_store');
    }
};
