<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProcessingJobs extends Model
{
    /** @use HasFactory<\Database\Factories\ProcessingJobsFactory> */
    use HasFactory, HasUuids;

    protected $table = 'processing_jobs';

    protected $fillable = [
        'document_id',
        'user_id',
        'operation_id',
        'status',
        'progress',
        'attempts',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress' => 'integer',
        'attempts' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Documents::class, 'document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operations::class, 'operation_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(JobStatusHistories::class, 'processing_job_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(JobResults::class, 'processing_job_id');
    }
}
