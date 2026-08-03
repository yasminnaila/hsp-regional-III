<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE basic_items MODIFY COLUMN item_type ENUM('labor', 'material', 'equipment', 'dkd') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE basic_items MODIFY COLUMN item_type ENUM('labor', 'material', 'equipment') NOT NULL");
    }
};
