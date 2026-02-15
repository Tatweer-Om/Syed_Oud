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
            $table->string('status')->default('draft')->after('total_amount');
            $table->decimal('cost_per_unit', 12, 2)->default(0)->after('status');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->string('status')->default('approved')->after('total_amount');
            $table->decimal('cost_per_unit', 12, 2)->default(0)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_drafts', function (Blueprint $table) {
            $table->dropColumn(['status', 'cost_per_unit']);
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn(['status', 'cost_per_unit']);
        });
    }
};
