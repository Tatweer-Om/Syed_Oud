<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('histories', function (Blueprint $table) {
            $table->id();
            $table->string('operation')->nullable();
            $table->string('source')->nullable();
            $table->string('added_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->softDeletes();
            $table->timestamp('added_at')->nullable();
            $table->json('previous_data')->nullable();
            $table->json('new_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('histories');
    }
};
