<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\ActivityLog;
use App\Models\FieldAgent;
use App\Models\SaleItem;
use App\Models\CommissionDisbursement;
use App\Imports\UsersImport;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\LoginCredentialsMail;
use App\Helpers\SmsHelper;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();

        // Field agents only see their own profile; redirect to profile
        if ($currentUser->fieldAgentProfile && $currentUser->branch_id) {
            return redirect()->route('profile.show');
        }

        // Get customer role ID to exclude customers
        $customerRole = Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : null;

        // Restrict to current user's branch and branches below (when user has a branch)
        $allowedBranchIds = null;
        if ($currentUser->branch_id) {
            $allowedBranchIds = \App\Models\Branch::selfAndDescendantIds($currentUser->branch_id);
        }

        $query = User::with(['branch', 'roleModel', 'fieldAgentProfile']);

        // Exclude customers by role_id if available, otherwise fallback to role enum
        if ($customerRoleId) {
            $query->where('role_id', '!=', $customerRoleId)->whereNotNull('role_id');
        } else {
            $query->whereNotIn('role', ['customer']);
        }

        if ($allowedBranchIds !== null) {
            $query->whereIn('branch_id', $allowedBranchIds);
        }

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%");
            });
        }
        if ($request->filled('branch')) {
            $branchFilter = $request->get('branch');
            if ($allowedBranchIds === null || in_array($branchFilter, $allowedBranchIds, true)) {
                $query->where('branch_id', $branchFilter);
            }
        }
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->get('role_id'));
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        // Calculate stats (same branch scope)
        $baseQuery = User::query();
        if ($customerRoleId) {
            $baseQuery->where('role_id', '!=', $customerRoleId)->whereNotNull('role_id');
        } else {
            $baseQuery->whereNotIn('role', ['customer']);
        }
        if ($allowedBranchIds !== null) {
            $baseQuery->whereIn('branch_id', $allowedBranchIds);
        }

        // Get admin and staff role IDs
        $adminRole = Role::where('slug', 'admin')->first();
        $staffRoles = Role::whereIn('slug', ['head_branch_manager', 'regional_branch_manager', 'staff'])->pluck('id');

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereNotNull('branch_id')->count(),
            'admins' => $adminRole ? (clone $baseQuery)->where('role_id', $adminRole->id)->count() : 0,
            'staff' => $staffRoles->isNotEmpty() ? (clone $baseQuery)->whereIn('role_id', $staffRoles)->count() : 0,
        ];

        // Branch filter: only show branches the user is allowed to see
        $branchesQuery = \App\Models\Branch::where('is_active', true)->orderBy('name');
        if ($allowedBranchIds !== null) {
            $branchesQuery->whereIn('id', $allowedBranchIds);
        }
        $branches = $branchesQuery->get(['id', 'name']);
        $roles = Role::where('is_active', true)->where('slug', '!=', 'customer')->orderBy('name')->get(['id', 'name']);

        return view('users.index', compact('users', 'stats', 'branches', 'roles'));
    }

    public function create()
    {
        $query = Role::where('is_active', true);

        // Non-admin users cannot assign admin role
        if (!Auth::user()->isAdmin()) {
            $query->where('slug', '!=', 'admin');
        }

        $roles = $query->orderBy('name')->get();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email|required_without:phone',
            'phone' => 'nullable|string|max:255|required_without:email',
            'role_id' => 'required|exists:roles,id',
        ], [
            'email.required_without' => 'Either email or phone is required.',
            'phone.required_without' => 'Either email or phone is required.',
        ]);

        // Get the role to set both role_id and role (for backward compatibility)
        $role = Role::findOrFail($validated['role_id']);

        // Prevent non-admin users from assigning admin role
        if ($role->slug === 'admin' && !Auth::user()->isAdmin()) {
            return back()->withErrors(['role_id' => 'You do not have permission to assign the admin role.'])
                ->withInput();
        }

        $validated['role'] = $this->roleSlugToEnum($role->slug);

        // Generate a random password and set for credentials email (non-customers receive it via User model created event)
        $plainPassword = $this->generateRandomPassword();
        $validated['password'] = Hash::make($plainPassword);

        // Automatically assign to the logged-in user's branch
        $validated['branch_id'] = $request->user()->branch_id;

        $validated['email'] = $validated['email'] ?? null;
        $validated['phone'] = $validated['phone'] ?? null;

        // So that the User model can email credentials to non-customers on create (only if they have email)
        User::$plainPasswordForNewUser = $plainPassword;
        $newUser = User::create($validated);

        $identifier = $newUser->email ?? $newUser->phone ?? '—';
        ActivityLog::log(
            Auth::id(),
            'user_created',
            "Created user: {$newUser->name} ({$identifier})",
            User::class,
            $newUser->id,
            ['user_name' => $newUser->name, 'user_email' => $newUser->email, 'user_phone' => $newUser->phone]
        );

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $currentUser = Auth::user();
        // Field agents may only view their own profile
        if ($currentUser->fieldAgentProfile && $currentUser->branch_id) {
            if ($user->id !== $currentUser->id) {
                return redirect()->route('profile.show');
            }
        } elseif (!User::visibleToUser($user, $currentUser)) {
            abort(403, 'You do not have access to this user.');
        }
        $user->load(['branch', 'roleModel', 'sales', 'assignedTickets']);
        $activityLogs = $user->activityLogs()->with('model')->latest()->paginate(20);

        $saleIds = $user->sales->pluck('id')->all();
        $revenue = $user->sales->sum('total');
        $totalBuyingPrice = \App\Models\Sale::totalBuyingPriceForSaleIds($saleIds);
        $costToSell = $totalBuyingPrice + $user->sales->sum('total_license_cost');
        $salesStats = [
            'revenue' => $revenue,
            'cost_to_sell' => $costToSell,
            'profit' => $revenue - $costToSell,
        ];

        $fieldAgentProfile = FieldAgent::where('user_id', $user->id)->first();
        $isFieldAgent = (bool) $fieldAgentProfile;

        // Commission stats from User model (show only when viewing own profile)
        $commissionStats = (Auth::id() === $user->id) ? $this->calculateCommissionStats($user) : null;

        // Branches allowed for "change branch" (current user's branch + descendants, or all for admin)
        $allowedBranchIds = $currentUser->branch_id ? Branch::selfAndDescendantIds($currentUser->branch_id) : null;
        $branchesForBranchChange = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Branch::where('is_active', true)->orderBy('name')->get();

        return view('users.show', compact('user', 'activityLogs', 'isFieldAgent', 'fieldAgentProfile', 'commissionStats', 'salesStats', 'branchesForBranchChange'));
    }

    /**
     * Calculate commission statistics from User model (commissions tied to user, not only agents).
     */
    private function calculateCommissionStats(User $user)
    {
        $totalEarned = (float) ($user->total_commission_earned ?? 0);
        $availableBalance = (float) ($user->commission_available_balance ?? 0);
        $totalWithdrawn = $totalEarned - $availableBalance;

        return [
            'total_earned' => $totalEarned,
            'total_withdrawn' => $totalWithdrawn,
            'available_balance' => $availableBalance,
        ];
    }

    /**
     * Role slug to store in users.role column.
     * Column is VARCHAR(64), so we store the role's slug as-is so the saved role matches the selected role.
     */
    private function roleSlugToEnum(string $slug): string
    {
        return $slug !== '' ? $slug : 'staff';
    }

    public function makeFieldAgent(User $user)
    {
        if (!User::visibleToUser($user, Auth::user())) {
            abort(403, 'You do not have access to this user.');
        }
        if ($user->isCustomer()) {
            return back()->withErrors(['error' => 'Customers cannot be converted into field agents.']);
        }

        $existing = FieldAgent::where('user_id', $user->id)->first();
        if ($existing) {
            return redirect()->route('users.show', $user)->with('success', 'User is already a field agent.');
        }

        FieldAgent::create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        ActivityLog::log(
            Auth::id(),
            'field_agent_created',
            "Converted user to field agent: {$user->name}",
            User::class,
            $user->id,
            ['user_id' => $user->id]
        );

        return redirect()->route('users.show', $user)->with('success', 'User converted to field agent successfully.');
    }

    public function updateBranch(Request $request, User $user)
    {
        if (!User::visibleToUser($user, Auth::user())) {
            abort(403, 'You do not have access to this user.');
        }
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot change your own branch.']);
        }
        if (!Auth::user()->hasPermission('users.update')) {
            abort(403, 'You do not have permission to update users.');
        }

        $allowedBranchIds = Auth::user()->branch_id ? Branch::selfAndDescendantIds(Auth::user()->branch_id) : null;
        $branchRule = 'nullable|exists:branches,id';
        if ($allowedBranchIds !== null) {
            $branchRule .= '|in:' . implode(',', $allowedBranchIds);
        }
        $validated = $request->validate([
            'branch_id' => $branchRule,
        ]);

        $oldBranchId = $user->branch_id;
        $newBranchId = $validated['branch_id'] ?: null;
        $user->update(['branch_id' => $newBranchId]);

        $oldName = $oldBranchId ? (Branch::find($oldBranchId)?->name ?? 'None') : 'None';
        $newName = $newBranchId ? (Branch::find($newBranchId)?->name ?? 'None') : 'None';

        ActivityLog::log(
            Auth::id(),
            'user_branch_changed',
            "Changed branch for {$user->name} from {$oldName} to {$newName}",
            User::class,
            $user->id,
            ['user_name' => $user->name, 'old_branch_id' => $oldBranchId, 'new_branch_id' => $newBranchId]
        );

        return redirect()->route('users.show', $user)->with('success', 'User branch updated successfully.');
    }

    public function revokeFieldAgent(User $user)
    {
        if (!User::visibleToUser($user, Auth::user())) {
            abort(403, 'You do not have access to this user.');
        }

        $profile = FieldAgent::where('user_id', $user->id)->first();
        if (!$profile) {
            return redirect()->back()->with('info', 'User is not a field agent.');
        }

        $profile->delete();

        ActivityLog::log(
            Auth::id(),
            'field_agent_revoked',
            "Converted field agent to normal user: {$user->name}",
            User::class,
            $user->id,
            ['user_id' => $user->id]
        );

        return redirect()->route('users.show', $user)->with('success', 'User converted to normal user successfully.');
    }

    public function edit(User $user)
    {
        if (!User::visibleToUser($user, Auth::user())) {
            abort(403, 'You do not have access to this user.');
        }
        // Prevent users from editing their own role
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot edit your own role. Please use the profile page to update your personal information.');
        }

        // Prevent non-admin users from editing admin users
        if ($user->isAdmin() && !Auth::user()->isAdmin()) {
            return redirect()->route('users.index')
                ->with('error', 'You do not have permission to edit admin users.');
        }

        $query = Role::where('is_active', true);

        // Non-admin users cannot assign admin role
        if (!Auth::user()->isAdmin()) {
            $query->where('slug', '!=', 'admin');
        }

        $roles = $query->orderBy('name')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        if (!User::visibleToUser($user, Auth::user())) {
            abort(403, 'You do not have access to this user.');
        }
        // Prevent users from editing their own role
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot change your own role. Please use the profile page to update your personal information.');
        }

        // Prevent non-admin users from editing admin users
        if ($user->isAdmin() && !Auth::user()->isAdmin()) {
            return redirect()->route('users.index')
                ->with('error', 'You do not have permission to edit admin users.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id . '|required_without:phone',
            'phone' => 'nullable|string|max:255|required_without:email',
            'role_id' => 'required|exists:roles,id',
        ], [
            'email.required_without' => 'Either email or phone is required.',
            'phone.required_without' => 'Either email or phone is required.',
        ]);

        // Get the role to set both role_id and role (for backward compatibility)
        $role = Role::findOrFail($validated['role_id']);

        // Prevent non-admin users from assigning admin role
        if ($role->slug === 'admin' && !Auth::user()->isAdmin()) {
            return back()->withErrors(['role_id' => 'You do not have permission to assign the admin role.'])
                ->withInput();
        }

        $validated['role'] = $this->roleSlugToEnum($role->slug);
        $validated['email'] = $validated['email'] ?? null;
        $validated['phone'] = $validated['phone'] ?? null;

        // Update only allowed attributes (explicit list so role/role_id are never omitted)
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role_id' => $validated['role_id'],
            'role' => $validated['role'],
        ]);

        // Log activity
        ActivityLog::log(
            Auth::id(),
            'user_updated',
            "Updated user: {$user->name}",
            User::class,
            $user->id,
            ['user_name' => $user->name]
        );

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if (!User::visibleToUser($user, Auth::user())) {
            abort(403, 'You do not have access to this user.');
        }
        // Prevent deletion of own account
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function suspend(User $user)
    {
        if (!User::visibleToUser($user, Auth::user())) {
            abort(403, 'You do not have access to this user.');
        }
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot suspend your own account.']);
        }
        if ($user->isSuspended()) {
            return back()->with('info', 'User is already suspended.');
        }

        $user->update(['suspended_at' => now()]);

        ActivityLog::log(
            Auth::id(),
            'user_suspended',
            "Suspended user: {$user->name}",
            User::class,
            $user->id,
            ['user_id' => $user->id]
        );

        return redirect()->route('users.show', $user)->with('success', 'User has been suspended. They will not be able to log in until unsuspended.');
    }

    public function unsuspend(User $user)
    {
        if (!User::visibleToUser($user, Auth::user())) {
            abort(403, 'You do not have access to this user.');
        }
        if (!$user->isSuspended()) {
            return back()->with('info', 'User is not suspended.');
        }

        $user->update(['suspended_at' => null]);

        ActivityLog::log(
            Auth::id(),
            'user_unsuspended',
            "Unsuspended user: {$user->name}",
            User::class,
            $user->id,
            ['user_id' => $user->id]
        );

        return redirect()->route('users.show', $user)->with('success', 'User has been unsuspended. They can log in again.');
    }

    /**
     * Generate a new password for the user and send it to both email and SMS (when available).
     */
    public function resetPassword(User $user)
    {
        if (!User::visibleToUser($user, Auth::user())) {
            abort(403, 'You do not have access to this user.');
        }

        if (!$user->email && !$user->phone) {
            return redirect()->route('users.show', $user)
                ->withErrors(['password' => 'This user has no email or phone. Add at least one to send a new password.']);
        }

        $plainPassword = $this->generateRandomPassword(12);
        $user->password = Hash::make($plainPassword);
        $user->save();

        $sentEmail = false;
        $sentSms = false;
        if (!empty($user->email)) {
            Mail::to($user->email)->send(new LoginCredentialsMail($user, $plainPassword));
            $sentEmail = true;
        }
        if (!empty($user->phone)) {
            $loginId = $user->email ?: $user->phone;
            SmsHelper::send($user->phone, "Stock Management - Your new password: {$plainPassword}. Login: {$loginId}. Change password after first login.");
            $sentSms = true;
        }

        ActivityLog::log(
            Auth::id(),
            'user_password_reset',
            "Reset password for user: {$user->name}",
            User::class,
            $user->id,
            ['user_id' => $user->id]
        );

        $channels = array_filter([$sentEmail ? 'email' : null, $sentSms ? 'SMS' : null]);
        $message = count($channels) > 0
            ? 'New password generated and sent to ' . implode(' and ', $channels) . '.'
            : 'New password was set but could not be sent (no email or phone).';

        return redirect()->route('users.show', $user)->with('success', $message);
    }

    /**
     * Set a custom password for the user (admin-defined). Credentials are always sent to user email and SMS when available.
     */
    public function setPassword(Request $request, User $user)
    {
        if (!User::visibleToUser($user, Auth::user())) {
            abort(403, 'You do not have access to this user.');
        }

        if (!$user->email && !$user->phone) {
            return redirect()->route('users.show', $user)
                ->withErrors(['password' => 'This user has no email or phone. Add at least one so credentials can be sent.']);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $plainPassword = $validated['password'];
        $user->password = Hash::make($plainPassword);
        $user->save();

        $sentEmail = false;
        $sentSms = false;
        if (!empty($user->email)) {
            Mail::to($user->email)->send(new LoginCredentialsMail($user, $plainPassword));
            $sentEmail = true;
        }
        if (!empty($user->phone)) {
            $loginId = $user->email ?: $user->phone;
            SmsHelper::send($user->phone, "Stock Management - Your new password: {$plainPassword}. Login: {$loginId}. Change password after first login.");
            $sentSms = true;
        }
        $channels = array_filter([$sentEmail ? 'email' : null, $sentSms ? 'SMS' : null]);
        $message = 'Password set and sent to ' . implode(' and ', $channels) . '.';

        ActivityLog::log(
            Auth::id(),
            'user_password_set',
            "Set password for user: {$user->name}",
            User::class,
            $user->id,
            ['user_id' => $user->id]
        );

        return redirect()->route('users.show', $user)->with('success', $message);
    }

    public function importForm()
    {
        $query = Role::where('is_active', true)->where('slug', '!=', 'customer')->orderBy('name');
        if (!Auth::user()->isAdmin()) {
            $query->where('slug', '!=', 'admin');
        }
        $roles = $query->get(['id', 'name', 'slug']);

        $user = Auth::user();
        $allowedBranchIds = $user->branch_id ? \App\Models\Branch::selfAndDescendantIds($user->branch_id) : null;
        $branchesQuery = \App\Models\Branch::where('is_active', true)->orderBy('name');
        if ($allowedBranchIds !== null) {
            $branchesQuery->whereIn('id', $allowedBranchIds);
        }
        $branches = $branchesQuery->get(['id', 'name', 'code']);

        return view('users.import', compact('roles', 'branches'));
    }

    public function importSubmit(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:5120',
        ]);

        $user = Auth::user();
        $defaultBranchId = $user->branch_id;
        $allowedBranchIds = $defaultBranchId ? \App\Models\Branch::selfAndDescendantIds($defaultBranchId) : null;

        $import = new UsersImport($defaultBranchId, $allowedBranchIds, $user->isAdmin());
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $f) {
                $errors[] = 'Row ' . $f->row() . ': ' . implode(', ', $f->errors());
            }
            return redirect()->route('users.import')->withErrors(['file' => $errors])->withInput();
        }

        $imported = $import->getImportedCount();
        $errors = $import->getErrors();
        if (count($errors) > 0) {
            return redirect()->route('users.index')
                ->with('import_errors', $errors)
                ->with($imported > 0 ? 'success' : 'warning', $imported > 0 ? "{$imported} user(s) imported. Some rows had errors." : 'No users were imported. Please fix the errors below.');
        }
        return redirect()->route('users.index')->with('success', $imported . ' user(s) imported successfully.');
    }

    public function downloadSampleCsv(): BinaryFileResponse
    {
        $path = resource_path('samples/users-import-sample.csv');
        return response()->download($path, 'users-import-sample.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Generate a random password
     */
    private function generateRandomPassword(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $max)];
        }

        return $password;
    }
}
