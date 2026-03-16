<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\CustomerDisbursement;
use App\Models\Region;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;

        $query = Branch::with(['headBranch', 'region']);
        if ($allowedBranchIds !== null) {
            $query->whereIn('id', $allowedBranchIds);
        }

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where('name', 'like', "%{$term}%");
        }
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->get('region_id'));
        }
        if ($request->filled('status')) {
            if ($request->get('status') === 'active') {
                $query->where('is_active', true);
            }
            if ($request->get('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $allBranches = $query->get();

        // Order branches hierarchically
        $ordered = $this->orderBranchesHierarchically($allBranches);

        // Paginate the ordered branches
        $currentPage = $request->get('page', 1);
        $perPage = 15;
        $items = $ordered->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $branches = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $ordered->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $branches->withQueryString();

        $statsQuery = Branch::query();
        if ($allowedBranchIds !== null) {
            $statsQuery->whereIn('id', $allowedBranchIds);
        }
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('is_active', true)->count(),
            'inactive' => (clone $statsQuery)->where('is_active', false)->count(),
            'total_stock' => BranchStock::sum('quantity'),
        ];

        // Stock totals per branch (for table overview)
        $stockByBranch = BranchStock::selectRaw('branch_id, sum(quantity) as total_stock')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');

        $branchIds = $allBranches->pluck('id')->all();
        $salesTotalByBranch = Sale::query()
            ->whereIn('branch_id', $branchIds)
            ->where('status', 'completed')
            ->selectRaw('branch_id, sum(total) as total_sales')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
        $disbursementTotalByBranch = $branchIds !== []
            ? CustomerDisbursement::query()
                ->join('sales', 'customer_disbursements.sale_id', '=', 'sales.id')
                ->whereIn('sales.branch_id', $branchIds)
                ->selectRaw('sales.branch_id, sum(customer_disbursements.amount) as total_disbursement')
                ->groupBy('sales.branch_id')
                ->get()
                ->keyBy('branch_id')
            : collect();

        $regions = Region::orderBy('name')->get(['id', 'name']);

        return view('branches.index', compact('branches', 'stats', 'stockByBranch', 'regions', 'salesTotalByBranch', 'disbursementTotalByBranch'));
    }

    /**
     * Order branches hierarchically (head branches first, then their children)
     */
    private function orderBranchesHierarchically($branches)
    {
        $ordered = collect();
        $processed = collect();

        // First, get all head branches (branches with no head_branch_id)
        $headBranches = $branches->whereNull('head_branch_id')->sortBy('name');

        // Recursively add branches and their children
        foreach ($headBranches as $headBranch) {
            $this->addBranchAndChildren($headBranch, $branches, $ordered, $processed);
        }

        // Add any remaining branches that might have circular references or orphaned branches
        foreach ($branches as $branch) {
            if (!$processed->contains($branch->id)) {
                $ordered->push($branch);
            }
        }

        return $ordered;
    }

    /**
     * Recursively add a branch and its children to the ordered collection
     */
    private function addBranchAndChildren($branch, $allBranches, &$ordered, &$processed, $level = 0)
    {
        if ($processed->contains($branch->id)) {
            return; // Already processed (prevent infinite loops)
        }

        $processed->push($branch->id);
        // Add hierarchy level to branch for display
        $branch->hierarchy_level = $level;
        $ordered->push($branch);

        // Find and add children of this branch
        $children = $allBranches->where('head_branch_id', $branch->id)->sortBy('name');
        foreach ($children as $child) {
            $this->addBranchAndChildren($child, $allBranches, $ordered, $processed, $level + 1);
        }
    }

    public function create()
    {
        $user = Auth::user();
        $headBranches = Branch::all();
        $regions = Region::where('is_active', true)->orderBy('name')->get();
        $userBranchId = $user->branch_id;
        return view('branches.create', compact('headBranches', 'regions', 'userBranchId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'head_branch_id' => 'nullable|exists:branches,id',
            'region_id' => 'required|exists:regions,id',
            'is_active' => 'boolean',
        ]);

        // Generate unique branch code
        $validated['code'] = $this->generateBranchCode($validated['name']);

        // Set head_branch_id to the logged-in user's branch if not provided
        $user = Auth::user();
        if (!$validated['head_branch_id'] && $user->branch_id) {
            $validated['head_branch_id'] = $user->branch_id;
        }

        Branch::create($validated);
        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function show(Branch $branch)
    {
        $user = Auth::user();
        // When user has a branch, they can only view branches in their tree (this branch and below)
        if ($user->branch_id) {
            $allowedBranchIds = Branch::selfAndDescendantIds($user->branch_id);
            if (!in_array($branch->id, $allowedBranchIds, true)) {
                abort(403, 'You do not have access to this branch.');
            }
        }

        $branch->load(['headBranch', 'regionalBranches', 'users', 'region']);
        $availableUsers = [];

        // Get users from current user's branch tree (branch and below) not already in this branch
        if ($user->branch_id) {
            $allowedBranchIds = Branch::selfAndDescendantIds($user->branch_id);
            $existingUserIds = $branch->users->pluck('id')->toArray();
            $availableUsers = User::whereIn('branch_id', $allowedBranchIds)
                ->whereNotIn('id', $existingUserIds)
                ->where('id', '!=', $user->id)
                ->get();
        } else {
            $availableUsers = collect();
        }

        $branchStocks = \App\Models\BranchStock::where('branch_id', $branch->id)
            ->with('product')
            ->get()
            ->sortBy(fn ($bs) => $bs->product?->name ?? '');

        return view('branches.show', compact('branch', 'availableUsers', 'branchStocks'));
    }

    public function edit(Branch $branch)
    {
        $user = Auth::user();
        if ($user->branch_id) {
            $allowedBranchIds = Branch::selfAndDescendantIds($user->branch_id);
            if (!in_array($branch->id, $allowedBranchIds, true)) {
                abort(403, 'You do not have access to this branch.');
            }
        }
        $headBranches = Branch::where('id', '!=', $branch->id)->get();
        $regions = Region::where('is_active', true)->orderBy('name')->get();
        $userBranchId = $user->branch_id;
        return view('branches.edit', compact('branch', 'headBranches', 'regions', 'userBranchId'));
    }

    public function update(Request $request, Branch $branch)
    {
        $user = Auth::user();
        if ($user->branch_id) {
            $allowedBranchIds = Branch::selfAndDescendantIds($user->branch_id);
            if (!in_array($branch->id, $allowedBranchIds, true)) {
                abort(403, 'You do not have access to this branch.');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'head_branch_id' => 'nullable|exists:branches,id',
            'region_id' => 'required|exists:regions,id',
            'is_active' => 'boolean',
        ]);

        // Code is auto-generated and should not be updated
        // Only update other fields
        $branch->update($validated);
        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        $user = Auth::user();
        if ($user->branch_id) {
            $allowedBranchIds = Branch::selfAndDescendantIds($user->branch_id);
            if (!in_array($branch->id, $allowedBranchIds, true)) {
                abort(403, 'You do not have access to this branch.');
            }
        }
        $branch->delete();
        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
    }

    /**
     * Generate a unique branch code based on branch name
     */
    private function generateBranchCode(string $name): string
    {
        // Extract initials from branch name (first 3 letters, uppercase)
        $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3));

        // If name is too short, pad with X
        $initials = str_pad($initials, 3, 'X', STR_PAD_RIGHT);

        // Get the last branch code number or start from 1
        $lastBranch = Branch::where('code', 'like', $initials . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastBranch && preg_match('/' . preg_quote($initials, '/') . '(\d+)$/', $lastBranch->code, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        // Format: XXX001, XXX002, etc.
        $code = $initials . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Ensure uniqueness (in case of conflicts)
        $counter = 1;
        while (Branch::where('code', $code)->exists()) {
            $code = $initials . str_pad($nextNumber + $counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $code;
    }

    /**
     * Show form to add users to branch
     */
    public function addUsers(Branch $branch)
    {
        $user = Auth::user();

        if (!$user->branch_id) {
            return redirect()->route('branches.show', $branch)
                ->with('error', 'You must belong to a branch to add users.');
        }

        // Get users from the logged-in user's branch that are not already assigned to this branch
        // Exclude the logged-in user from being transferable
        $availableUsers = User::where('branch_id', $user->branch_id)
            ->whereNotIn('id', $branch->users->pluck('id'))
            ->where('id', '!=', $user->id) // Exclude logged-in user
            ->get();

        return view('branches.add-users', compact('branch', 'availableUsers'));
    }

    /**
     * Assign users to branch
     */
    public function assignUsers(Request $request, Branch $branch)
    {
        $user = Auth::user();

        if (!$user->branch_id) {
            return redirect()->route('branches.show', $branch)
                ->with('error', 'You must belong to a branch to assign users.');
        }

        $allowedBranchIds = Branch::selfAndDescendantIds($user->branch_id);
        if (!in_array($branch->id, $allowedBranchIds, true)) {
            abort(403, 'You do not have access to this branch.');
        }

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'required|exists:users,id',
        ]);

        if (in_array($user->id, $validated['user_ids'])) {
            return redirect()->route('branches.show', $branch)
                ->with('error', 'You cannot transfer yourself to another branch.');
        }

        $usersToAssign = User::whereIn('id', $validated['user_ids'])
            ->whereIn('branch_id', $allowedBranchIds)
            ->get();

        if ($usersToAssign->count() !== count($validated['user_ids'])) {
            return redirect()->route('branches.show', $branch)
                ->with('error', 'Some selected users are not in your branch or branches below.');
        }

        // Assign users to the branch
        User::whereIn('id', $validated['user_ids'])
            ->update(['branch_id' => $branch->id]);

        return redirect()->route('branches.show', $branch)
            ->with('success', count($validated['user_ids']) . ' user(s) assigned to this branch successfully.');
    }
}
