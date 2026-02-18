<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packaging_history', function (Blueprint $table) {
            $table->decimal('actual_pieces_packed', 12, 2)->nullable()->after('phase_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('packaging_history', function (Blueprint $table) {
            $table->dropColumn('actual_pieces_packed');
        });
    }
};
