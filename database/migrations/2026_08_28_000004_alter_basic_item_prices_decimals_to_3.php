<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_item_prices', function (Blueprint $table): void {
            $table->decimal('price', 18, 3)->change();
            $table->decimal('reference_price_1', 18, 3)->nullable()->change();
            $table->decimal('reference_price_2', 18, 3)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('basic_item_prices', function (Blueprint $table): void {
            $table->decimal('price', 18, 2)->change();
            $table->decimal('reference_price_1', 18, 2)->nullable()->change();
            $table->decimal('reference_price_2', 18, 2)->nullable()->change();
        });
    }
};
