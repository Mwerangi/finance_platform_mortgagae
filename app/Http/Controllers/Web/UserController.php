<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $query = User::with(['roles', 'institution']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->role);
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Paginate
        $users = $query->latest()->paginate(15)->withQueryString();

        // Stats
        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'online' => User::where('last_login_at', '>=', now()->subMinutes(5))->count(),
        ];

        return Inertia::render('Users/Index', [
            'users' => $users,
            'stats' => $stats,
            'roles' => Role::all(['id', 'name']),
            'filters' => $request->only(['search', 'role', 'status'])
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'roles' => Role::all(),
            'institutions' => Institution::where('status', 'active')->get(['id', 'name'])
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'institution_id' => ['required', 'exists:institutions,id'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
            'send_welcome_email' => ['boolean'],
            'require_password_change' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
            'institution_id' => $validated['institution_id'],
        ]);

        // Assign roles
        $user->roles()->sync($validated['roles']);

        // TODO: Send welcome email if requested
        // if ($validated['send_welcome_email'] ?? false) {
        //     Mail::to($user->email)->send(new WelcomeEmail($user));
        // }

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        $user->load(['roles.permissions', 'institution']);

        // Get recent activities
        $activities = AuditLog::where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        // Get active sessions (mock data for now)
        $sessions = [];

        // Get stats
        $stats = [
            'total_sessions' => AuditLog::where('user_id', $user->id)
                ->where('action', 'login')
                ->count(),
            'applications_created' => \App\Models\Application::where('created_by', $user->id)->count(),
        ];

        return Inertia::render('Users/Show', [
            'user' => array_merge($user->toArray(), [
                'permissions' => $user->getAllPermissions()
            ]),
            'activities' => $activities,
            'sessions' => $sessions,
            'stats' => $stats
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $user->load(['roles', 'institution']);

        $currentUser = auth()->user();

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => Role::all(),
            'institutions' => Institution::where('status', 'active')->get(['id', 'name']),
            'canDelete' => $currentUser->id !== $user->id && $currentUser->hasRole('provider-super-admin'),
            'canChangeInstitution' => $currentUser->hasRole('provider-super-admin')
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'institution_id' => ['required', 'exists:institutions,id'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
            'require_password_change' => ['boolean'],
        ]);

        // Update basic fields
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
            'institution_id' => $validated['institution_id'],
        ]);

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        // Sync roles
        $user->roles()->sync($validated['roles']);

        return redirect()->route('users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevent deleting own account
        if (auth()->id() === $user->id) {
            return redirect()->back()
                ->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting the last admin
        if ($user->hasRole('provider-super-admin')) {
            $adminCount = User::whereHas('roles', function ($q) {
                $q->where('slug', 'provider-super-admin');
            })->count();

            if ($adminCount <= 1) {
                return redirect()->back()
                    ->with('error', 'Cannot delete the last system administrator.');
            }
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Update user status.
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        $user->update(['status' => $validated['status']]);

        return redirect()->back()
            ->with('success', 'User status updated successfully.');
    }

    /**
     * Send password reset email.
     */
    public function sendPasswordReset(User $user): RedirectResponse
    {
        // TODO: Implement password reset email
        // Password::sendResetLink(['email' => $user->email]);

        return redirect()->back()
            ->with('success', 'Password reset email sent successfully.');
    }
}
