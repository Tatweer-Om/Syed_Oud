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
        Schema::table('production_drafts', function (Blueprint $table) {
            $table->date('production_date')->nullable()->after('stock_id');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->date('production_date')->nullable()->after('stock_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_drafts', function (Blueprint $table) {
            $table->dropColumn('production_date');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn('production_date');
        });
    }
};
