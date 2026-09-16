<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('processing_job_id')->unique()->constrained('processing_jobs')->cascadeOnDelete();
            $table->string('result_path');
            $table->string('result_mime_type');
            $table->bigInteger('size_bytes');
            $table->string('external_task_id');
            $table->string('external_document_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_results');
    }
};
