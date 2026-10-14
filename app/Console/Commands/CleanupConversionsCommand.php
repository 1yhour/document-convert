<?php

namespace App\Console\Commands;

use App\Models\Conversion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanupConversionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversions:cleanup {--hours= : Retention period in hours (defaults to config)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete local input/output files for conversions older than the retention period and clear paths';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = $this->option('hours') !== null
            ? (int) $this->option('hours')
            : (int) config('services.cloudconvert.retention_hours', 24);

        $cutoff = now()->subHours($hours);

        $conversions = Conversion::where('created_at', '<=', $cutoff)
            ->where(function ($query) {
                $query->whereNotNull('input_path')
                    ->orWhereNotNull('output_path');
            })
            ->get();

        if ($conversions->isEmpty()) {
            $this->info("No conversions found older than {$hours} hour(s) with files to clean.");

            return self::SUCCESS;
        }

        $this->info("Found {$conversions->count()} conversion(s) to clean up.");

        $disk = Storage::disk('local');
        $cleanedCount = 0;

        foreach ($conversions as $conversion) {
            try {
                if ($conversion->input_path && $disk->exists($conversion->input_path)) {
                    $disk->delete($conversion->input_path);
                }

                if ($conversion->output_path && $disk->exists($conversion->output_path)) {
                    $disk->delete($conversion->output_path);
                }

                $conversion->update([
                    'input_path' => null,
                    'output_path' => null,
                ]);

                $cleanedCount++;
            } catch (Throwable $e) {
                $this->error("Failed to clean files for conversion {$conversion->id}: {$e->getMessage()}");
            }
        }

        $this->info("Successfully cleaned up {$cleanedCount} conversion(s).");

        return self::SUCCESS;
    }
}
