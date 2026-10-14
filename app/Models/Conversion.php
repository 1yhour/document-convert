<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'conversions';

    protected $fillable = [
        'user_id',
        'operation',
        'original_name',
        'input_path',
        'output_path',
        'status',
        'progress',
        'cloudconvert_job_id',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'progress' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
