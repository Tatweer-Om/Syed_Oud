<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packagings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->onDelete('cascade');
            $table->string('batch_id')->nullable();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->string('packaging_id')->unique()->nullable();
            $table->string('filling_id')->unique()->nullable();
            $table->decimal('estimated_output', 12, 2)->default(0);
            $table->decimal('actual_output', 12, 2)->nullable();
            $table->decimal('total_quantity', 12, 2)->default(0);
            $table->integer('total_items')->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('cost_per_unit', 12, 2)->default(0);
            $table->string('status')->default('under_process');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('packaging_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packaging_id')->constrained('packagings')->onDelete('cascade');
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->json('materials_json')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('added_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packaging_details');
        Schema::dropIfExists('packagings');
    }
};
