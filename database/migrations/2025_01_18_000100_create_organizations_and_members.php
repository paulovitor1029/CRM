<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('name');
            $table->string('slug')->unique();
            $table->uuid('created_by')->nullable();
            if (method_exists($table, 'jsonb')) { $table->jsonb('settings')->nullable(); } else { $table->json('settings')->nullable(); }
            $table->string('status')->default('active'); // active|inactive
            $table->timestampsTz(0);
        });

        Schema::create('organization_user', function (Blueprint $table) {
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->string('role')->default('member'); // org_admin|member
            $table->json('permissions')->nullable();
            $table->timestampTz('invited_at')->nullable();
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampsTz(0);
            $table->primary(['organization_id','user_id']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
    }
};

