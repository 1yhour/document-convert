<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProcessingJobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcessingJobsController extends Controller
{
    /**
     * Display a listing of the resource.
     * List queries for normal users must be scoped to where('user_id', auth()->id()).
     */
    public function index(): JsonResponse
    {
        $jobs = ProcessingJobs::where('user_id', auth()->id())
            ->with(['document', 'operation', 'result'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $jobs,
        ]);
    }

    /**
     * Store a newly created conversion job in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_id' => 'required|uuid|exists:documents,id',
            'operation_id' => 'required|uuid|exists:operations,id',
        ]);

        $job = ProcessingJobs::create([
            'document_id' => $validated['document_id'],
            'operation_id' => $validated['operation_id'],
            'user_id' => auth()->id(),
            'status' => 'pending',
            'progress' => 0,
            'attempts' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Conversion job created successfully.',
            'data' => $job,
        ], 201);
    }

    /**
     * Display the specified conversion job (progress endpoint).
     * Authorizes view ownership.
     */
    public function show(ProcessingJobs $processingJob): JsonResponse
    {
        $this->authorize('view', $processingJob);

        $processingJob->load(['document', 'operation', 'result', 'statusHistories']);

        return response()->json([
            'status' => true,
            'data' => $processingJob,
        ]);
    }

    /**
     * Download the converted file for the specified processing job.
     * Authorizes download ownership.
     */
    public function download(ProcessingJobs $processingJob): JsonResponse
    {
        $this->authorize('download', $processingJob);

        $result = $processingJob->result;

        return response()->json([
            'status' => true,
            'message' => 'Download authorized.',
            'data' => [
                'job_id' => $processingJob->id,
                'result_path' => $result?->result_path,
                'external_document_url' => $result?->external_document_url,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProcessingJobs $processingJob): JsonResponse
    {
        $this->authorize('delete', $processingJob);

        $processingJob->delete();

        return response()->json([
            'status' => true,
            'message' => 'Job deleted successfully.',
        ]);
    }
}
