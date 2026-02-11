<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure quantity is decimal for numeric operations (no doctrine/dbal needed)
        DB::statement('ALTER TABLE materials MODIFY quantity DECIMAL(12,2) DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE materials MODIFY quantity VARCHAR(255) NULL');
    }
};
