<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomerDisbursement;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Device;
use App\Models\Branch;
use App\Models\User;
use App\Models\ActivityLog;
use App\Exports\CustomerDisbursementsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerDisbursementController extends Controller
{
    protected function allowedBranchIds(): ?array
    {
        /** @var User|null $user */
        $user = Auth::user();
        return $user && $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
    }

    /**
     * Build the filtered query used for listing (and exporting) customer disbursements.
     */
    protected function buildDisbursementsQuery(Request $request)
    {
        $allowedBranchIds = $this->allowedBranchIds();

        $query = CustomerDisbursement::with(['customer', 'sale', 'device', 'disbursedBy' => fn($q) => $q->with('branch')])
            ->when($allowedBranchIds !== null, fn($q) => $q->where(function ($q) use ($allowedBranchIds) {
                $q->whereHas('disbursedBy', fn($u) => $u->whereIn('branch_id', $allowedBranchIds))
                    ->orWhereHas('device', fn($d) => $d->whereIn('branch_id', $allowedBranchIds));
            }))
            ->latest();

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('branch_id')) {
            $query->whereHas('disbursedBy', fn($u) => $u->where('branch_id', $request->branch_id));
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->whereHas('customer', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    /**
     * Display all customer disbursements
     */
    public function index(Request $request)
    {
        $allowedBranchIds = $this->allowedBranchIds();
        $query = $this->buildDisbursementsQuery($request);

        // Stats: derive from the same filtered set as the list so they match applied filters
        $filteredIds = (clone $query)->pluck('id')->all();
        $baseFiltered = CustomerDisbursement::whereIn('id', $filteredIds);
        $stats = [
            'total' => count($filteredIds),
            'pending' => (clone $baseFiltered)->where('status', CustomerDisbursement::STATUS_PENDING)->count(),
            'total_amount' => (clone $baseFiltered)->where('status', CustomerDisbursement::STATUS_APPROVED)->sum('amount'),
            'this_month' => (clone $baseFiltered)->where('status', CustomerDisbursement::STATUS_APPROVED)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];

        $disbursements = $query->paginate(15)->withQueryString();

        $branches = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $customers = $allowedBranchIds !== null
            ? Customer::visibleToBranches($allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Customer::where('is_active', true)->orderBy('name')->get();
        /** @var User|null $user */
        $user = Auth::user();
        if ($user) {
            $user->loadMissing('roleModel');
        }
        $canApprove = $user && $user->hasPermission('customer-disbursements.approve');

        return view('customer-disbursements.index', compact('disbursements', 'stats', 'customers', 'branches', 'canApprove'));
    }

    public function export(Request $request)
    {
        $query = $this->buildDisbursementsQuery($request)->with('device.product');
        $filename = 'customer-disbursements-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new CustomerDisbursementsExport($query), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Show the form for creating a new disbursement
     */
    public function create(Request $request)
    {
        $allowedBranchIds = $this->allowedBranchIds();
        $customerId = $request->get('customer_id');
        $saleId = $request->get('sale_id');

        $customers = $allowedBranchIds !== null
            ? Customer::visibleToBranches($allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Customer::where('is_active', true)->orderBy('name')->get();
        $sale = $saleId ? Sale::with('customer')->find($saleId) : null;

        // If sale is provided, use its customer
        if ($sale && !$customerId) {
            $customerId = $sale->customer_id;
        }

        // Get customer phone for default value
        $defaultPhone = null;
        $devices = collect();

        if ($customerId) {
            $customer = Customer::find($customerId);
            $defaultPhone = $customer?->phone;
            // Devices that haven't received disbursement and don't have a pending/approved disbursement (scope to user's branches)
            $devices = Device::where('customer_id', $customerId)
                ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
                ->where('has_received_disbursement', false)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('customer_disbursements')
                        ->whereColumn('customer_disbursements.device_id', 'devices.id')
                        ->whereIn('customer_disbursements.status', [CustomerDisbursement::STATUS_PENDING, CustomerDisbursement::STATUS_APPROVED]);
                })
                ->with('product')
                ->orderBy('imei')
                ->get();
        } elseif ($sale && $sale->customer) {
            $defaultPhone = $sale->customer->phone;
            $devices = Device::where('sale_id', $sale->id)
                ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
                ->where('has_received_disbursement', false)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('customer_disbursements')
                        ->whereColumn('customer_disbursements.device_id', 'devices.id')
                        ->whereIn('customer_disbursements.status', [CustomerDisbursement::STATUS_PENDING, CustomerDisbursement::STATUS_APPROVED]);
                })
                ->with('product')
                ->orderBy('imei')
                ->get();
        }

        $defaultDeviceId = ($sale && $devices->isNotEmpty()) ? $devices->first()->id : null;

        return view('customer-disbursements.create', compact('customers', 'customerId', 'sale', 'saleId', 'defaultPhone', 'devices', 'defaultDeviceId'));
    }

    /**
     * Store a new disbursement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_id' => 'required|exists:sales,id',
            'device_id' => 'nullable|exists:devices,id',
            'amount' => 'required|numeric|min:0.01',
            'disbursement_phone' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ], [
            'sale_id.required' => 'A sale is required for every customer disbursement.',
        ]);

        $saleId = $validated['sale_id'];
        $deviceId = $validated['device_id'] ?? null;

        if (!$deviceId) {
            $device = Device::where('sale_id', $saleId)
                ->where('customer_id', $validated['customer_id'])
                ->first();
            if (!$device) {
                $deviceIds = \App\Models\SaleItem::where('sale_id', $saleId)->pluck('device_id')->filter()->values()->all();
                if (!empty($deviceIds)) {
                    $device = Device::whereIn('id', $deviceIds)
                        ->where('customer_id', $validated['customer_id'])
                        ->first();
                }
            }
            if (!$device) {
                return back()->withErrors(['sale_id' => 'No device found for this sale and customer. Ensure the sale has a device attached and the customer matches.'])->withInput();
            }
            $deviceId = $device->id;
        }

        $device = Device::findOrFail($deviceId);
        if ($device->customer_id !== $validated['customer_id']) {
            return back()->withErrors(['device_id' => 'Selected device does not belong to the selected customer.'])->withInput();
        }
        if ((string) $device->sale_id !== (string) $saleId) {
            return back()->withErrors(['sale_id' => 'Selected device is not linked to this sale.'])->withInput();
        }

        $validated['device_id'] = $deviceId;

        // One disbursement per device (enforced by DB unique); update existing instead of creating a second
        $existing = CustomerDisbursement::where('device_id', $deviceId)->first();

        if ($existing) {
            if ($existing->isApproved() || $existing->isRejected()) {
                return back()->withErrors(['sale_id' => 'This sale already has an approved or rejected disbursement for this device.'])->withInput();
            }
        } elseif ($device->has_received_disbursement) {
            return back()->withErrors(['device_id' => 'This device has already received a disbursement.'])->withInput();
        }

        DB::transaction(function () use ($validated, $existing, $saleId) {
            $customer = Customer::findOrFail($validated['customer_id']);
            $data = [
                'customer_id' => $validated['customer_id'],
                'sale_id' => $saleId,
                'amount' => $validated['amount'],
                'disbursement_phone' => $validated['disbursement_phone'],
                'notes' => $validated['notes'] ?? null,
                'disbursed_by' => Auth::id(),
            ];
            if ($existing) {
                $existing->update($data);
                $disbursement = $existing;
                ActivityLog::log(
                    Auth::id(),
                    'customer_disbursement_updated',
                    "Disbursement updated to {$disbursement->amount} for {$customer->name} (pending approval)",
                    CustomerDisbursement::class,
                    $disbursement->id,
                    ['amount' => $disbursement->amount, 'customer_id' => $customer->id]
                );
            } else {
                $disbursement = CustomerDisbursement::create([
                    'customer_id' => $validated['customer_id'],
                    'sale_id' => $saleId,
                    'device_id' => $validated['device_id'],
                    'amount' => $validated['amount'],
                    'disbursement_phone' => $validated['disbursement_phone'],
                    'notes' => $validated['notes'] ?? null,
                    'disbursed_by' => Auth::id(),
                    'status' => CustomerDisbursement::STATUS_PENDING,
                ]);
                ActivityLog::log(
                    Auth::id(),
                    'customer_disbursement_created',
                    "Disbursement of {$disbursement->amount} for {$customer->name} (pending approval)",
                    CustomerDisbursement::class,
                    $disbursement->id,
                    ['amount' => $disbursement->amount, 'customer_id' => $customer->id]
                );
            }
        });

        $message = $existing
            ? 'Disbursement updated. It requires approval before it is applied.'
            : 'Customer disbursement created. It requires approval before it is applied.';
        return redirect()->route('customer-disbursements.index')->with('success', $message);
    }

    /**
     * Display a specific disbursement
     */
    public function show(CustomerDisbursement $customerDisbursement)
    {
        $customerDisbursement->load(['customer', 'sale', 'device', 'disbursedBy' => fn($q) => $q->with('branch'), 'approvedBy', 'rejectedBy']);
        /** @var User|null $user */
        $user = Auth::user();
        if ($user) {
            $user->loadMissing('roleModel');
        }
        $canApprove = $user && $user->hasPermission('customer-disbursements.approve');
        return view('customer-disbursements.show', compact('customerDisbursement', 'canApprove'));
    }

    /**
     * Approve a pending disbursement (secondary approval). Applies total_disbursed and device flag.
     */
    public function approve(CustomerDisbursement $customerDisbursement)
    {
        /** @var User $user */
        $user = Auth::user();
        $user->loadMissing('roleModel');
        if (!$user->hasPermission('customer-disbursements.approve')) {
            abort(403, 'You do not have permission to approve disbursements.');
        }
        if (!$customerDisbursement->isPending()) {
            return redirect()->route('customer-disbursements.show', $customerDisbursement)
                ->with('error', 'Only pending disbursements can be approved.');
        }

        DB::transaction(function () use ($customerDisbursement) {
            $customerDisbursement->update([
                'status' => CustomerDisbursement::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);
            $customerDisbursement->customer->increment('total_disbursed', $customerDisbursement->amount);
            if ($customerDisbursement->device_id) {
                $customerDisbursement->device->update(['has_received_disbursement' => true]);
            }
            ActivityLog::log(
                Auth::id(),
                'customer_disbursement_approved',
                "Approved disbursement of {$customerDisbursement->amount} for {$customerDisbursement->customer->name}",
                CustomerDisbursement::class,
                $customerDisbursement->id,
                ['amount' => $customerDisbursement->amount]
            );
        });

        return redirect()->route('customer-disbursements.show', $customerDisbursement)
            ->with('success', 'Disbursement approved.');
    }

    /**
     * Reject a pending disbursement.
     */
    public function reject(Request $request, CustomerDisbursement $customerDisbursement)
    {
        /** @var User $user */
        $user = Auth::user();
        $user->loadMissing('roleModel');
        if (!$user->hasPermission('customer-disbursements.approve')) {
            abort(403, 'You do not have permission to reject disbursements.');
        }
        if (!$customerDisbursement->isPending()) {
            return redirect()->route('customer-disbursements.show', $customerDisbursement)
                ->with('error', 'Only pending disbursements can be rejected.');
        }

        $validated = $request->validate(['rejection_reason' => 'nullable|string|max:1000']);

        DB::transaction(function () use ($customerDisbursement, $validated, $user) {
            // Keep the record for bookkeeping; free the device by clearing device_id so a new disbursement can be issued
            $customerDisbursement->update([
                'status' => CustomerDisbursement::STATUS_REJECTED,
                'rejected_at' => now(),
                'rejected_by' => $user?->id,
                'rejection_reason' => $validated['rejection_reason'] ?? null,
                'device_id' => null,
            ]);

            // Treat the entire sale as rejected: cancel it whether it was pending or completed
            $sale = $customerDisbursement->sale;
            if ($sale) {
                $sale->update(['status' => 'cancelled']);
                $sale->freeDevicesForResale($user?->id);
                ActivityLog::log(
                    $user?->id,
                    'sale_cancelled',
                    "Sale #{$sale->sale_number} cancelled (disbursement rejected)",
                    Sale::class,
                    $sale->id,
                    ['sale_number' => $sale->sale_number]
                );
            }

            ActivityLog::log(
                $user?->id,
                'customer_disbursement_rejected',
                "Rejected disbursement of {$customerDisbursement->amount} for {$customerDisbursement->customer->name}",
                CustomerDisbursement::class,
                $customerDisbursement->id,
                ['amount' => $customerDisbursement->amount]
            );
        });

        return redirect()->route('customer-disbursements.show', $customerDisbursement)
            ->with('success', 'Disbursement rejected and sale cancelled.');
    }

    /**
     * Show disbursements for a specific customer
     */
    public function customerDisbursements(Customer $customer)
    {
        $disbursements = CustomerDisbursement::where('customer_id', $customer->id)
            ->with(['sale', 'device', 'disbursedBy'])
            ->latest()
            ->paginate(15);

        return view('customer-disbursements.customer-index', compact('customer', 'disbursements'));
    }
}
