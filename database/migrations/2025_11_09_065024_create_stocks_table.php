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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('stock_name')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('barcode')->nullable();
             $table->string('image')->nullable();
            $table->longText('stock_notes')->nullable();
            $table->decimal('cost_price', 10, 3)->nullable();
            $table->decimal('sales_price', 10, 3)->nullable();
            $table->decimal('discount', 10, 3)->nullable();
            $table->decimal('tax', 10, 3)->nullable();
            $table->string('quantity')->nullable();
            $table->integer('notification_limit')->nullable();
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('user_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
