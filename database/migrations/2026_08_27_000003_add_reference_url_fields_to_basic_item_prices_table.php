<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_item_prices', function (Blueprint $table): void {
            $table->text('reference_url_1')
                ->nullable()
                ->after('reference_link_1');
            $table->text('reference_url_2')
                ->nullable()
                ->after('reference_link_2');
        });
    }

    public function down(): void
    {
        Schema::table('basic_item_prices', function (Blueprint $table): void {
            $table->dropColumn([
                'reference_url_1',
                'reference_url_2',
            ]);
        });
    }
};
