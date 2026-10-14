<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversion;
use App\Services\CloudConvertService;
use CloudConvert\Exceptions\SignatureVerificationException;
use CloudConvert\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CloudConvertWebhookController extends Controller
{
    /**
     * Handle incoming CloudConvert signed webhooks.
     */
    public function handle(Request $request, CloudConvertService $cloudConvertService): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('CloudConvert-Signature', '');

        if (empty($signature) || empty($payload)) {
            return response()->json(['message' => 'Missing signature or payload.'], 400);
        }

        try {
            $event = $cloudConvertService->verifyAndParseWebhook($payload, $signature);
        } catch (SignatureVerificationException $e) {
            return response()->json(['message' => 'Invalid webhook signature.'], 400);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Invalid webhook payload.'], 400);
        }

        $job = $event->getJob();
        if (! $job) {
            return response()->json(['message' => 'Event does not contain job data.'], 200);
        }

        // Find the Conversion by cloudconvert_job_id or tag
        $conversion = Conversion::where('cloudconvert_job_id', $job->getId())->first();
        if (! $conversion && $job->getTag()) {
            $conversion = Conversion::find($job->getTag());
        }

        // Ignore unknown jobs without crashing
        if (! $conversion) {
            Log::info('CloudConvert webhook received for unknown job', [
                'job_id' => $job->getId(),
                'tag' => $job->getTag(),
            ]);

            return response()->json(['message' => 'Job not found, event ignored.'], 200);
        }

        // Idempotency: CloudConvert retries webhooks, so ignore events for a conversion already completed or failed
        if (in_array($conversion->status, ['completed', 'failed'])) {
            return response()->json(['message' => 'Conversion already processed.'], 200);
        }

        $eventType = $event->getEvent();

        if ($eventType === WebhookEvent::EVENT_JOB_FINISHED) {
            // Milestone: Webhook completion sets 90 then 100
            $conversion->update(['progress' => 90]);

            $exportUrl = $cloudConvertService->getExportUrl($job);
            if (! $exportUrl) {
                Log::error('No export URL found for finished CloudConvert job', [
                    'conversion_id' => $conversion->id,
                    'job_id' => $job->getId(),
                ]);

                $conversion->update([
                    'status' => 'failed',
                    'error_message' => 'Failed to retrieve converted file.',
                ]);

                return response()->json(['message' => 'Export URL missing.'], 200);
            }

            $operations = config('services.cloudconvert.operations', []);
            $outputFormat = $operations[$conversion->operation]['output_format'] ?? 'docx';
            $outputPath = 'conversions/outputs/'.Str::uuid().'.'.$outputFormat;

            $downloaded = $cloudConvertService->downloadExportFile($exportUrl, $outputPath, 'local');

            if (! $downloaded) {
                $conversion->update([
                    'status' => 'failed',
                    'error_message' => 'Failed to download converted file from converter service.',
                ]);

                return response()->json(['message' => 'File download failed.'], 200);
            }

            $conversion->update([
                'output_path' => $outputPath,
                'status' => 'completed',
                'progress' => 100,
                'completed_at' => now(),
            ]);

            return response()->json(['message' => 'Conversion completed successfully.'], 200);
        }

        if ($eventType === WebhookEvent::EVENT_JOB_FAILED) {
            $conversion->update([
                'status' => 'failed',
                'progress' => 100,
                'error_message' => 'Conversion failed on CloudConvert service.',
            ]);

            return response()->json(['message' => 'Conversion marked as failed.'], 200);
        }

        return response()->json(['message' => 'Event ignored.'], 200);
    }
}
