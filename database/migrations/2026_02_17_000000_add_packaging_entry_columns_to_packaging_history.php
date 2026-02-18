<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packaging_history', function (Blueprint $table) {
            $table->unsignedBigInteger('production_id')->nullable()->after('packaging_id');
            $table->string('filling_id')->nullable()->after('batch_id');
            $table->date('packaging_date')->nullable()->after('filling_id');
            $table->json('materials_json')->nullable()->after('packaging_date');
            $table->decimal('production_output_taken', 12, 2)->nullable()->after('materials_json');
            $table->decimal('expected_packaging_units', 12, 2)->nullable()->after('production_output_taken');
        });
    }

    public function down(): void
    {
        Schema::table('packaging_history', function (Blueprint $table) {
            $table->dropColumn([
                'production_id', 'filling_id', 'packaging_date',
                'materials_json', 'production_output_taken', 'expected_packaging_units'
            ]);
        });
    }
};
