<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('entity_type'); // customers|products|contacts
            $table->string('status')->default('uploaded'); // uploaded|mapped|validating|validated|processing|completed|failed|canceled
            $table->string('file_key');
            $table->string('original_filename')->nullable();
            $table->json('mapping')->nullable();
            $table->unsignedBigInteger('total_rows')->default(0);
            $table->unsignedBigInteger('valid_rows')->default(0);
            $table->unsignedBigInteger('invalid_rows')->default(0);
            $table->string('error_report_key')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestampsTz(0);
            $table->index(['tenant_id','entity_type','status']);
        });

        Schema::create('import_job_errors', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('import_job_id');
            $table->unsignedBigInteger('row_number');
            $table->json('errors');
            $table->json('row_data')->nullable();
            $table->timestampsTz(0);
            $table->foreign('import_job_id')->references('id')->on('import_jobs')->cascadeOnDelete();
            $table->index(['import_job_id','row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_job_errors');
        Schema::dropIfExists('import_jobs');
    }
};

