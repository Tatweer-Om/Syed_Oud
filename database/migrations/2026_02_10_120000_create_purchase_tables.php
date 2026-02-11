<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stage 1: Drafts (waiting for completion) - editable, deletable
        Schema::create('purchase_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->string('invoice_no')->nullable();
                         $table->decimal('invoice_amount', 12, 2)->default(0);

            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('materials_json')->nullable(); // array of material line items
            $table->decimal('total_quantity', 12, 2)->default(0);
            $table->integer('total_items')->default(0); // count of lines
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->integer('user_id')->nullable();
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        // Completed purchases (header)
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->string('invoice_no')->nullable();
             $table->decimal('invoice_amount', 12, 2)->default(0);
            $table->decimal('total_quantity', 12, 2)->default(0);
            $table->integer('total_items')->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('total_shipping_price', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        // Purchase details (materials as JSON per purchase)
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->json('materials_json')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
        });

        // Purchase payments (for now: purchase_id, account_id, amount)
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->integer('user_id')->nullable();
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
        });

        // Uses existing 'histories' table (no new migration)
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_details');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('purchase_drafts');
    }
};
