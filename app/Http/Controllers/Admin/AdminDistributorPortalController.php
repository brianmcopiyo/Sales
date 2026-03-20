<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PeriodHelper;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DistributorProfile;
use App\Models\DistributorTarget;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminDistributorPortalController extends Controller
{
    public function index(Request $request)
    {
        $query = DistributorProfile::with(['user', 'customer', 'assignedBranch']);

        if ($request->filled('search')) {
            $query->whereHas('customer', fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'));
        }
        if ($request->filled('branch')) {
            $query->where('assigned_branch_id', $request->branch);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $profiles = $query->latest()->paginate(20)->withQueryString();
        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.distributor-portal.index', compact('profiles', 'branches'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone', 'email']);
        $branches  = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.distributor-portal.create', compact('customers', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'         => 'required|exists:customers,id|unique:distributor_profiles,customer_id',
            'assigned_branch_id'  => 'nullable|exists:branches,id',
            'credit_limit'        => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:1000',
            // New user fields
            'user_name'           => 'required|string|max:255',
            'user_email'          => 'nullable|email|unique:users,email',
            'user_phone'          => 'nullable|string|max:20|unique:users,phone',
            'user_password'       => 'required|string|min:8',
        ]);

        // Require at least email or phone
        if (empty($validated['user_email']) && empty($validated['user_phone'])) {
            return back()->withErrors(['user_email' => 'Provide at least an email or phone for the portal account.'])->withInput();
        }

        $distributorRole = Role::where('slug', 'distributor')->first();
        if (!$distributorRole) {
            return back()->withErrors(['error' => 'Distributor role not found. Please run migrations.'])->withInput();
        }

        DB::transaction(function () use ($validated, $distributorRole) {
            $plainPassword = $validated['user_password'];
            User::$plainPasswordForNewUser = $plainPassword;

            $user = User::create([
                'name'     => $validated['user_name'],
                'email'    => $validated['user_email'] ?? null,
                'phone'    => $validated['user_phone'] ?? null,
                'role_id'  => $distributorRole->id,
                'password' => Hash::make($plainPassword),
            ]);

            DistributorProfile::create([
                'user_id'             => $user->id,
                'customer_id'         => $validated['customer_id'],
                'assigned_branch_id'  => $validated['assigned_branch_id'] ?? null,
                'credit_limit'        => $validated['credit_limit'] ?? null,
                'notes'               => $validated['notes'] ?? null,
                'is_active'           => true,
                'portal_enabled_at'   => now(),
            ]);
        });

        return redirect()->route('admin.distributor-portal.index')
            ->with('success', 'Distributor portal account created successfully.');
    }

    public function show(DistributorProfile $profile)
    {
        $profile->load(['user', 'customer', 'assignedBranch', 'targets.setBy', 'claims']);

        [$mtdStart, $mtdEnd] = PeriodHelper::getRange('this_month');
        $revenueMtd = Sale::where('customer_id', $profile->customer_id)
            ->secondarySales()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$mtdStart, $mtdEnd])
            ->sum('total');

        $totalOrders = Sale::where('customer_id', $profile->customer_id)->secondarySales()->count();
        $branches    = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.distributor-portal.show', compact('profile', 'revenueMtd', 'totalOrders', 'branches'));
    }

    public function edit(DistributorProfile $profile)
    {
        $branches = Branch::orderBy('name')->get(['id', 'name']);
        return view('admin.distributor-portal.edit', compact('profile', 'branches'));
    }

    public function update(Request $request, DistributorProfile $profile)
    {
        $validated = $request->validate([
            'assigned_branch_id'  => 'nullable|exists:branches,id',
            'credit_limit'        => 'nullable|numeric|min:0',
            'outstanding_balance' => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:1000',
            'is_active'           => 'boolean',
        ]);

        $profile->update($validated);

        return redirect()->route('admin.distributor-portal.show', $profile)
            ->with('success', 'Distributor profile updated.');
    }
}
