<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('material_quantity_audits')) {
            return;
        }

        Schema::create('material_quantity_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id');
            $table->string('material_name')->nullable();
            $table->string('operation_type')->default('added');
            $table->decimal('previous_quantity', 10, 2)->default(0);
            $table->decimal('new_quantity', 10, 2)->default(0);
            $table->decimal('quantity_change', 10, 2)->default(0);
            $table->decimal('remaining_quantity', 10, 2)->default(0);
            $table->unsignedBigInteger('tailor_id')->nullable();
            $table->string('tailor_name')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('added_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('material_id');
            $table->index('operation_type');
            $table->index('created_at');
        });

        // Add foreign key only if materials table exists (avoid failure on fresh install order)
        if (Schema::hasTable('materials')) {
            Schema::table('material_quantity_audits', function (Blueprint $table) {
                $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
            });
        }

        // Add optional columns from the later migration if not present
        if (!Schema::hasColumn('material_quantity_audits', 'stock_id')) {
            Schema::table('material_quantity_audits', function (Blueprint $table) {
                $table->unsignedBigInteger('stock_id')->nullable()->after('material_id');
                $table->string('stock_code')->nullable()->after('stock_id');
                $table->string('source')->nullable()->after('stock_code');
                $table->string('status')->nullable()->after('source');
                $table->decimal('tailor_material_quantity_deducted', 10, 2)->nullable();
                $table->decimal('previous_tailor_material_quantity', 10, 2)->nullable();
                $table->decimal('new_tailor_material_quantity', 10, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('material_quantity_audits');
    }
};
