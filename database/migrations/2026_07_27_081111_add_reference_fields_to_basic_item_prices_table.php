<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'basic_item_prices',
            function (Blueprint $table): void {
                $table->decimal(
                    'reference_price_1',
                    18,
                    2
                )
                    ->nullable()
                    ->after('price');

                $table->text('reference_link_1')
                    ->nullable()
                    ->after('reference_price_1');

                $table->decimal(
                    'reference_price_2',
                    18,
                    2
                )
                    ->nullable()
                    ->after('reference_link_1');

                $table->text('reference_link_2')
                    ->nullable()
                    ->after('reference_price_2');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'basic_item_prices',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'reference_price_1',
                    'reference_link_1',
                    'reference_price_2',
                    'reference_link_2',
                ]);
            }
        );
    }
};
