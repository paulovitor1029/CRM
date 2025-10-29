<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->uuid('user_id')->nullable();
            $table->string('type');
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->string('channel')->default('in_app'); // in_app|web_push|broadcast
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampsTz(0);
            $table->index(['tenant_id','user_id','created_at']);
        });

        Schema::create('user_notification_prefs', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->json('preferences')->nullable(); // { quiet_hours: {start,end,timezone}, enabled_types:[], channels:[] }
            $table->json('push_subscriptions')->nullable(); // [{endpoint, keys:{p256dh,auth}, browser, platform}]
            $table->timestampsTz(0);
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 64)->default('default');
            $table->string('key')->unique(); // e.g., task.created
            $table->string('title');
            $table->text('body');
            $table->timestampsTz(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('user_notification_prefs');
        Schema::dropIfExists('notifications');
    }
};

