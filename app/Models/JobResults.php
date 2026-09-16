<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobResults extends Model
{
    /** @use HasFactory<\Database\Factories\JobResultsFactory> */
    use HasFactory, HasUuids;

    protected $table = 'job_results';

    protected $fillable = [
        'processing_job_id',
        'result_path',
        'result_mime_type',
        'size_bytes',
        'external_task_id',
        'external_document_url',
    ];

    public function processingJob(): BelongsTo
    {
        return $this->belongsTo(ProcessingJobs::class, 'processing_job_id');
    }
}

