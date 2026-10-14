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
        Schema::create('conversions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('operation');
            $table->string('original_name');
            $table->string('input_path')->nullable();
            $table->string('output_path')->nullable();
            $table->enum('status', ['pending', 'uploading', 'processing', 'completed', 'failed'])->default('pending')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('cloudconvert_job_id')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};
