<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 100)->unique()->after('name');
            $table->enum('role', ['admin', 'user'])->default('user')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
        });

        Schema::create('periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('regions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 255);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hsp', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('period_id')->constrained('periods')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete()->cascadeOnUpdate();
            $table->string('work_code', 100);
            $table->string('binkon_code', 100)->nullable()->index();
            $table->text('description');
            $table->string('unit', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['period_id', 'work_code']);
            $table->index('category_id');
        });

        Schema::create('hsp_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hsp_id')->constrained('hsp')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('region_id')->constrained('regions')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('regional_code', 100)->index();
            $table->decimal('material', 18, 2)->default(0);
            $table->decimal('service', 18, 2)->default(0);
            $table->decimal('price', 18, 2)->default(0);
            $table->timestamps();
            $table->unique(['hsp_id', 'region_id']);
            $table->index(['region_id', 'price']);
        });

        Schema::create('basic_items', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 100)->nullable()->index();
            $table->string('source_no', 50)->nullable();
            $table->enum('item_type', ['labor', 'material', 'equipment']);
            $table->string('description', 500);
            $table->string('unit', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['item_type', 'unit']);
        });

        Schema::create('basic_item_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('basic_item_id')->constrained('basic_items')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('period_id')->constrained('periods')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('region_id')->constrained('regions')->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('price', 18, 2)->default(0);
            $table->timestamps();
            $table->unique(['basic_item_id', 'period_id', 'region_id']);
            $table->index(['period_id', 'region_id']);
        });

        Schema::create('ahsp_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hsp_id')->constrained('hsp')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('basic_item_id')->constrained('basic_items')->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('coefficient', 20, 8)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->index(['hsp_id', 'basic_item_id']);
            $table->index(['hsp_id', 'sort_order']);
        });

        Schema::create('ahsp_parameters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hsp_id')->constrained('hsp')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('region_id')->constrained('regions')->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('overhead_profit_percent', 8, 4)->default(0);
            $table->timestamps();
            $table->unique(['hsp_id', 'region_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ahsp_parameters');
        Schema::dropIfExists('ahsp_components');
        Schema::dropIfExists('basic_item_prices');
        Schema::dropIfExists('basic_items');
        Schema::dropIfExists('hsp_prices');
        Schema::dropIfExists('hsp');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('periods');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'role', 'is_active']);
        });
    }
};
