<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('organization_id', 64)->default('default');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestampsTz(0);
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('products', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->string('organization_id', 64)->default('default');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name');
            $table->string('sku')->unique();
            $table->integer('price_cents');
            $table->string('currency', 3)->default('BRL');
            $table->boolean('active')->default(true);
            $table->timestampsTz(0);
            $table->foreign('category_id')->references('id')->on('product_categories')->nullOnDelete();
        });
        // Removed: product_metadata, plans, addons, bundles, subscriptions and related tables
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
