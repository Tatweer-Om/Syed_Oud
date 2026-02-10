<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_name');
            $table->timestamps();
        });

        // Seed default units used by materials
        DB::table('units')->insert([
            ['unit_name' => 'meter', 'created_at' => now(), 'updated_at' => now()],
            ['unit_name' => 'piece', 'created_at' => now(), 'updated_at' => now()],
            ['unit_name' => 'roll', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
