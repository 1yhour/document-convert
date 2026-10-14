<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessConversionJob;
use App\Models\Conversion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConversionController extends Controller
{
    /**
     * Get allowed operations from config for frontend dropdown.
     */
    public function operations(): JsonResponse
    {
        $operations = config('services.cloudconvert.operations', []);
        $formatted = [];

        foreach ($operations as $key => $config) {
            $formatted[] = [
                'operation' => $key,
                'label' => $config['label'] ?? $key,
                'input_format' => $config['input_format'],
                'output_format' => $config['output_format'],
                'allowed_extensions' => $config['allowed_extensions'] ?? [],
                'allowed_mime_types' => $config['allowed_mime_types'] ?? [],
                'max_file_size_kb' => $config['max_file_size_kb'] ?? 10240,
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $formatted,
        ]);
    }

    /**
     * List only the current user's conversions, paginated.
     */
    public function index(Request $request): JsonResponse
    {
        $conversions = Conversion::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => true,
            'data' => $conversions,
        ]);
    }

    /**
     * Store and dispatch a newly requested conversion.
     */
    public function store(Request $request): JsonResponse
    {
        $operations = config('services.cloudconvert.operations', []);
        $allowedOperationKeys = array_keys($operations);

        $request->validate([
            'operation' => ['required', 'string', Rule::in($allowedOperationKeys)],
        ]);

        $operation = $request->input('operation');
        $opConfig = $operations[$operation];

        $allowedExtensions = $opConfig['allowed_extensions'] ?? ['pdf'];
        $allowedMimes = $opConfig['allowed_mime_types'] ?? ['application/pdf'];
        $maxSizeKb = $opConfig['max_file_size_kb'] ?? 10240;

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.$maxSizeKb,
                'mimes:'.implode(',', $allowedExtensions),
            ],
        ]);

        $file = $request->file('file');

        // Check mime type against config allow-list
        if (! empty($allowedMimes) && ! in_array($file->getMimeType(), $allowedMimes)) {
            throw ValidationException::withMessages([
                'file' => ['The file mime type ('.$file->getMimeType().') is not supported for this operation.'],
            ]);
        }

        // Store file on private disk with a random filename (never user's filename)
        $inputPath = $file->store('conversions/inputs', 'local');

        $conversion = Conversion::create([
            'user_id' => $request->user()->id,
            'operation' => $operation,
            'original_name' => $file->getClientOriginalName(),
            'input_path' => $inputPath,
            'status' => 'pending',
            'progress' => 0,
        ]);

        ProcessConversionJob::dispatch($conversion);

        return response()->json([
            'status' => true,
            'message' => 'Conversion queued successfully.',
            'data' => [
                'id' => $conversion->id,
                'operation' => $conversion->operation,
                'original_name' => $conversion->original_name,
                'status' => $conversion->status,
                'progress' => $conversion->progress,
            ],
        ], 202);
    }

    /**
     * Display the specified conversion status and progress.
     */
    public function show(Conversion $conversion): JsonResponse
    {
        $this->authorize('view', $conversion);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $conversion->id,
                'operation' => $conversion->operation,
                'original_name' => $conversion->original_name,
                'status' => $conversion->status,
                'progress' => $conversion->progress,
                'error_message' => $conversion->error_message,
                'completed_at' => $conversion->completed_at,
                'created_at' => $conversion->created_at,
            ],
        ]);
    }

    /**
     * Download the converted file from private storage.
     */
    public function download(Conversion $conversion): StreamedResponse|JsonResponse
    {
        $this->authorize('download', $conversion);

        if ($conversion->status !== 'completed') {
            return response()->json([
                'status' => false,
                'message' => 'Conversion is not completed yet.',
            ], 400);
        }

        if (empty($conversion->output_path) || ! Storage::disk('local')->exists($conversion->output_path)) {
            return response()->json([
                'status' => false,
                'message' => 'Converted file not found in storage.',
            ], 404);
        }

        $operations = config('services.cloudconvert.operations', []);
        $outputFormat = $operations[$conversion->operation]['output_format'] ?? 'docx';
        $downloadFilename = pathinfo($conversion->original_name, PATHINFO_FILENAME).'.'.$outputFormat;

        return Storage::disk('local')->download($conversion->output_path, $downloadFilename);
    }
}
