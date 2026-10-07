<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AccountManagementController extends Controller
{
    public function index(Request $request): View
    {
        $currentAdmin = Auth::guard('admin')->user();

        $admins = $currentAdmin->is_super_admin
            ? $this->filteredAdmins($request)
            : collect();

        $loginSessions = $currentAdmin->loginSessions()->limit(100)->get();

        return view('admin.account-management.index', compact('currentAdmin', 'admins', 'loginSessions'));
    }

    public function table(Request $request): View
    {
        $admins = $this->filteredAdmins($request);

        return view('admin.account-management.partials.admins-table', compact('admins'));
    }

    private function filteredAdmins(Request $request)
    {
        $query = User::where('role', 'admin')->where('is_super_admin', false);

        if ($request->status === 'archived') {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('account_status', $request->status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        return $query->latest()->get();
    }

    public function show(User $admin): View
    {
        $this->authorizeSuperAdmin();

        return view('admin.account-management.show', compact('admin'));
    }

    public function create(): View
    {
        $this->authorizeSuperAdmin();

        return view('admin.account-management.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:5'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'email_username' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9._-]+$/',
            ],
        ], [
            'email_username.regex' => 'Username can only contain letters, numbers, dots, dashes, and underscores. The @vendo-ph.app part is fixed and cannot be changed.',
        ]);

        $validated['email'] = strtolower($validated['email_username']) . '@vendo-ph.app';

        $request->validate([
            'email' => ['unique:users,email'],
        ], [
            'email.unique' => 'This email is already taken by another admin.',
        ]);

        $temporaryPassword = \Illuminate\Support\Str::password(12);

        $newAdmin = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'middle_initial' => $validated['middle_initial'] ?? null,
            'name' => trim($validated['first_name'] . ' ' . ($validated['middle_initial'] ? $validated['middle_initial'] . '. ' : '') . $validated['last_name']),
            'phone_number' => $validated['phone_number'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($temporaryPassword),
            'role' => 'admin',
            'status' => 'approved',
            'is_super_admin' => false,
            'must_change_password' => true,
            'temp_password_plain' => $temporaryPassword,
        ]);

        return redirect()->route('admin.account-management.show', $newAdmin)
            ->with('confirmation', 'created')
            ->with('generated_email', $validated['email'])
            ->with('generated_password', $temporaryPassword);
    }

    public function update(Request $request, User $admin): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:5'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'recovery_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $admin->update(array_merge($validated, [
            'name' => trim($validated['first_name'] . ' ' . ($validated['middle_initial'] ? $validated['middle_initial'] . '. ' : '') . $validated['last_name']),
        ]));

        return back()->with('confirmation', 'admin-updated');
    }

    public function suspend(User $admin): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        abort_if($admin->is_super_admin, 403);

        $admin->update(['account_status' => 'suspended']);

        return back()->with('confirmation', 'suspended');
    }

    public function reactivate(User $admin): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $admin->update(['account_status' => 'active']);

        return back()->with('confirmation', 'reactivated');
    }

    public function destroy(User $admin): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        abort_unless($admin->role === 'admin', 404);
        abort_if($admin->is_super_admin, 403);

        $admin->update(['archived_at' => now()]);

        return redirect()
            ->route('admin.account-management.index', ['tab' => 'admin-accounts', 'status' => 'archived'])
            ->with('confirmation', 'admin-archived');
    }

    public function restore(Request $request, User $admin): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $admin->update(['archived_at' => null, 'account_status' => 'active']);

        return redirect()
            ->route('admin.account-management.index', ['tab' => 'admin-accounts'])
            ->with('confirmation', 'admin-restored');
    }

    public function forceDelete(Request $request, User $admin): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        abort_if($admin->is_super_admin, 403);

        $admin->delete();

        return redirect()
            ->route('admin.account-management.index', ['tab' => 'admin-accounts'])
            ->with('confirmation', 'admin-deleted');
    }

    public function sendResetLink(User $admin): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        if (empty($admin->recovery_email)) {
            return back()->with('error', 'This admin has not set a secondary/recovery email yet. Ask them to add one first.');
        }

        Password::sendResetLink(['email' => $admin->email]);

        return back()->with('confirmation', 'reset-link-sent');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:5'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'recovery_email' => ['nullable', 'string', 'email', 'max:255'],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
        ]);

        $admin->fill($validated);
        $admin->name = trim($validated['first_name'] . ' ' . ($validated['middle_initial'] ? $validated['middle_initial'] . '. ' : '') . $validated['last_name']);

        if ($request->hasFile('profile_picture')) {
            $admin->profile_picture = $request->file('profile_picture')->store('profile-pictures', 'public');
        }

        $admin->save();

        return back()->with('confirmation', 'profile-updated');
    }

    public function showForcePassword(): View
    {
        return view('admin.account-management.force-password');
    }

    public function storeForcePassword(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'temp_password_plain' => null,
        ]);

        return redirect()->route('admin.dashboard')->with('confirmation', 'password-set');
    }

    public function viewTempPassword(User $admin): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        if (! $admin->must_change_password || empty($admin->temp_password_plain)) {
            return back()->with('error', 'No temporary password available. The admin may have already set their own password.');
        }

        return back()
            ->with('generated_email', $admin->email)
            ->with('generated_password', $admin->temp_password_plain);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $admin->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $admin->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        return back()->with('confirmation', 'password-updated');
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(Auth::guard('admin')->user()?->is_super_admin, 403, 'Only the super admin can manage admin accounts.');
    }

    public function checkStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        if ($admin && ($admin->account_status !== 'active' || $admin->archived_at)) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json(['active' => false]);
        }

        if (! $admin) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json(['active' => false]);
        }

        return response()->json(['active' => true]);
    }
}
