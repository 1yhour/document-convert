<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobStatusHistories extends Model
{
    /** @use HasFactory<\Database\Factories\JobStatusHistoriesFactory> */
    use HasFactory, HasUuids;

    protected $table = 'job_status_histories';

    protected $fillable = [
        'processing_job_id',
        'from_status',
        'to_status',
        'external_status',
        'note',
    ];

    public function processingJob(): BelongsTo
    {
        return $this->belongsTo(ProcessingJobs::class, 'processing_job_id');
    }
}
