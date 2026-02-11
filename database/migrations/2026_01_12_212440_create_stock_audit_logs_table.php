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
        Schema::create('stock_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->string('stock_code')->nullable();
            $table->string('barcode')->nullable();
            $table->string('design_name')->nullable();
            $table->enum('operation_type', ['added', 'updated', 'sold', 'transferred', 'special_order'])->default('added');
            $table->integer('previous_quantity')->default(0);
            $table->integer('new_quantity')->default(0);
            $table->integer('quantity_change')->default(0); // positive for add, negative for subtract
            $table->string('related_id')->nullable(); // order_no, transfer_code, special_order_id
            $table->string('related_type')->nullable(); // 'pos_order', 'transfer', 'special_order'
            $table->text('related_info')->nullable(); // JSON or text for additional info like 'to_whom' for transfers
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('added_by')->nullable(); // user name
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('stock_id');
            $table->index('stock_code');
            $table->index('barcode');
            $table->index('operation_type');
            $table->index('created_at');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_audit_logs');
    }
};
