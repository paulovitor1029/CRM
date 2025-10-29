<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->uuid('customer_id');
            $table->uuid('subscription_id')->nullable();
            $table->string('status')->default('open'); // open|paid|failed|canceled
            $table->string('currency', 3)->default('BRL');
            $table->integer('subtotal_cents')->default(0);
            $table->integer('discount_cents')->default(0);
            $table->integer('courtesy_cents')->default(0);
            $table->integer('total_cents')->default(0);
            $table->timestampTz('period_start')->nullable();
            $table->timestampTz('period_end')->nullable();
            $table->timestampTz('issued_at')->nullable();
            $table->timestampTz('due_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestampsTz(0);
            $table->index(['tenant_id','customer_id','status']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('invoice_id');
            $table->string('type')->default('plan'); // plan|addon|product|prorate|courtesy|adjustment
            $table->uuid('reference_id')->nullable();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->integer('unit_price_cents')->default(0);
            $table->integer('total_cents')->default(0);
            $table->timestampsTz(0);
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });

        Schema::create('payments', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('invoice_id');
            $table->string('status')->default('pending'); // pending|paid|failed
            $table->integer('amount_cents');
            $table->string('currency', 3)->default('BRL');
            $table->string('method')->nullable();
            $table->string('external_id')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestampsTz(0);
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->index(['invoice_id','status']);
        });

        Schema::create('invoice_logs', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('invoice_id');
            $table->string('action'); // issue|update|payment|cancel
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('origin')->nullable();
            $table->timestampsTz(0);
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};

