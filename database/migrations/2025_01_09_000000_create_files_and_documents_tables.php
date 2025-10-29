<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_objects', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->uuid('user_id')->nullable();
            $table->string('disk')->default(env('FILES_DISK','local'));
            $table->string('key');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('content_type')->nullable();
            $table->string('checksum', 128)->nullable();
            $table->timestampTz('uploaded_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestampsTz(0);
            $table->unique(['tenant_id','key']);
            $table->index(['tenant_id','user_id']);
        });

        Schema::create('documents', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->uuid('owner_id')->nullable();
            $table->uuid('sector_id')->nullable();
            $table->unsignedInteger('current_version')->default(0);
            $table->timestampTz('autosave_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestampsTz(0);
            $table->foreign('sector_id')->references('id')->on('sectors')->nullOnDelete();
            $table->index(['tenant_id','owner_id']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('document_id');
            $table->unsignedInteger('version');
            $table->longText('content')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestampsTz(0);
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->unique(['document_id','version']);
        });

        Schema::create('document_shares', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('document_id');
            $table->string('role_name')->nullable();
            $table->uuid('sector_id')->nullable();
            $table->boolean('can_edit')->default(false);
            $table->timestampsTz(0);
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->foreign('sector_id')->references('id')->on('sectors')->nullOnDelete();
            $table->index(['document_id','role_name','sector_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_shares');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('file_objects');
    }
};

