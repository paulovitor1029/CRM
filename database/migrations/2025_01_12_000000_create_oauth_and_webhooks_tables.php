<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('secret'); // hashed
            $table->json('scopes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampsTz(0);
        });

        Schema::create('oauth_access_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id');
            $table->string('token', 128)->unique();
            $table->json('scopes')->nullable();
            $table->timestampTz('expires_at');
            $table->timestampsTz(0);
            $table->foreign('client_id')->references('id')->on('oauth_clients')->cascadeOnDelete();
            $table->index(['client_id','expires_at']);
        });

        Schema::create('webhook_endpoints', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('event_key');
            $table->string('url');
            $table->string('secret');
            $table->json('headers')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampsTz(0);
            $table->unique(['tenant_id','event_key','url']);
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('endpoint_id');
            $table->uuid('outbox_id')->nullable();
            $table->string('event_key');
            $table->json('payload');
            $table->string('idempotency_key')->nullable();
            $table->string('status')->default('pending'); // pending|delivering|delivered|failed
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('next_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampsTz(0);
            $table->foreign('endpoint_id')->references('id')->on('webhook_endpoints')->cascadeOnDelete();
            $table->index(['endpoint_id','status','next_attempt_at']);
            $table->unique(['endpoint_id','outbox_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('oauth_access_tokens');
        Schema::dropIfExists('oauth_clients');
    }
};

