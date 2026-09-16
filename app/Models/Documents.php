<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Documents extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentsFactory> */
    use HasFactory, HasUuids;

    protected $table = 'documents';

    protected $fillable = [
        'user_id',
        'original_name',
        'stored_path',
        'disk',
        'mime_type',
        'extention',
        'size_bytes',
        'checksum',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processingJobs(): HasMany
    {
        return $this->hasMany(ProcessingJobs::class, 'document_id');
    }
}

