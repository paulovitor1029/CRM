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
            $table->string('tenant_id', 64)->default('default');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestampsTz(0);
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('products', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->string('tenant_id', 64)->default('default');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name');
            $table->string('sku')->unique();
            $table->integer('price_cents');
            $table->string('currency', 3)->default('BRL');
            $table->boolean('active')->default(true);
            $table->timestampsTz(0);
            $table->foreign('category_id')->references('id')->on('product_categories')->nullOnDelete();
        });

        Schema::create('product_metadata', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('product_id');
            if (method_exists($table, 'jsonb')) {
                $table->jsonb('data')->nullable();
            } else {
                $table->json('data')->nullable();
            }
            $table->timestampsTz(0);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('plans', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->string('tenant_id', 64)->default('default');
            $table->uuid('product_id');
            $table->string('name');
            $table->string('billing_interval')->default('month'); // day, week, month, year
            $table->unsignedSmallInteger('billing_period')->default(1); // every N intervals
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->boolean('pro_rata')->default(true);
            $table->unsignedSmallInteger('courtesy_days')->default(0);
            $table->json('limits')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampsTz(0);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('addons', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->string('tenant_id', 64)->default('default');
            $table->uuid('product_id');
            $table->string('name');
            $table->string('billing_interval')->default('month');
            $table->unsignedSmallInteger('billing_period')->default(1);
            $table->json('limits')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampsTz(0);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('bundles', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->string('tenant_id', 64)->default('default');
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampsTz(0);
        });

        Schema::create('bundle_items', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('bundle_id');
            $table->string('item_type'); // product|plan|addon
            $table->uuid('item_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestampsTz(0);
            $table->foreign('bundle_id')->references('id')->on('bundles')->cascadeOnDelete();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->string('tenant_id', 64)->default('default');
            $table->uuid('customer_id');
            $table->string('status')->default('active'); // active|trialing|canceled|paused
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('trial_ends_at')->nullable();
            $table->timestampTz('next_billing_at')->nullable();
            $table->boolean('pro_rata')->default(true);
            $table->timestampTz('courtesy_until')->nullable();
            $table->json('limits')->nullable();
            $table->timestampsTz(0);
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->index(['tenant_id', 'customer_id', 'status']);
        });

        Schema::create('subscription_items', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('subscription_id');
            $table->string('item_type'); // plan|addon|product
            $table->uuid('item_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->integer('price_cents');
            $table->string('currency', 3)->default('BRL');
            $table->timestampsTz(0);
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
        });

        Schema::create('subscription_logs', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('subscription_id');
            $table->string('action'); // create, update, cancel
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('origin')->nullable();
            $table->timestampsTz(0);
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
            $table->index(['subscription_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_logs');
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('bundle_items');
        Schema::dropIfExists('bundles');
        Schema::dropIfExists('addons');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('product_metadata');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};

