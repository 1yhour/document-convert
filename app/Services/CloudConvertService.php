<?php

namespace App\Services;

use CloudConvert\CloudConvert;
use CloudConvert\Models\Job;
use CloudConvert\Models\Task;
use CloudConvert\Models\WebhookEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CloudConvertService
{
    protected CloudConvert $client;

    public function __construct(?CloudConvert $client = null)
    {
        if ($client !== null) {
            $this->client = $client;
        } else {
            $apiKey = (string) config('services.cloudconvert.api_key', '');
            $sandbox = (bool) config('services.cloudconvert.sandbox', false);
            $this->client = new CloudConvert([
                'api_key' => $apiKey ?: 'test_api_key',
                'sandbox' => $sandbox,
            ]);
        }
    }

    /**
     * Create a CloudConvert job with upload, convert, and export tasks.
     */
    public function createJob(string $tag, string $inputFormat, string $outputFormat): Job
    {
        $job = (new Job)
            ->setTag($tag)
            ->addTask(new Task('import/upload', 'upload-task'))
            ->addTask(
                (new Task('convert', 'convert-task'))
                    ->set('input', 'upload-task')
                    ->set('input_format', $inputFormat)
                    ->set('output_format', $outputFormat)
            )
            ->addTask(
                (new Task('export/url', 'export-task'))
                    ->set('input', 'convert-task')
            );

        return $this->client->jobs()->create($job);
    }

    /**
     * Upload a file to CloudConvert using the import/upload task from the created job.
     *
     * @param  resource|string  $fileStreamOrPath
     */
    public function uploadFile(Job $job, $fileStreamOrPath, string $filename): void
    {
        $uploadTasks = $job->getTasks() ? $job->getTasks()->whereOperation('import/upload') : [];
        $uploadTask = $uploadTasks[0] ?? null;

        if (! $uploadTask) {
            throw new \RuntimeException('No import/upload task found on CloudConvert job.');
        }

        $this->client->tasks()->upload($uploadTask, $fileStreamOrPath, $filename);
    }

    /**
     * Fetch a job by ID from CloudConvert.
     */
    public function getJob(string $jobId): Job
    {
        return $this->client->jobs()->get($jobId);
    }

    /**
     * Verify the webhook signature against the raw payload and parse the event.
     */
    public function verifyAndParseWebhook(string $payload, string $signature): WebhookEvent
    {
        $signingSecret = (string) config('services.cloudconvert.webhook_secret', '');

        return $this->client->webhookHandler()->constructEvent($payload, $signature, $signingSecret);
    }

    /**
     * Extract the export URL for converted file from CloudConvert Job.
     */
    public function getExportUrl(Job $job): ?string
    {
        $exportUrls = $job->getExportUrls();
        if (! empty($exportUrls) && isset($exportUrls[0]->url)) {
            return $exportUrls[0]->url;
        }

        if ($job->getTasks()) {
            foreach ($job->getTasks()->whereOperation('export/url') as $task) {
                if ($task->getResult() && ! empty($task->getResult()->files)) {
                    return $task->getResult()->files[0]->url ?? null;
                }
            }
        }

        return null;
    }

    /**
     * Download the exported converted file into private disk storage.
     */
    public function downloadExportFile(string $exportUrl, string $destinationPath, string $disk = 'local'): bool
    {
        $fullPath = Storage::disk($disk)->path($destinationPath);
        $directory = dirname($fullPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $response = Http::timeout(120)->sink($fullPath)->get($exportUrl);

        if (! $response->successful()) {
            Log::error('Failed to download exported file from CloudConvert', [
                'status' => $response->status(),
                'destination' => $destinationPath,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Get underlying CloudConvert client.
     */
    public function getClient(): CloudConvert
    {
        return $this->client;
    }
}
