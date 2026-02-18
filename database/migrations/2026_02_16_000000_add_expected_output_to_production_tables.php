<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_drafts', function (Blueprint $table) {
            $table->decimal('expected_output', 12, 2)->nullable()->after('estimated_output');
        });
        Schema::table('productions', function (Blueprint $table) {
            $table->decimal('expected_output', 12, 2)->nullable()->after('estimated_output');
        });
    }

    public function down(): void
    {
        Schema::table('production_drafts', function (Blueprint $table) {
            $table->dropColumn('expected_output');
        });
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn('expected_output');
        });
    }
};
