<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 64)->default('default');
            $table->string('name');
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz(0);
            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_statuses');
    }
};

