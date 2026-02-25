<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $status = $request->input('status');

        $query = User::with('roles');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $users = $query->latest()->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'] ?? 'active',
        ]);

        // Assign roles if provided
        if (isset($validated['role_ids'])) {
            $user->syncRoles($validated['role_ids']);
        }

        $user->load('roles');

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user,
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        $user->load('roles.permissions');

        return response()->json([
            'user' => $user,
            'permissions' => $user->getAllPermissions(),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['exists:roles,id'],
        ]);

        // Update basic fields
        $user->update(collect($validated)->except(['role_ids'])->toArray());

        // Sync roles if provided
        if (isset($validated['role_ids'])) {
            $user->syncRoles($validated['role_ids']);
        }

        $user->load('roles');

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user,
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent deleting own account
        if (auth()->id() === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $user->assignRole($role);

        $user->load('roles');

        return response()->json([
            'message' => 'Role assigned successfully.',
            'user' => $user,
        ]);
    }

    /**
     * Remove a role from a user.
     */
    public function removeRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $user->removeRole($role);

        $user->load('roles');

        return response()->json([
            'message' => 'Role removed successfully.',
            'user' => $user,
        ]);
    }

    /**
     * Get all available roles.
     */
    public function getRoles(): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        return response()->json([
            'roles' => $roles,
        ]);
    }
}
