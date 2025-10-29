<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_consents', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('subject_type'); // user|customer|...
            $table->uuid('subject_id');
            $table->string('purpose'); // e.g., marketing, analytics
            $table->string('version')->nullable();
            $table->timestampTz('given_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz(0);
            $table->index(['tenant_id','subject_type','subject_id','purpose']);
        });

        Schema::create('access_logs', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('subject_type');
            $table->uuid('subject_id');
            $table->string('actor_type')->default('system'); // user|system|api
            $table->uuid('actor_id')->nullable();
            $table->string('action'); // read|update|delete|export|anonymize|pseudonymize|consent
            $table->string('resource')->nullable(); // e.g., customers, users
            $table->uuid('resource_id')->nullable();
            $table->json('fields')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestampTz('occurred_at')->default(DB::raw('now()'));
            $table->timestampsTz(0);
            $table->index(['tenant_id','subject_type','subject_id','occurred_at']);
        });

        Schema::create('data_retention_policies', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('entity'); // e.g., customers, access_logs
            $table->unsignedInteger('retention_days')->default(365);
            $table->string('action')->default('anonymize'); // anonymize|delete
            $table->json('conditions')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampsTz(0);
            $table->unique(['tenant_id','entity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_retention_policies');
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('privacy_consents');
    }
};

