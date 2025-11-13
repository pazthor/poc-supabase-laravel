<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentsController extends Controller
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Get all documents (filtered by team)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [];

        if ($request->has('team_id')) {
            $filters['team_id'] = 'eq.' . $request->input('team_id');
        }

        if ($request->has('employee_id')) {
            $filters['employee_id'] = 'eq.' . $request->input('employee_id');
        }

        if ($request->has('category')) {
            $filters['category'] = 'eq.' . $request->input('category');
        }

        $options = [
            'order' => $request->input('order', 'created_at.desc'),
            'limit' => $request->input('limit', 50),
        ];

        $response = $this->supabase->from('documents', $filters, $options);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch documents',
                'error' => $response->json()
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $response->json()
        ]);
    }

    /**
     * Get a single document
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $response = $this->supabase->from('documents', ['id' => 'eq.' . $id]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
                'error' => $response->json()
            ], 404);
        }

        $data = $response->json();

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data[0]
        ]);
    }

    /**
     * Upload a document
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|uuid',
            'employee_id' => 'nullable|uuid',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:performance_review,report,presentation,other',
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $teamId = $request->input('team_id');

        // Generate unique file path
        $fileName = Str::uuid() . '_' . $file->getClientOriginalName();
        $filePath = $teamId . '/' . $fileName;

        // Upload to Supabase Storage
        $uploadResponse = $this->supabase->uploadFile(
            'documents',
            $filePath,
            file_get_contents($file->getRealPath()),
            ['contentType' => $file->getMimeType()]
        );

        if ($uploadResponse->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed',
                'error' => $uploadResponse->json()
            ], 400);
        }

        // Get user ID from token
        $token = $request->bearerToken();
        $userResponse = $this->supabase->getUser($token);

        if ($userResponse->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'error' => $userResponse->json()
            ], 401);
        }

        $user = $userResponse->json();

        // Create document record
        $documentData = [
            'team_id' => $teamId,
            'employee_id' => $request->input('employee_id'),
            'uploaded_by' => $user['id'],
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'file_path' => $filePath,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'bucket_name' => 'documents',
            'category' => $request->input('category'),
        ];

        $docResponse = $this->supabase->insert('documents', $documentData);

        if ($docResponse->failed()) {
            // Cleanup: delete uploaded file if document record creation fails
            $this->supabase->deleteFile('documents', $filePath);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create document record',
                'error' => $docResponse->json()
            ], 400);
        }

        // Create activity log
        $this->supabase->insert('activity_logs', [
            'team_id' => $teamId,
            'user_id' => $user['id'],
            'action_type' => 'document_uploaded',
            'action_description' => 'Uploaded document: ' . $request->input('title'),
            'metadata' => [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'category' => $request->input('category'),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => $docResponse->json()
        ], 201);
    }

    /**
     * Get document download URL
     *
     * @param string $id
     * @return JsonResponse
     */
    public function download(string $id): JsonResponse
    {
        // Get document record
        $response = $this->supabase->from('documents', ['id' => 'eq.' . $id]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
                'error' => $response->json()
            ], 404);
        }

        $data = $response->json();

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        $document = $data[0];

        // Generate public URL (note: this requires bucket to be public or use signed URLs)
        $url = $this->supabase->getPublicUrl($document['bucket_name'], $document['file_path']);

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $url,
                'document' => $document,
            ]
        ]);
    }

    /**
     * Update document metadata
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|in:performance_review,report,presentation,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $response = $this->supabase->update(
            'documents',
            ['id' => 'eq.' . $id],
            $request->all()
        );

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document',
                'error' => $response->json()
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document updated successfully',
            'data' => $response->json()
        ]);
    }

    /**
     * Delete a document
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        // Get document record first to delete file
        $docResponse = $this->supabase->from('documents', ['id' => 'eq.' . $id]);

        if ($docResponse->failed() || empty($docResponse->json())) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        $document = $docResponse->json()[0];

        // Delete file from storage
        $this->supabase->deleteFile($document['bucket_name'], $document['file_path']);

        // Delete document record
        $response = $this->supabase->delete('documents', ['id' => 'eq.' . $id]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => $response->json()
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully'
        ]);
    }
}
