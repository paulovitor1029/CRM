<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->string('tenant_id', 64)->default('default');
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestampsTz(0);
            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sectors');
    }
};

