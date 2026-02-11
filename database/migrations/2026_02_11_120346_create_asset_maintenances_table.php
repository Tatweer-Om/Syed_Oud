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
        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')
                    ->constrained('assets')
                    ->cascadeOnDelete();

            $table->tinyInteger('maintenance_type')
                    ->comment('1: Scheduled, 2: Emergency');

            $table->date('maintenance_date');

            $table->date('next_maintenance_date')->nullable(); // for alerts

            $table->text('description')->nullable();

            $table->tinyInteger('performed_by')
                    ->comment('1: Internal, 2: External');

            $table->decimal('cost', 12, 3)->default(0);

            $table->tinyInteger('status')
                    ->default(1)
                    ->comment('1: Completed, 2: Pending');
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('user_id')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_maintenances');
    }
};
