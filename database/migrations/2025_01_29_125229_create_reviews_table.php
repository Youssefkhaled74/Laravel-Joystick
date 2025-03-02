<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User who wrote the review
            $table->text('content'); // Review content
            $table->boolean('is_approved')->default(false); // Whether the review is approved
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Admin who approved the review
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
};
