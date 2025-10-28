<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_history', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('customer_id');
            $table->string('action'); // create, update, delete
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('origin')->nullable();
            $table->timestampsTz(0);

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_history');
    }
};

