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
            $table->decimal('total_amount', 12, 2)->default(0)->after('total_items');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->string('batch_id')->nullable()->after('filling_id');
            $table->decimal('total_amount', 12, 2)->default(0)->after('total_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_drafts', function (Blueprint $table) {
            $table->dropColumn('total_amount');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn(['batch_id', 'total_amount']);
        });
    }
};
