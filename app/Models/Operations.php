<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operations extends Model
{
    /** @use HasFactory<\Database\Factories\OperationsFactory> */
    use HasFactory, HasUuids;

    protected $table = 'operations';

    protected $fillable = [
        'code',
        'name',
        'input_format',
        'output_format',
        'cc_convert_engine',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function processingJobs(): HasMany
    {
        return $this->hasMany(ProcessingJobs::class, 'operation_id');
    }
}

