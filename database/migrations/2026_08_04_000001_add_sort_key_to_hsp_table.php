<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hsp', function (Blueprint $table): void {
            $table->string('sort_key', 64)->nullable()->after('work_code');
            $table->index('sort_key');
        });
    }

    public function down(): void
    {
        Schema::table('hsp', function (Blueprint $table): void {
            $table->dropIndex(['sort_key']);
            $table->dropColumn('sort_key');
        });
    }
};
