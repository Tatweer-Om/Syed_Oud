<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->decimal('actual_packaging_output', 12, 2)->default(0)->after('actual_output');
        });

        Schema::table('packagings', function (Blueprint $table) {
            $table->decimal('actual_packaging_output', 12, 2)->default(0)->after('actual_output');
        });
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn('actual_packaging_output');
        });
        Schema::table('packagings', function (Blueprint $table) {
            $table->dropColumn('actual_packaging_output');
        });
    }
};
