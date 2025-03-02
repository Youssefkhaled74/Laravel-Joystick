<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('repair_request_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_request_id')->constrained('repair_requests')->onDelete('cascade');
            $table->foreignId('technicion_id')->constrained('technicions')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Drop the child table first
        Schema::dropIfExists('repair_request_order_items');
    
        // Drop the parent table
        Schema::dropIfExists('repair_request_orders');
    }
};
