<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['repair_request_id']);
            $table->dropColumn('repair_request_id');
            $table->morphs('invoiceable');
        });
    }


    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropMorphs('invoiceable');
            $table->foreignId('repair_request_id')->constrained()->onDelete('cascade');
        });
    }
};
