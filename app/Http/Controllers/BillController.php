<?php

namespace App\Http\Controllers;

use App\Exports\BillsExport;
use App\Models\ActivityLog;
use App\Models\Bill;
use App\Models\BillAttachment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Branch;
use App\Models\BillCategory;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

class BillController extends Controller
{
    protected function allowedBranchIds(): ?array
    {
        $user = auth()->user();
        return $user && $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
    }

    public function index(Request $request)
    {
        $allowedBranchIds = $this->allowedBranchIds();

        $query = Bill::with(['vendor', 'branch', 'category', 'createdBy'])
            ->when($allowedBranchIds !== null, fn ($q) => $q->where(function ($q) use ($allowedBranchIds) {
                $q->whereIn('branch_id', $allowedBranchIds)->orWhereNull('branch_id');
            }))
            ->latest();

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        // Preset filters from stat card clicks
        if ($request->filled('filter')) {
            match ($request->filter) {
                'unpaid' => $query->unpaid(),
                'due_this_week' => $query->unpaid()->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]),
                'overdue' => $query->overdue(),
                'paid_this_month' => $query->paid()
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year),
                default => null,
            };
        }

        $filteredIds = (clone $query)->pluck('id')->all();
        $baseFiltered = Bill::whereIn('id', $filteredIds);

        $stats = [
            'total_unpaid' => (clone $baseFiltered)->unpaid()->sum('amount'),
            'due_this_week' => (clone $baseFiltered)->unpaid()
                ->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'overdue' => (clone $baseFiltered)->overdue()->count(),
            'paid_this_month' => (clone $baseFiltered)->paid()
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
        ];

        $bills = $query->paginate(15)->withQueryString();

        $vendors = Vendor::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $categories = BillCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $branches = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $user = auth()->user();
        $canCreate = $user && $user->hasPermission('bills.create');
        $canApprove = $user && $user->hasPermission('bills.approve');
        $canPay = $user && $user->hasPermission('bills.pay');
        $canExport = $user && $user->hasPermission('bills.export');

        return view('bills.index', compact(
            'bills',
            'stats',
            'vendors',
            'categories',
            'branches',
            'canCreate',
            'canApprove',
            'canPay',
            'canExport'
        ));
    }

    public function create()
    {
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $categories = BillCategory::where('is_active', true)->orderBy('name')->get();
        $allowedBranchIds = $this->allowedBranchIds();
        $branches = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Branch::where('is_active', true)->orderBy('name')->get();

        return view('bills.create', compact('vendors', 'categories', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:bill_categories,id',
            'invoice_number' => 'nullable|string|max:80',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
        ]);

        $validated['currency'] = $validated['currency'] ?? config('app.currency_symbol');
        $validated['status'] = Bill::STATUS_PENDING_APPROVAL;
        $validated['created_by'] = Auth::id();

        $allowedBranchIds = $this->allowedBranchIds();
        if (!empty($validated['branch_id']) && $allowedBranchIds !== null && !in_array($validated['branch_id'], $allowedBranchIds, true)) {
            return back()->withErrors(['branch_id' => 'You do not have access to this branch.'])->withInput();
        }

        $bill = Bill::create($validated);
        $bill->load('vendor');
        ActivityLog::log(
            auth()->id(),
            'bill_created',
            "Bill created for {$bill->vendor->name}, amount {$bill->amount} {$bill->currency} (pending approval)",
            Bill::class,
            $bill->id,
            ['amount' => $bill->amount, 'vendor_id' => $bill->vendor_id, 'invoice_number' => $bill->invoice_number]
        );

        $approvers = User::usersWithBillsApprovePermission()->reject(fn ($u) => $u->id === auth()->id());
        if ($approvers->isNotEmpty()) {
            $title = 'New bill pending approval';
            $message = $bill->vendor->name . ' – ' . config('app.currency_symbol') . ' ' . number_format((float) $bill->amount, 2) . ' – pending your approval.';
            $url = route('bills.show', $bill);
            Notification::send($approvers, new AppNotification($title, $message, $url, 'bill_pending_approval', ['bill_id' => $bill->id]));
        }

        return redirect()->route('bills.index')->with('success', 'Bill created successfully.');
    }

    public function show(Bill $bill)
    {
        $this->authorizeBillAccess($bill);

        $bill->load(['vendor', 'branch', 'category', 'approvedBy', 'rejectedBy', 'paidBy', 'createdBy', 'attachments']);

        $user = auth()->user();
        $canApprove = $user && $user->hasPermission('bills.approve') && $bill->isPendingApproval();
        $canPay = $user && $user->hasPermission('bills.pay') && $bill->isApproved();
        $canEdit = $user && $user->hasPermission('bills.create') && in_array($bill->status, [Bill::STATUS_DRAFT, Bill::STATUS_PENDING_APPROVAL]);

        return view('bills.show', compact('bill', 'canApprove', 'canPay', 'canEdit'));
    }

    public function edit(Bill $bill)
    {
        $this->authorizeBillAccess($bill);

        if (!in_array($bill->status, [Bill::STATUS_DRAFT, Bill::STATUS_PENDING_APPROVAL], true)) {
            return redirect()->route('bills.show', $bill)->withErrors(['error' => 'Only draft or pending approval bills can be edited.']);
        }

        $bill->load(['vendor', 'branch', 'category']);
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $categories = BillCategory::where('is_active', true)->orderBy('name')->get();
        $allowedBranchIds = $this->allowedBranchIds();
        $branches = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Branch::where('is_active', true)->orderBy('name')->get();

        return view('bills.edit', compact('bill', 'vendors', 'categories', 'branches'));
    }

    public function update(Request $request, Bill $bill)
    {
        $this->authorizeBillAccess($bill);

        if (!in_array($bill->status, [Bill::STATUS_DRAFT, Bill::STATUS_PENDING_APPROVAL], true)) {
            return redirect()->route('bills.show', $bill)->withErrors(['error' => 'Only draft or pending approval bills can be edited.']);
        }

        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:bill_categories,id',
            'invoice_number' => 'nullable|string|max:80',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
        ]);

        $validated['currency'] = $validated['currency'] ?? config('app.currency_symbol');

        $allowedBranchIds = $this->allowedBranchIds();
        if (!empty($validated['branch_id']) && $allowedBranchIds !== null && !in_array($validated['branch_id'], $allowedBranchIds, true)) {
            return back()->withErrors(['branch_id' => 'You do not have access to this branch.'])->withInput();
        }

        $bill->update($validated);

        return redirect()->route('bills.show', $bill)->with('success', 'Bill updated successfully.');
    }

    public function approve(Bill $bill)
    {
        if (!auth()->user()?->hasPermission('bills.approve')) {
            abort(403, 'You do not have permission to approve bills.');
        }
        $this->authorizeBillAccess($bill);

        if (!$bill->isPendingApproval()) {
            return redirect()->route('bills.show', $bill)->withErrors(['error' => 'Only bills pending approval can be approved.']);
        }

        $bill->update([
            'status' => Bill::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
        $bill->load('vendor');
        ActivityLog::log(
            auth()->id(),
            'bill_approved',
            "Bill approved for {$bill->vendor->name}, amount {$bill->amount} {$bill->currency}",
            Bill::class,
            $bill->id,
            ['amount' => $bill->amount, 'vendor_id' => $bill->vendor_id]
        );

        return redirect()->route('bills.show', $bill)->with('success', 'Bill approved.');
    }

    public function reject(Request $request, Bill $bill)
    {
        if (!auth()->user()?->hasPermission('bills.approve')) {
            abort(403, 'You do not have permission to reject bills.');
        }
        $this->authorizeBillAccess($bill);

        if (!$bill->isPendingApproval()) {
            return redirect()->route('bills.show', $bill)->withErrors(['error' => 'Only bills pending approval can be rejected.']);
        }

        $validated = $request->validate(['rejection_reason' => 'nullable|string|max:500']);

        $bill->update([
            'status' => Bill::STATUS_REJECTED,
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $bill->load('vendor');
        $reason = $validated['rejection_reason'] ?? null;
        ActivityLog::log(
            auth()->id(),
            'bill_rejected',
            "Bill rejected for {$bill->vendor->name}, amount {$bill->amount} {$bill->currency}" . ($reason ? " – {$reason}" : ''),
            Bill::class,
            $bill->id,
            ['amount' => $bill->amount, 'vendor_id' => $bill->vendor_id]
        );

        return redirect()->route('bills.show', $bill)->with('success', 'Bill rejected.');
    }

    public function markPaid(Request $request, Bill $bill)
    {
        if (!auth()->user()?->hasPermission('bills.pay')) {
            abort(403, 'You do not have permission to mark bills as paid.');
        }
        $this->authorizeBillAccess($bill);

        if (!$bill->isApproved()) {
            return redirect()->route('bills.show', $bill)->withErrors(['error' => 'Only approved bills can be marked as paid.']);
        }

        $validated = $request->validate([
            'paid_at' => 'required|date',
            'payment_reference' => 'nullable|string|max:120',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:5120',
        ]);

        $bill->update([
            'status' => Bill::STATUS_PAID,
            'paid_at' => $validated['paid_at'],
            'paid_by' => Auth::id(),
            'payment_reference' => $validated['payment_reference'] ?? null,
        ]);

        if ($request->hasFile('evidence')) {
            $file = $request->file('evidence');
            $path = $file->store('bill-payment-evidence', 'public');
            BillAttachment::create([
                'bill_id' => $bill->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_by' => Auth::id(),
            ]);
        }

        $bill->load('vendor');
        ActivityLog::log(
            auth()->id(),
            'bill_paid',
            "Bill marked as paid for {$bill->vendor->name}, amount {$bill->amount} {$bill->currency}" . ($validated['payment_reference'] ?? '' ? " (ref: {$validated['payment_reference']})" : ''),
            Bill::class,
            $bill->id,
            ['amount' => $bill->amount, 'vendor_id' => $bill->vendor_id, 'paid_at' => $validated['paid_at']]
        );

        return redirect()->route('bills.show', $bill)->with('success', 'Bill marked as paid.');
    }

    public function downloadAttachment(BillAttachment $attachment)
    {
        $attachment->load('bill');
        $this->authorizeBillAccess($attachment->bill);
        $path = storage_path('app/public/' . $attachment->file_path);
        if (!is_file($path)) {
            abort(404, 'File not found.');
        }
        $name = $attachment->original_name ?: basename($attachment->file_path);
        return response()->download($path, $name);
    }

    public function export(Request $request)
    {
        $filename = 'bills-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new BillsExport($request), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    protected function authorizeBillAccess(Bill $bill): void
    {
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds === null) {
            return;
        }
        if ($bill->branch_id !== null && !in_array($bill->branch_id, $allowedBranchIds, true)) {
            abort(403, 'You do not have access to this bill.');
        }
    }
}
