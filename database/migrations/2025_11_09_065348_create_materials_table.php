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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('material_name');
            $table->longText('description')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('material_type', 50)->default('production');
            $table->decimal('unit_price', 10, 3)->default(0);
            $table->string('added_by')->nullable();
            $table->string('user_id')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
