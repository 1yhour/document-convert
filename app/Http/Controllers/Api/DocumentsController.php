<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Documents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentsController extends Controller
{
    /**
     * Display a listing of documents for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $documents = Documents::where('user_id', auth()->id())->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $documents,
        ]);
    }

    /**
     * Display the specified document.
     */
    public function show(Documents $document): JsonResponse
    {
        $this->authorize('view', $document);

        return response()->json([
            'status' => true,
            'data' => $document,
        ]);
    }

    /**
     * Download the specified document.
     */
    public function download(Documents $document): JsonResponse
    {
        $this->authorize('download', $document);

        return response()->json([
            'status' => true,
            'message' => 'Download authorized.',
            'data' => [
                'document_id' => $document->id,
                'stored_path' => $document->stored_path,
            ],
        ]);
    }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(Documents $document): JsonResponse
    {
        $this->authorize('delete', $document);

        $document->delete();

        return response()->json([
            'status' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }
}
