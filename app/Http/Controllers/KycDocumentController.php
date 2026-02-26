<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Enums\VerificationStatus;
use App\Models\Customer;
use App\Models\KycDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;

class KycDocumentController extends Controller
{
    /**
     * Display a listing of KYC documents for a customer.
     */
    public function index(Customer $customer): JsonResponse
    {
        $documents = $customer->kycDocuments()
            ->with(['verifier', 'rejecter'])
            ->latest()
            ->get();

        return response()->json([
            'documents' => $documents,
        ]);
    }

    /**
     * Upload a new KYC document for a customer.
     */
    public function store(Customer $customer, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', new Enum(DocumentType::class)],
            'file' => ['required', 'file', 'max:10240'], // 10MB max
            'document_number' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'description' => ['nullable', 'string'],
        ]);

        // Check if document type already exists for this customer
        $existingDocument = $customer->kycDocuments()
            ->where('document_type', $validated['document_type'])
            ->whereNull('deleted_at')
            ->first();

        if ($existingDocument) {
            return response()->json([
                'message' => 'This document type has already been uploaded. Please delete the existing document first or upload a different document type.',
                'errors' => [
                    'document_type' => ['A document of this type already exists. Please delete it first to upload a new one.']
                ]
            ], 422);
        }

        // Store the file
        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs(
            "kyc-documents/{$customer->institution_id}/{$customer->id}",
            $filename,
            'private'
        );

        // Create KYC document record
        $document = $customer->kycDocuments()->create([
            'institution_id' => $customer->institution_id,
            'document_type' => $validated['document_type'],
            'document_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType(),
            'document_number' => $validated['document_number'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'uploaded_by' => $request->user()->id,
            'verification_status' => VerificationStatus::PENDING,
        ]);

        return response()->json([
            'message' => 'KYC document uploaded successfully.',
            'document' => $document,
        ], 201);
    }

    /**
     * Display the specified KYC document.
     */
    public function show(KycDocument $kycDocument): JsonResponse
    {
        $kycDocument->load(['customer', 'verifier', 'rejecter', 'uploader']);

        return response()->json([
            'document' => $kycDocument,
        ]);
    }

    /**
     * Update the specified KYC document.
     */
    public function update(Request $request, KycDocument $kycDocument): JsonResponse
    {
        $validated = $request->validate([
            'document_number' => ['nullable', 'string', 'max:255'],
            'descriptiony_date' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string'],
        ]);

        $kycDocument->update($validated);

        return response()->json([
            'message' => 'KYC document updated successfully.',
            'document' => $kycDocument,
        ]);
    }

    /**
     * Remove the specified KYC document.
     */
    public function destroy(KycDocument $kycDocument): JsonResponse
    {
        // Delete the file from storage
        if (Storage::disk('private')->exists($kycDocument->file_path)) {
            Storage::disk('private')->delete($kycDocument->file_path);
        }

        $kycDocument->delete();

        return response()->json([
            'message' => 'KYC document deleted successfully.',
        ]);
    }

    /**
     * Verify a KYC document.
     */
    public function verify(KycDocument $kycDocument, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'verification_notes' => ['nullable', 'string'],
        ]);

        $kycDocument->verify(
            $request->user()->id,
            $validated['verification_notes'] ?? null
        );

        return response()->json([
            'message' => 'KYC document verified successfully.',
            'document' => $kycDocument->load('verifier'),
        ]);
    }

    /**
     * Reject a KYC document.
     */
    public function reject(KycDocument $kycDocument, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rejection_notes' => ['required', 'string'],
        ]);

        $kycDocument->reject(
            $request->user()->id,
            $validated['rejection_notes']
        );

        return response()->json([
            'message' => 'KYC document rejected.',
            'document' => $kycDocument->load('rejecter'),
        ]);
    }

    /**
     * Download a KYC document.
     */
    public function download(KycDocument $kycDocument)
    {
        if (!Storage::disk('private')->exists($kycDocument->file_path)) {
            return response()->json([
                'message' => 'File not found.',
            ], 404);
        }

        return Storage::disk('private')->download(
            $kycDocument->file_path,
            $kycDocument->file_name
        );
    }
}
