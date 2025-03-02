<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->text('fcm_token')->nullable();
        });

        Schema::table('technicions', function (Blueprint $table) {
            $table->text('fcm_token')->nullable();
        });

        Schema::table('storekeepers', function (Blueprint $table) {
            $table->text('fcm_token')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });

        Schema::table('technicions', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });

        Schema::table('store_keepers', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });
    }
};