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
        Schema::table('repair_request_devices', function (Blueprint $table) {
            $table->renameColumn('Proplems_Parts', 'problem_parts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repair_request_devices', function (Blueprint $table) {
            $table->renameColumn('problem_parts', 'Proplems_Parts');
        });
    }
};
