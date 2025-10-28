<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('customer_id');
            $table->string('type')->default('home'); // home, billing, shipping
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city');
            $table->string('state', 64)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('country', 2)->default('BR');
            $table->json('meta')->nullable();
            $table->timestampsTz(0);

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->index(['customer_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};

