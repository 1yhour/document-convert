<?php

namespace App\Jobs;

use App\Models\Conversion;
use App\Services\CloudConvertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(public Conversion $conversion) {}

    /**
     * Execute the job.
     */
    public function handle(CloudConvertService $cloudConvertService): void
    {
        $this->conversion->refresh();

        if (in_array($this->conversion->status, ['completed', 'failed'])) {
            return;
        }

        $operations = config('services.cloudconvert.operations', []);
        $operationConfig = $operations[$this->conversion->operation] ?? null;

        if (! $operationConfig) {
            $this->conversion->update([
                'status' => 'failed',
                'error_message' => 'Invalid or unsupported conversion operation.',
            ]);

            return;
        }

        $inputFormat = $operationConfig['input_format'];
        $outputFormat = $operationConfig['output_format'];

        try {
            // Milestone 1: Job created on CloudConvert (10%)
            $job = $cloudConvertService->createJob($this->conversion->id, $inputFormat, $outputFormat);

            $this->conversion->update([
                'cloudconvert_job_id' => $job->getId(),
                'status' => 'uploading',
                'progress' => 10,
            ]);

            // Milestone 2: Upload file from private storage to CloudConvert (30%)
            $disk = 'local';
            if (! Storage::disk($disk)->exists($this->conversion->input_path)) {
                throw new \RuntimeException("Input file not found in storage: {$this->conversion->input_path}");
            }

            $stream = Storage::disk($disk)->readStream($this->conversion->input_path);
            try {
                $cloudConvertService->uploadFile($job, $stream, $this->conversion->original_name);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            // Milestone 3: File uploaded and processing commenced (30% -> 50%)
            $this->conversion->update([
                'status' => 'processing',
                'progress' => 30,
            ]);

            $this->conversion->update([
                'status' => 'processing',
                'progress' => 50,
            ]);
        } catch (Throwable $e) {
            Log::error("ProcessConversionJob failed for conversion {$this->conversion->id}: {$e->getMessage()}", [
                'conversion_id' => $this->conversion->id,
                'exception' => $e,
            ]);

            $this->conversion->update([
                'status' => 'failed',
                'error_message' => 'Conversion failed during processing. Please try again later.',
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("ProcessConversionJob permanently failed for conversion {$this->conversion->id}", [
            'conversion_id' => $this->conversion->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->conversion->update([
            'status' => 'failed',
            'error_message' => 'Conversion processing permanently failed. Please try again later.',
        ]);
    }
}
