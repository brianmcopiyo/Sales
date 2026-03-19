<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Branch;
use App\Models\RecurringBill;
use App\Models\Vendor;
use App\Models\BillCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecurringBillController extends Controller
{
    protected function allowedBranchIds(): ?array
    {
        $user = auth()->user();
        return $user && $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
    }

    public function index()
    {
        $allowedBranchIds = $this->allowedBranchIds();

        $recurring = RecurringBill::with(['vendor', 'branch', 'category'])
            ->when($allowedBranchIds !== null, fn ($q) => $q->where(function ($q) use ($allowedBranchIds) {
                $q->whereIn('branch_id', $allowedBranchIds)->orWhereNull('branch_id');
            }))
            ->orderBy('next_due_date')
            ->paginate(15);

        $canCreate = auth()->user()?->hasPermission('bills.create') ?? false;

        return view('bills.recurring.index', compact('recurring', 'canCreate'));
    }

    public function create()
    {
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $categories = BillCategory::where('is_active', true)->orderBy('name')->get();
        $allowedBranchIds = $this->allowedBranchIds();
        $branches = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Branch::where('is_active', true)->orderBy('name')->get();

        return view('bills.recurring.create', compact('vendors', 'categories', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:bill_categories,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'frequency' => 'required|in:monthly,quarterly,yearly',
            'next_due_date' => 'required|date',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);

        $allowedBranchIds = $this->allowedBranchIds();
        if (! empty($validated['branch_id']) && $allowedBranchIds !== null && ! in_array($validated['branch_id'], $allowedBranchIds, true)) {
            return back()->withErrors(['branch_id' => 'You do not have access to this branch.'])->withInput();
        }

        RecurringBill::create($validated);

        return redirect()->route('bills.recurring.index')->with('success', 'Recurring bill template created.');
    }

    public function edit(RecurringBill $recurringBill)
    {
        $this->authorizeRecurringAccess($recurringBill);

        $recurringBill->load(['vendor', 'branch', 'category']);
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $categories = BillCategory::where('is_active', true)->orderBy('name')->get();
        $allowedBranchIds = $this->allowedBranchIds();
        $branches = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Branch::where('is_active', true)->orderBy('name')->get();

        return view('bills.recurring.edit', compact('recurringBill', 'vendors', 'categories', 'branches'));
    }

    public function update(Request $request, RecurringBill $recurringBill)
    {
        $this->authorizeRecurringAccess($recurringBill);

        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:bill_categories,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'frequency' => 'required|in:monthly,quarterly,yearly',
            'next_due_date' => 'required|date',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);

        $allowedBranchIds = $this->allowedBranchIds();
        if (! empty($validated['branch_id']) && $allowedBranchIds !== null && ! in_array($validated['branch_id'], $allowedBranchIds, true)) {
            return back()->withErrors(['branch_id' => 'You do not have access to this branch.'])->withInput();
        }

        $recurringBill->update($validated);

        return redirect()->route('bills.recurring.index')->with('success', 'Recurring bill template updated.');
    }

    /**
     * Create the next bill from this template and advance next_due_date.
     */
    public function createNextBill(RecurringBill $recurringBill)
    {
        $this->authorizeRecurringAccess($recurringBill);

        if (! $recurringBill->is_active) {
            return redirect()->route('bills.recurring.index')
                ->withErrors(['error' => 'This recurring template is inactive.']);
        }

        $recurringBill->load('vendor');
        $dueDate = $recurringBill->next_due_date;

        $bill = Bill::create([
            'vendor_id' => $recurringBill->vendor_id,
            'branch_id' => $recurringBill->branch_id,
            'category_id' => $recurringBill->category_id,
            'recurring_bill_id' => $recurringBill->id,
            'invoice_number' => null,
            'invoice_date' => $dueDate,
            'due_date' => $dueDate,
            'amount' => $recurringBill->amount,
            'currency' => config('app.currency_symbol'),
            'status' => Bill::STATUS_PENDING_APPROVAL,
            'description' => $recurringBill->description,
            'created_by' => Auth::id(),
        ]);

        $recurringBill->advanceNextDueDate();

        return redirect()->route('bills.show', $bill)
            ->with('success', 'Bill created from recurring template. Next due date has been advanced.');
    }

    protected function authorizeRecurringAccess(RecurringBill $recurringBill): void
    {
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds === null) {
            return;
        }
        if ($recurringBill->branch_id !== null && ! in_array($recurringBill->branch_id, $allowedBranchIds, true)) {
            abort(403, 'You do not have access to this recurring bill template.');
        }
    }
}
