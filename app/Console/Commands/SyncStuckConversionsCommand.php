<?php

namespace App\Console\Commands;

use App\Models\Conversion;
use App\Services\CloudConvertService;
use CloudConvert\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SyncStuckConversionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversions:sync-stuck {--minutes=15 : Number of minutes after which a conversion is considered stuck}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize stuck conversions with CloudConvert in case webhooks were missed';

    /**
     * Execute the console command.
     */
    public function handle(CloudConvertService $cloudConvertService): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff = now()->subMinutes($minutes);

        $stuckConversions = Conversion::whereIn('status', ['uploading', 'processing'])
            ->where('updated_at', '<=', $cutoff)
            ->whereNotNull('cloudconvert_job_id')
            ->get();

        if ($stuckConversions->isEmpty()) {
            $this->info("No stuck conversions found (older than {$minutes} minutes).");

            return self::SUCCESS;
        }

        $this->info("Found {$stuckConversions->count()} potentially stuck conversion(s). Synchronizing...");

        foreach ($stuckConversions as $conversion) {
            try {
                $job = $cloudConvertService->getJob($conversion->cloudconvert_job_id);
                $status = $job->getStatus();

                if ($status === Job::STATUS_FINISHED) {
                    $exportUrl = $cloudConvertService->getExportUrl($job);
                    if ($exportUrl) {
                        $operations = config('services.cloudconvert.operations', []);
                        $outputFormat = $operations[$conversion->operation]['output_format'] ?? 'docx';
                        $outputPath = 'conversions/outputs/'.Str::uuid().'.'.$outputFormat;

                        $downloaded = $cloudConvertService->downloadExportFile($exportUrl, $outputPath, 'local');
                        if ($downloaded) {
                            $conversion->update([
                                'output_path' => $outputPath,
                                'status' => 'completed',
                                'progress' => 100,
                                'completed_at' => now(),
                            ]);
                            $this->info("Conversion {$conversion->id} marked completed.");

                            continue;
                        }
                    }

                    $conversion->update([
                        'status' => 'failed',
                        'error_message' => 'Failed to download converted file from converter service.',
                    ]);
                    $this->warn("Conversion {$conversion->id} export download failed.");
                } elseif ($status === Job::STATUS_ERROR) {
                    $conversion->update([
                        'status' => 'failed',
                        'progress' => 100,
                        'error_message' => 'Conversion failed on CloudConvert service.',
                    ]);
                    $this->warn("Conversion {$conversion->id} marked failed.");
                } else {
                    $this->line("Conversion {$conversion->id} still in state: {$status}");
                }
            } catch (Throwable $e) {
                Log::error("Failed to sync stuck conversion {$conversion->id}", [
                    'cloudconvert_job_id' => $conversion->cloudconvert_job_id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Error syncing conversion {$conversion->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
