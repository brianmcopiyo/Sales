<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\CustomerDisbursement;
use App\Exports\CustomersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    protected function customerQuery(Request $request)
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        $allowedBranchIds = !$isFieldAgent && $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;

        $query = Customer::query();
        if ($isFieldAgent) {
            $query->whereHas('sales.items', fn($q) => $q->where('field_agent_id', $user->id));
        } else {
            $query->when($allowedBranchIds !== null, fn($q) => $q->visibleToBranches($allowedBranchIds));
        }

        if ($request->filled('branch')) {
            $branchId = $request->get('branch');
            if ($allowedBranchIds === null || in_array($branchId, $allowedBranchIds, true)) {
                $query->visibleToBranches([$branchId]);
            }
        }

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }
        if ($request->filled('status')) {
            if ($request->get('status') === 'active') {
                $query->where('is_active', true);
            }
            if ($request->get('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        return $query;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        $allowedBranchIds = !$isFieldAgent && $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
        $branches = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->orderBy('name')->get()
            : Branch::orderBy('name')->get();

        $query = $this->customerQuery($request);
        $customers = $query->latest()->paginate(15)->withQueryString();

        $statsQuery = Customer::query();
        if ($isFieldAgent) {
            $statsQuery->whereHas('sales.items', fn($q) => $q->where('field_agent_id', $user->id));
        } else {
            $statsQuery->when($allowedBranchIds !== null, fn($q) => $q->visibleToBranches($allowedBranchIds));
        }
        if ($request->filled('branch')) {
            $branchId = $request->get('branch');
            if ($allowedBranchIds === null || in_array($branchId, $allowedBranchIds, true)) {
                $statsQuery->visibleToBranches([$branchId]);
            }
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('is_active', true)->count(),
            'inactive' => (clone $statsQuery)->where('is_active', false)->count(),
            'this_month' => (clone $statsQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        $branchFilter = $request->get('branch');
        return view('customers.index', compact('customers', 'stats', 'branches', 'branchFilter'));
    }

    public function export(Request $request)
    {
        $filename = 'customers-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new CustomersExport($request), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:customers,email',
            'phone' => 'nullable|string|max:255|unique:customers,phone',
            'address' => 'nullable|string',
            'id_number' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['email'] = trim($validated['email'] ?? '') ?: null;
        $validated['phone'] = trim($validated['phone'] ?? '') ?: null;

        Customer::create($validated);
        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        if ($isFieldAgent) {
            $hasAccess = $customer->sales()->whereHas('items', fn($q) => $q->where('field_agent_id', $user->id))->exists();
            if (!$hasAccess) {
                abort(403, 'You do not have access to this customer.');
            }
        } elseif ($user->branch_id) {
            $branchIds = Branch::selfAndDescendantIds($user->branch_id);
            $hasAccess = $customer->devices()->whereIn('branch_id', $branchIds)->exists()
                || $customer->sales()->whereIn('branch_id', $branchIds)->exists();
            if (!$hasAccess) {
                abort(403, 'You do not have access to this customer.');
            }
        }
        $customer->load(['devices.product', 'sales.items.product', 'tickets']);
        $saleIds = $customer->sales->pluck('id')->all();
        $revenue = $customer->sales->sum('total');
        $totalBuyingPrice = \App\Models\Sale::totalBuyingPriceForSaleIds($saleIds);
        $costToSell = $totalBuyingPrice + $customer->sales->sum('total_license_cost') + CustomerDisbursement::whereIn('sale_id', $saleIds)->sum('amount');
        $salesStats = [
            'revenue' => $revenue,
            'cost_to_sell' => $costToSell,
            'profit' => $revenue - $costToSell,
        ];
        $userBranch = $user->branch;
        return view('customers.show', compact('customer', 'salesStats', 'userBranch'));
    }

    public function edit(Customer $customer)
    {
        $user = Auth::user();
        if ($user->branch_id) {
            $branchIds = Branch::selfAndDescendantIds($user->branch_id);
            $hasAccess = $customer->devices()->whereIn('branch_id', $branchIds)->exists()
                || $customer->sales()->whereIn('branch_id', $branchIds)->exists();
            if (!$hasAccess) {
                abort(403, 'You do not have access to this customer.');
            }
        }
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if ($user->branch_id) {
            $branchIds = Branch::selfAndDescendantIds($user->branch_id);
            $hasAccess = $customer->devices()->whereIn('branch_id', $branchIds)->exists()
                || $customer->sales()->whereIn('branch_id', $branchIds)->exists();
            if (!$hasAccess) {
                abort(403, 'You do not have access to this customer.');
            }
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:255|unique:customers,phone,' . $customer->id,
            'address' => 'nullable|string',
            'id_number' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['email'] = trim($validated['email'] ?? '') ?: null;
        $validated['phone'] = trim($validated['phone'] ?? '') ?: null;

        $customer->update($validated);
        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $user = Auth::user();
        if ($user->branch_id) {
            $branchIds = Branch::selfAndDescendantIds($user->branch_id);
            $hasAccess = $customer->devices()->whereIn('branch_id', $branchIds)->exists()
                || $customer->sales()->whereIn('branch_id', $branchIds)->exists();
            if (!$hasAccess) {
                abort(403, 'You do not have access to this customer.');
            }
        }
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
}
