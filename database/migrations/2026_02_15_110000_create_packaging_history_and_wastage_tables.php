<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packaging_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packaging_id')->constrained('packagings')->onDelete('cascade');
            $table->string('batch_id')->nullable();
            $table->string('action');
            $table->unsignedBigInteger('material_id')->nullable();
            $table->string('material_name')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit')->nullable();
            $table->string('wastage_type')->nullable();
            $table->text('notes')->nullable();
            $table->string('added_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('packaging_wastage_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packaging_id')->constrained('packagings')->onDelete('cascade');
            $table->string('batch_id')->nullable();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->string('material_name')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit')->nullable();
            $table->string('wastage_type')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('added_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packaging_wastage_materials');
        Schema::dropIfExists('packaging_history');
    }
};
