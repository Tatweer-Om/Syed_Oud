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
        Schema::create('material_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->string('stock_code')->nullable();
            $table->string('barcode')->nullable();
            $table->string('design_name')->nullable();
            $table->enum('operation_type', ['stock_added', 'quantity_added', 'special_order_received'])->default('stock_added');
            $table->integer('quantity_added')->default(0);
            $table->unsignedBigInteger('tailor_id')->nullable();
            $table->string('tailor_name')->nullable();
            $table->unsignedBigInteger('special_order_id')->nullable();
            $table->string('special_order_number')->nullable();
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('added_by')->nullable();
            $table->timestamp('added_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('stock_id');
            $table->index('operation_type');
            $table->index('tailor_id');
            $table->index('added_at');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_audit_logs');
    }
};
