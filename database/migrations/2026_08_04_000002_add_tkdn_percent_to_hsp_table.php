<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hsp', function (Blueprint $table): void {
            $table->decimal('tkdn_percent', 6, 2)->nullable()->after('sort_key');
        });
    }

    public function down(): void
    {
        Schema::table('hsp', function (Blueprint $table): void {
            $table->dropColumn('tkdn_percent');
        });
    }
};
