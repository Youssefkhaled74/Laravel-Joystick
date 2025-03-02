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
        Schema::create('tracking', function (Blueprint $table) {
            $table->id();
            $table->decimal('team_latitude', 10, 7)->nullable();
            $table->decimal('team_longitude', 10, 7)->nullable();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('repair_request_id');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('repair_request_id')->references('id')->on('repair_requests')->onDelete('cascade');
            $table->string('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking');
    }
};
