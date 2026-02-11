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
        Schema::table('material_quantity_audits', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_id')->nullable()->after('material_id');
            $table->string('stock_code')->nullable()->after('stock_id');
            $table->string('source')->nullable()->after('stock_code'); // 'stock', 'special_order', 'manage_quantity'
            $table->string('status')->nullable()->after('source'); // 'success', 'insufficient', 'error'
            
            $table->index('stock_id');
            $table->index('stock_code');
            $table->index('source');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_quantity_audits', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
            $table->dropIndex(['stock_id']);
            $table->dropIndex(['stock_code']);
            $table->dropIndex(['source']);
            $table->dropColumn(['stock_id', 'stock_code', 'source', 'status']);
        });
    }
};
