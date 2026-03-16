<?php

namespace App\Http\Controllers;

use App\Models\FieldAgent;
use App\Models\Branch;
use App\Models\Role;
use App\Models\SaleItem;
use App\Models\User;
use App\Imports\FieldAgentsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FieldAgentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        // Field agents only see their own profile; redirect to it
        if ($user->fieldAgentProfile && $user->branch_id) {
            return redirect()->route('field-agents.show', $user->fieldAgentProfile);
        }
        $visibleUserIds = User::visibleTo($user)->pluck('id');

        $query = FieldAgent::query()
            ->with('user.branch')
            ->when($visibleUserIds->isNotEmpty(), fn($q) => $q->whereIn('user_id', $visibleUserIds))
            ->latest();

        if ($request->filled('search')) {
            $term = $request->search;
            $query->whereHas('user', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('user', fn($q) => $q->where('branch_id', $request->branch_id));
        }

        $fieldAgents = $query->paginate(15)->withQueryString();

        $baseStatsQuery = FieldAgent::query()->when($visibleUserIds->isNotEmpty(), fn($q) => $q->whereIn('user_id', $visibleUserIds));
        $statsUserIds = $visibleUserIds->isEmpty() ? FieldAgent::pluck('user_id') : $visibleUserIds;
        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'active' => (clone $baseStatsQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseStatsQuery)->where('is_active', false)->count(),
            'devices_distributed' => SaleItem::whereNotNull('field_agent_id')->whereIn('field_agent_id', $visibleUserIds->isEmpty() ? [-1] : $visibleUserIds)->sum('quantity'),
            'total_commission' => \App\Models\User::whereIn('id', $statsUserIds)->sum('total_commission_earned'),
        ];

        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
        $branches = $allowedBranchIds
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Branch::where('is_active', true)->orderBy('name')->get();

        return view('field-agents.index', compact('fieldAgents', 'stats', 'branches'));
    }

    public function create()
    {
        return view('field-agents.create');
    }

    public function importForm()
    {
        $user = Auth::user();
        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
        $branchesQuery = Branch::where('is_active', true)->orderBy('name');
        if ($allowedBranchIds !== null) {
            $branchesQuery->whereIn('id', $allowedBranchIds);
        }
        $branches = $branchesQuery->get(['id', 'name', 'code']);
        return view('field-agents.import', compact('branches'));
    }

    public function importSubmit(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:5120',
        ]);

        $user = Auth::user();
        $defaultBranchId = $user->branch_id;
        $allowedBranchIds = $defaultBranchId ? Branch::selfAndDescendantIds($defaultBranchId) : null;

        $import = new FieldAgentsImport($defaultBranchId, $allowedBranchIds);
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $f) {
                $errors[] = 'Row ' . $f->row() . ': ' . implode(', ', $f->errors());
            }
            return redirect()->route('field-agents.import')->withErrors(['file' => $errors])->withInput();
        }

        $imported = $import->getImportedCount();
        $errors = $import->getErrors();
        if (count($errors) > 0) {
            return redirect()->route('field-agents.index')
                ->with('import_errors', $errors)
                ->with($imported > 0 ? 'success' : 'warning', $imported > 0 ? "{$imported} field agent(s) imported. Some rows had errors." : 'No field agents were imported. Please fix the errors below.');
        }
        return redirect()->route('field-agents.index')->with('success', $imported . ' field agent(s) imported successfully.');
    }

    public function downloadSampleCsv(): BinaryFileResponse
    {
        $path = resource_path('samples/field-agents-import-sample.csv');
        return response()->download($path, 'field-agents-import-sample.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // User fields – at least one of email or phone required
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email|required_without:phone',
            'phone' => 'nullable|string|max:255|required_without:email',
            'password' => 'nullable|string|min:8|confirmed',

            // Field agent profile fields
            'is_active' => 'boolean',
        ], [
            'email.required_without' => 'Either email or phone is required.',
            'phone.required_without' => 'Either email or phone is required.',
        ]);

        $plainPassword = $validated['password'] ?? null;
        if (!$plainPassword) {
            $plainPassword = Str::random(12);
        }

        $currentUser = Auth::user();
        $branchId = $currentUser?->branch_id;

        DB::transaction(function () use ($validated, $plainPassword, $branchId) {
            // Get staff role
            $staffRole = Role::where('slug', 'staff')->first();
            if (!$staffRole) {
                throw new \Exception('Staff role not found. Please run migrations.');
            }

            $user = \App\Models\User::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($plainPassword),
                'role_id' => $staffRole->id,
                'role' => 'staff', // For backward compatibility
                'branch_id' => $branchId,
                'phone' => $validated['phone'] ?? null,
            ]);

            FieldAgent::create([
                'user_id' => $user->id,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);
        });

        $msg = "Field agent created successfully. ";
        if (empty($validated['password'])) {
            $msg .= "Temporary password: {$plainPassword}";
        }

        return redirect()->route('field-agents.index')->with('success', trim($msg));
    }

    public function show(FieldAgent $fieldAgent)
    {
        $user = Auth::user();
        // Field agents may only view their own profile
        if ($user->fieldAgentProfile && $user->branch_id) {
            if ((string) $fieldAgent->user_id !== (string) $user->id) {
                return redirect()->route('field-agents.show', $user->fieldAgentProfile);
            }
        }
        $fieldAgent->load('user.branch');

        $saleItems = $fieldAgent->saleItems()
            ->with(['sale.customer', 'sale.branch', 'device', 'product'])
            ->latest()
            ->paginate(20);

        // Commission totals from User model (commissions tied to user)
        $agentUser = $fieldAgent->user;
        $totals = [
            'devices_distributed' => $fieldAgent->saleItems()->sum('quantity'),
            'total_commission' => (float) ($agentUser->total_commission_earned ?? 0),
            'available_balance' => (float) ($agentUser->commission_available_balance ?? 0),
        ];

        return view('field-agents.show', compact('fieldAgent', 'saleItems', 'totals'));
    }

    public function edit(FieldAgent $fieldAgent)
    {
        $user = Auth::user();
        if (!User::visibleToUser($fieldAgent->user, $user)) {
            abort(403, 'You do not have access to this field agent.');
        }
        $fieldAgent->load('user.branch');

        return view('field-agents.edit', compact('fieldAgent'));
    }

    public function update(Request $request, FieldAgent $fieldAgent)
    {
        if (!User::visibleToUser($fieldAgent->user, Auth::user())) {
            abort(403, 'You do not have access to this field agent.');
        }
        $validated = $request->validate([
            'is_active' => 'boolean',
        ]);

        $fieldAgent->update($validated);

        return redirect()->route('field-agents.index')->with('success', 'Field agent updated successfully.');
    }

    public function destroy(FieldAgent $fieldAgent)
    {
        if (!User::visibleToUser($fieldAgent->user, Auth::user())) {
            abort(403, 'You do not have access to this field agent.');
        }
        $fieldAgent->delete();

        return redirect()->route('field-agents.index')->with('success', 'Field agent deleted successfully.');
    }
}
