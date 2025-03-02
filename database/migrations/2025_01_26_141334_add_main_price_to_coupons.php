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
        Schema::table('coupons', function (Blueprint $table) {
            $table->decimal('min_price', 10, 2)->nullable()->after('status');
            $table->string('user_id')->nullable()->after('min_price');
            $table->enum('type',[1,2])->default(1)->comment('1: fixed, 2: percentage')->nullable()->after('user_id');
            $table->date('expire_date')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('min_price');
            $table->dropColumn('user_id');
            $table->dropColumn('type');
            $table->dropColumn('expire_date');
        });
    }
};
