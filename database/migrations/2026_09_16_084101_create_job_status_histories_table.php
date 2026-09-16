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
        Schema::create('job_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('processing_job_id')->constrained('processing_jobs')->cascadeOnDelete();
            $table->string('from_status');
            $table->string('to_status');
            $table->string('external_status');
            $table->text('note');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_status_histories');
    }
};
