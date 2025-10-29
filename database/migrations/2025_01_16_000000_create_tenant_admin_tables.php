<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_configs', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('scope'); // branding|domains|timezone|holidays|numbering
            $table->unsignedInteger('version')->default(1);
            $table->json('data');
            $table->uuid('updated_by')->nullable();
            $table->timestampsTz(0);
            $table->unique(['tenant_id','scope']);
        });

        Schema::create('tenant_config_logs', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64);
            $table->string('scope');
            $table->unsignedInteger('version');
            $table->json('before')->nullable();
            $table->json('after');
            $table->uuid('updated_by')->nullable();
            $table->timestampTz('updated_at');
            $table->index(['tenant_id','scope','version']);
        });

        Schema::create('tenant_custom_fields', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('entity'); // customers|products|contacts|tasks|...
            $table->string('name');
            $table->string('key');
            $table->string('type'); // string|number|boolean|date|enum
            $table->boolean('required')->default(false);
            $table->json('visibility_roles')->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->uuid('updated_by')->nullable();
            $table->timestampsTz(0);
            $table->unique(['tenant_id','entity','key']);
        });

        Schema::create('tenant_custom_field_logs', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('field_id');
            $table->unsignedInteger('version');
            $table->json('before')->nullable();
            $table->json('after');
            $table->uuid('updated_by')->nullable();
            $table->timestampTz('updated_at');
            $table->foreign('field_id')->references('id')->on('tenant_custom_fields')->cascadeOnDelete();
        });

        Schema::create('tenant_feature_flags', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('flag_key');
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->uuid('updated_by')->nullable();
            $table->timestampsTz(0);
            $table->unique(['tenant_id','flag_key']);
        });

        Schema::create('tenant_feature_flag_logs', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64);
            $table->string('flag_key');
            $table->unsignedInteger('version');
            $table->boolean('enabled');
            $table->uuid('updated_by')->nullable();
            $table->timestampTz('updated_at');
            $table->index(['tenant_id','flag_key','version']);
        });

        Schema::create('message_templates', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('channel'); // push|email|wa
            $table->string('key'); // e.g., task.assigned
            $table->string('subject')->nullable();
            $table->text('body');
            $table->unsignedInteger('version')->default(1);
            $table->uuid('updated_by')->nullable();
            $table->timestampsTz(0);
            $table->unique(['tenant_id','channel','key']);
        });

        Schema::create('message_template_logs', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64);
            $table->string('channel');
            $table->string('key');
            $table->unsignedInteger('version');
            $table->json('before')->nullable();
            $table->json('after');
            $table->uuid('updated_by')->nullable();
            $table->timestampTz('updated_at');
            $table->index(['tenant_id','channel','key','version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_template_logs');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('tenant_feature_flag_logs');
        Schema::dropIfExists('tenant_feature_flags');
        Schema::dropIfExists('tenant_custom_field_logs');
        Schema::dropIfExists('tenant_custom_fields');
        Schema::dropIfExists('tenant_config_logs');
        Schema::dropIfExists('tenant_configs');
    }
};

