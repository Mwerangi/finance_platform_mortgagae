<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InstitutionController extends Controller
{
    /**
     * Display a listing of institutions (Provider Super Admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $status = $request->input('status');

        $query = Institution::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $institutions = $query->latest()->paginate($perPage);

        return response()->json($institutions);
    }

    /**
     * Store a newly created institution.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'unique:institutions,slug'],
            'code' => ['sometimes', 'string', 'unique:institutions,code'],
            'description' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string'],
            'currency' => ['nullable', 'string'],
            'date_format' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        // Auto-generate slug if not provided
        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $institution = Institution::create($validated);

        if ($institution->status === 'active') {
            $institution->update(['activated_at' => now()]);
        }

        return response()->json([
            'message' => 'Institution created successfully.',
            'institution' => $institution,
        ], 201);
    }

    /**
     * Display the specified institution.
     */
    public function show(Institution $institution): JsonResponse
    {
        return response()->json([
            'institution' => $institution,
            'branding' => $institution->getBrandingConfig(),
            'settings' => $institution->getSettingsConfig(),
        ]);
    }

    /**
     * Update the specified institution.
     */
    public function update(Request $request, Institution $institution): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', Rule::unique('institutions')->ignore($institution->id)],
            'code' => ['sometimes', 'string', Rule::unique('institutions')->ignore($institution->id)],
            'description' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string'],
            'currency' => ['nullable', 'string'],
            'date_format' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        $institution->update($validated);

        return response()->json([
            'message' => 'Institution updated successfully.',
            'institution' => $institution,
        ]);
    }

    /**
     * Remove the specified institution.
     */
    public function destroy(Institution $institution): JsonResponse
    {
        $institution->delete();

        return response()->json([
            'message' => 'Institution deleted successfully.',
        ]);
    }

    /**
     * Get branding configuration for the institution.
     */
    public function getBranding(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Provider Super Admin can specify institution, others get their own
        if ($user->hasRole('provider-super-admin') && $request->has('institution_id')) {
            $institution = Institution::findOrFail($request->institution_id);
        } else {
            $institution = $user->institution;
        }

        if (!$institution) {
            return response()->json([
                'message' => 'No institution associated with this user.',
            ], 404);
        }

        return response()->json([
            'branding' => $institution->getBrandingConfig(),
        ]);
    }

    /**
     * Update branding configuration.
     */
    public function updateBranding(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Provider Super Admin can specify institution, others update their own
        if ($user->hasRole('provider-super-admin') && $request->has('institution_id')) {
            $institution = Institution::findOrFail($request->institution_id);
        } else {
            $institution = $user->institution;
        }

        if (!$institution) {
            return response()->json([
                'message' => 'No institution associated with this user.',
            ], 404);
        }

        $validated = $request->validate([
            'logo_url' => ['nullable', 'string'],
            'favicon_url' => ['nullable', 'string'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'custom_domain' => ['nullable', 'string'],
            'email_from_name' => ['nullable', 'string'],
            'email_from_address' => ['nullable', 'email'],
        ]);

        $institution->updateBranding($validated);

        return response()->json([
            'message' => 'Branding updated successfully.',
            'branding' => $institution->getBrandingConfig(),
        ]);
    }

    /**
     * Upload logo for institution.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Provider Super Admin can specify institution, others update their own
        if ($user->hasRole('provider-super-admin') && $request->has('institution_id')) {
            $institution = Institution::findOrFail($request->institution_id);
        } else {
            $institution = $user->institution;
        }

        if (!$institution) {
            return response()->json([
                'message' => 'No institution associated with this user.',
            ], 404);
        }

        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'], // 2MB max
            'type' => ['sometimes', Rule::in(['logo', 'favicon'])],
        ]);

        $type = $request->input('type', 'logo');
        $file = $request->file('logo');
        
        // Store file
        $path = $file->store("institutions/{$institution->id}/branding", 'public');
        $url = "/storage/{$path}";

        // Update branding
        $key = $type === 'favicon' ? 'favicon_url' : 'logo_url';
        $institution->updateBranding([$key => $url]);

        return response()->json([
            'message' => ucfirst($type) . ' uploaded successfully.',
            'url' => $url,
            'branding' => $institution->getBrandingConfig(),
        ]);
    }

    /**
     * Get settings for the institution.
     */
    public function getSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->hasRole('provider-super-admin') && $request->has('institution_id')) {
            $institution = Institution::findOrFail($request->institution_id);
        } else {
            $institution = $user->institution;
        }

        if (!$institution) {
            return response()->json([
                'message' => 'No institution associated with this user.',
            ], 404);
        }

        return response()->json([
            'settings' => $institution->getSettingsConfig(),
        ]);
    }

    /**
     * Update settings for the institution.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->hasRole('provider-super-admin') && $request->has('institution_id')) {
            $institution = Institution::findOrFail($request->institution_id);
        } else {
            $institution = $user->institution;
        }

        if (!$institution) {
            return response()->json([
                'message' => 'No institution associated with this user.',
            ], 404);
        }

        $validated = $request->validate([
            'features' => ['nullable', 'array'],
            'loan_account_prefix' => ['nullable', 'string', 'max:10'],
            'customer_id_prefix' => ['nullable', 'string', 'max:10'],
            'max_file_size_mb' => ['nullable', 'integer', 'min:1', 'max:200'],
            'allowed_file_types' => ['nullable', 'array'],
            'require_kyc_verification' => ['nullable', 'boolean'],
            'auto_run_analytics' => ['nullable', 'boolean'],
        ]);

        $institution->updateSettings($validated);

        return response()->json([
            'message' => 'Settings updated successfully.',
            'settings' => $institution->getSettingsConfig(),
        ]);
    }

    /**
     * Toggle institution status (activate/deactivate).
     */
    public function toggleStatus(Institution $institution): JsonResponse
    {
        if ($institution->isActive()) {
            $institution->deactivate();
            $message = 'Institution deactivated successfully.';
        } else {
            $institution->activate();
            $message = 'Institution activated successfully.';
        }

        return response()->json([
            'message' => $message,
            'institution' => $institution,
        ]);
    }
}
