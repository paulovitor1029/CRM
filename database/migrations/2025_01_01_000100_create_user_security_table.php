<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_security', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('user_id');
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable(); // Base32 encoded
            $table->json('two_factor_recovery_codes')->nullable();
            $table->unsignedSmallInteger('two_factor_failed_attempts')->default(0);
            $table->timestampTz('last_2fa_at')->nullable();
            $table->timestampsTz(0);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_security');
    }
};

