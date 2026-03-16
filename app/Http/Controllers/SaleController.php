<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Customer;
use App\Models\Device;
use App\Models\ActivityLog;
use App\Models\ProductRegionPrice;
use App\Models\CustomerDisbursement;
use App\Models\SaleAttachment;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Models\DeviceReplacement;
use App\Services\InventoryMovementService;
use App\Exports\SalesExport;
use App\Exceptions\DeviceNotInBranchException;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;

        // Branch filter: default is all branches (no default to user's branch).
        $branchFilter = $request->get('branch');
        if ($branchFilter === '') {
            $branchFilter = null;
        }
        if ($allowedBranchIds !== null && $branchFilter !== null && !in_array($branchFilter, $allowedBranchIds, true)) {
            $branchFilter = null;
        }

        $query = Sale::with(['customer', 'branch', 'soldBy', 'items.fieldAgent', 'items.device', 'items.product.brand', 'items.product.regionPrices'])
            ->withSum('customerDisbursements', 'amount');

        // Restrict to branches this user can view (their branch + descendants)
        $query->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds));
        if ($isFieldAgent) {
            $query->whereHas('items', fn($q) => $q->where('field_agent_id', $user->id));
        }
        if ($branchFilter !== null) {
            $query->where('branch_id', $branchFilter);
        }

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where('sale_number', 'like', "%{$term}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // Stats: derive from the same filtered set as the table (by sale IDs) so they always match applied filters
        $filteredIds = (clone $query)->pluck('id')->all();
        $baseFiltered = Sale::whereIn('id', $filteredIds);
        $stats = [
            'total' => count($filteredIds),
            'today' => (clone $baseFiltered)->whereDate('created_at', today())->count(),
            'this_month' => (clone $baseFiltered)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];
        $completedIds = (clone $baseFiltered)->where('status', 'completed')->pluck('id')->all();
        $completedQuery = Sale::whereIn('id', $completedIds);
        $licenseCost = (clone $completedQuery)->sum('total_license_cost');
        $disbursementCost = CustomerDisbursement::whereIn('sale_id', $completedIds)->sum('amount');
        $stats['total_revenue'] = (clone $completedQuery)->sum('total');
        $totalBuyingPrice = Sale::totalBuyingPriceForSaleIds($completedIds);
        $stats['total_commission'] = \App\Models\SaleItem::whereIn('sale_id', $completedIds)->sum('commission_amount');
        $stats['total_cost_to_sell'] = $totalBuyingPrice + $licenseCost + $stats['total_commission'] + $disbursementCost;
        $stats['total_profit'] = $stats['total_revenue'] - $stats['total_cost_to_sell'];

        $sales = $query->latest()->paginate(15)->withQueryString();

        // Preserve effective filters in pagination links and export URL (avoids reset to user branch)
        $paginatorQuery = $request->only(['search', 'branch', 'customer_id', 'status', 'date_from', 'date_to']);
        if (!$request->has('branch') && $branchFilter !== null) {
            $paginatorQuery['branch'] = $branchFilter;
        }
        $sales->appends(array_filter($paginatorQuery, fn($v) => $v !== null));
        $exportQuery = array_diff_key($paginatorQuery, array_flip(['page']));

        $customers = $isFieldAgent
            ? Customer::whereHas('sales.items', fn($q) => $q->where('field_agent_id', $user->id))->orderBy('name')->get(['id', 'name'])
            : Customer::orderBy('name')->get(['id', 'name']);

        // Filter dropdown: only branches this user can view (their branch + descendants)
        $branches = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('sales.index', compact('sales', 'stats', 'customers', 'branches', 'branchFilter', 'exportQuery'));
    }

    /**
     * Export sales to Excel (respects same filters as index).
     */
    public function export(Request $request)
    {
        $filename = 'sales-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new SalesExport($request), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    public function create(): \Illuminate\Contracts\View\View
    {
        $branch = Auth::user()->branch;
        $regionId = $branch?->region_id;

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)
            ->with([
                'regionPrices' => fn($q) => $regionId ? $q->where('region_id', $regionId) : $q->whereRaw('1=0'),
            ])
            ->get();
        $branchStocks = BranchStock::where('branch_id', $branch->id)
            ->with('product')
            ->get()
            ->keyBy('product_id');
        // All available devices in branch (agents may sell any device in their branch, not only those assigned to them)
        $availableDevices = $this->getAvailableDevicesGroupedByProduct($branch->id);
        $allAvailableDevices = $this->getAllAvailableDevicesForBranch($branch->id);

        $canCreateDevice = Auth::user() && Auth::user()->hasPermission('devices.create');
        return view('sales.create', compact('products', 'customers', 'branchStocks', 'availableDevices', 'allAvailableDevices', 'regionId', 'canCreateDevice'));
    }

    /** @return \Illuminate\Support\Collection */
    private function getAvailableDevicesGroupedByProduct(int|string $branchId): \Illuminate\Support\Collection
    {
        return Device::availableForSale()
            ->where('branch_id', $branchId)
            ->with('product', 'branch')
            ->get()
            ->groupBy('product_id')
            ->map(fn($devices) => $devices->map(fn($device) => [
                'id' => $device->id,
                'imei' => $device->imei,
                'product_id' => $device->product_id,
                'branch_name' => $device->branch ? $device->branch->name : null,
            ]));
    }

    /** @return \Illuminate\Support\Collection */
    private function getAllAvailableDevicesForBranch(int|string $branchId): \Illuminate\Support\Collection
    {
        return Device::availableForSale()
            ->where('branch_id', $branchId)
            ->with('product', 'branch')
            ->get()
            ->map(fn($device) => [
                'id' => $device->id,
                'imei' => $device->imei,
                'product_id' => $device->product_id,
                'product_name' => $device->product->name,
                'branch_name' => $device->branch ? $device->branch->name : null,
            ]);
    }

    public function store(Request $request)
    {
        // Build validation rules conditionally
        $rules = [
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1|max:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.device_id' => 'nullable|exists:devices,id',
            'items.*.device_imei' => 'nullable|string|regex:/^[0-9]{15}$/',
            'items.*.quantity' => 'required|integer|min:1|max:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'customer_support_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];

        // Customer is optional for sales; new customer fields only used when adding a customer at sale time
        $rules['new_customer_name'] = 'nullable|string|max:255';
        $rules['new_customer_phone'] = 'nullable|string|max:255';
        $rules['new_customer_email'] = 'nullable|string|email|max:255';
        if (empty($request->customer_id)) {
            $rules['new_customer_phone'] .= '|unique:customers,phone';
            $rules['new_customer_email'] .= '|unique:customers,email';
        }
        $rules['new_customer_id_number'] = 'nullable|string|max:255';
        $rules['new_customer_address'] = 'nullable|string';
        $rules['evidence'] = ['nullable', 'array', 'max:10'];
        $rules['evidence.*'] = ['file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf'];

        $validated = $request->validate($rules);

        $branch = Auth::user()->branch;
        if (!$branch) {
            return back()->withErrors(['branch' => 'User must be assigned to a branch.'])->withInput();
        }

        $evidenceFiles = $request->file('evidence', []);

        // All sales must have a customer: either selected or created from new customer fields
        $customerId = $validated['customer_id'] ?? null;
        $wouldCreateNew = !$customerId && !empty(trim($validated['new_customer_name'] ?? '')) && !empty(trim($validated['new_customer_phone'] ?? ''));
        if (!$customerId && !$wouldCreateNew) {
            return redirect()->back()
                ->withErrors(['customer_id' => 'A customer is required. Select an existing customer or enter name and phone to create one.'])
                ->withInput();
        }

        $sale = null;
        try {
            DB::transaction(function () use ($validated, $branch, $evidenceFiles, &$sale) {
            // Handle customer - create new if not selected
            $customerId = $validated['customer_id'] ?? null;

            if (!$customerId && !empty($validated['new_customer_name']) && !empty($validated['new_customer_phone'])) {
                $customer = Customer::create([
                    'name' => $validated['new_customer_name'],
                    'email' => trim($validated['new_customer_email'] ?? '') ?: null,
                    'phone' => trim($validated['new_customer_phone'] ?? '') ?: null,
                    'id_number' => $validated['new_customer_id_number'] ?? null,
                    'address' => $validated['new_customer_address'] ?? null,
                    'is_active' => true,
                ]);
                $customerId = $customer->id;
            }

            $subtotal = 0;
            $items = [];
            $totalCommissionAmount = 0;

            // Track which devices were just created (to avoid double stock decrement)
            $newlyCreatedDeviceIds = [];

            foreach ($validated['items'] as $item) {
                // Enforce one device per customer per sale
                $item['quantity'] = 1;

                $product = Product::findOrFail($item['product_id']);

                // Handle device - create if doesn't exist. Agents may sell any available device in their branch (no assignment required).
                if (!empty($item['device_id'])) {
                    $device = Device::findOrFail($item['device_id']);

                    if ((string) $device->branch_id !== (string) $branch->id) {
                        $hostBranch = $device->branch;
                        throw new DeviceNotInBranchException(
                            'This device is not in your branch. You can only sell devices in your branch.',
                            [
                                'device_id' => $device->id,
                                'imei' => $device->imei,
                                'host_branch_id' => $device->branch_id,
                                'host_branch_name' => $hostBranch ? $hostBranch->name : 'Other branch',
                            ]
                        );
                    }
                    if ($device->product_id !== $product->id) {
                        throw new \Exception("Device does not match selected product.");
                    }
                    if (!$device->isAvailable()) {
                        throw new \Exception("Device is not available for sale.");
                    }
                } elseif (!empty($item['device_imei'])) {
                    // Use existing device by IMEI or create new one
                    $imei = trim($item['device_imei']);

                    // Validate IMEI format
                    if (!preg_match('/^[0-9]{15}$/', $imei)) {
                        throw new \Exception("IMEI must be exactly 15 digits (numbers only).");
                    }

                    $existingDevice = Device::where('imei', $imei)->first();
                    if ($existingDevice) {
                        if ((string) $existingDevice->branch_id !== (string) $branch->id) {
                            $hostBranch = $existingDevice->branch;
                            throw new DeviceNotInBranchException(
                                "IMEI '{$imei}' is not in your branch. You can only sell devices in your branch.",
                                [
                                    'device_id' => $existingDevice->id,
                                    'imei' => $existingDevice->imei,
                                    'host_branch_id' => $existingDevice->branch_id,
                                    'host_branch_name' => $hostBranch ? $hostBranch->name : 'Other branch',
                                ]
                            );
                        }
                        if ($existingDevice->product_id !== $product->id) {
                            throw new \Exception("IMEI '{$imei}' is registered for a different product.");
                        }
                        if (!$existingDevice->isAvailable()) {
                            throw new \Exception("IMEI '{$imei}' is not available for sale (may already be sold or assigned).");
                        }
                        $device = $existingDevice;
                    } else {
                        if (!Auth::user() || !Auth::user()->hasPermission('devices.create')) {
                            throw new \Exception("IMEI '{$imei}' is not in the system. You do not have permission to create devices.");
                        }
                        $device = Device::create([
                            'imei' => $imei,
                            'product_id' => $product->id,
                            'branch_id' => $branch->id,
                            'status' => 'available',
                        ]);
                        $newlyCreatedDeviceIds[] = $device->id;
                    }
                } else {
                    throw new \Exception("Either device ID or IMEI must be provided.");
                }

                $branchStock = BranchStock::where('branch_id', $branch->id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if (!$branchStock || $branchStock->available_quantity < 1) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                $itemSubtotal = $item['unit_price'];
                $subtotal += $itemSubtotal;

                // Commission from product region pricing: always look up so sale records what the seller earns.
                // Use branch's region; if branch has no region, use first region that has pricing for this product.
                $regionId = $branch->region_id ?? ProductRegionPrice::where('product_id', $product->id)->value('region_id');
                $commissionPerDevice = 0;
                if ($regionId) {
                    $commissionPerDevice = (float) (ProductRegionPrice::where('product_id', $product->id)
                        ->where('region_id', $regionId)
                        ->value('commission_per_device') ?? 0);
                }
                $commissionAmount = $commissionPerDevice * 1;
                $totalCommissionAmount += $commissionAmount;

                // License cost (cost to sell) from product – applied per sale
                $unitLicenseCost = (float) ($product->license_cost ?? 0);

                // Attribute commission to the seller (person recording the sale)
                $sellerId = Auth::id();
                $items[] = [
                    'product_id' => $item['product_id'],
                    'device_id' => $device->id,
                    'field_agent_id' => $sellerId,
                    'quantity' => 1,
                    'unit_price' => $item['unit_price'],
                    'unit_license_cost' => $unitLicenseCost,
                    'subtotal' => $itemSubtotal,
                    'commission_per_device' => $commissionPerDevice,
                    'commission_amount' => $commissionAmount,
                ];
            }

            $tax = $validated['tax'] ?? 0;
            $customerSupportAmount = $validated['customer_support_amount'] ?? 0;
            $total = $subtotal + $tax;
            $totalLicenseCost = array_sum(array_map(fn ($i) => ($i['quantity'] ?? 1) * ($i['unit_license_cost'] ?? 0), $items));

            $sale = Sale::create([
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'sold_by' => Auth::id(),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => 0,
                'total' => $total,
                'total_license_cost' => $totalLicenseCost,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update devices with sale_id and mark as sold
            foreach ($items as $item) {
                Device::where('id', $item['device_id'])->update([
                    'sale_id' => $sale->id,
                    'customer_id' => $customerId,
                    'branch_id' => $branch->id, // Ensure branch_id is set
                    'status' => 'sold',
                ]);
            }

            foreach ($items as $item) {
                $sale->items()->create($item);
            }

            // Record inventory movements (service updates BranchStock) and stock adjustments for reporting
            foreach ($items as $item) {
                $movement = InventoryMovementService::recordSale(
                    $branch->id,
                    $item['product_id'],
                    1,
                    $sale->id,
                    Auth::id()
                );

                // Record stock adjustment so it appears on the Stock Adjustments page
                StockAdjustment::create([
                    'branch_id' => $branch->id,
                    'product_id' => $item['product_id'],
                    'stock_take_id' => null,
                    'adjustment_type' => 'sale',
                    'quantity_before' => $movement->quantity_before,
                    'quantity_after' => $movement->quantity_after,
                    'adjustment_amount' => -1,
                    'reason' => "Sale #{$sale->sale_number}",
                    'adjusted_by' => Auth::id(),
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);
            }

            // Create or update customer disbursement when support amount is provided and sale has a customer (one per sale+device)
            if ($customerSupportAmount > 0 && $customerId) {
                $customer = Customer::findOrFail($customerId);

                // Get the first device from the sale that hasn't received disbursement
                $device = Device::where('sale_id', $sale->id)
                    ->where('has_received_disbursement', false)
                    ->first();

                if ($device) {
                    // Unique constraint is on device_id only (one disbursement per device). Use device_id as lookup
                    // so we update an existing disbursement for this device instead of inserting a duplicate.
                    CustomerDisbursement::updateOrCreate(
                        ['device_id' => $device->id],
                        [
                            'customer_id' => $customerId,
                            'sale_id' => $sale->id,
                            'device_id' => $device->id,
                            'amount' => $customerSupportAmount,
                            'disbursement_phone' => $customer->phone ?? '',
                            'notes' => 'Support provided during sale creation',
                            'disbursed_by' => Auth::id(),
                            'status' => CustomerDisbursement::STATUS_PENDING,
                        ]
                    );
                    // total_disbursed and device flag applied only after approval
                }
            }

            // Commission is credited to the seller when the sale is completed (see complete()).

            // Store evidence attachments
            foreach ($evidenceFiles as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('sale-evidence', 'public');
                    SaleAttachment::create([
                        'sale_id' => $sale->id,
                        'attachment_type' => SaleAttachment::TYPE_INITIATION,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => Auth::id(),
                    ]);
                }
            }

            // Log activity
            ActivityLog::log(
                Auth::id(),
                'sale_created',
                "Created sale #{$sale->sale_number} for {$sale->total}",
                Sale::class,
                $sale->id,
                ['sale_number' => $sale->sale_number, 'total' => $sale->total, 'customer_id' => $customerId]
            );
        });
        } catch (DeviceNotInBranchException $e) {
            return redirect()->back()
                ->withErrors(['device' => $e->getMessage()])
                ->with('device_request', $e->getContext())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['device' => $e->getMessage()])
                ->withInput();
        }

        return redirect()->route('sales.show', $sale)->with('success', 'Sale recorded as pending.');
    }

    public function show(Sale $sale)
    {
        $user = Auth::user();
        if (!$this->canAccessSale($user, $sale)) {
            abort(403, 'You do not have access to this sale. It belongs to another branch.');
        }
        $sale->load(['customer', 'branch', 'soldBy', 'items.product.regionPrices', 'items.device', 'items.fieldAgent', 'customerDisbursements', 'evidence.uploadedBy', 'deviceReplacements.originalDevice', 'deviceReplacements.replacementDevice', 'deviceReplacements.replacedByUser']);
        if ($user instanceof User) {
            $user->loadMissing('roleModel');
        }
        $canEditSale = $user instanceof User && $user->hasPermission('sales.view');
        $canCompleteSale = $user instanceof User && (string) $sale->sold_by === (string) $user->id;
        $canCancelSale = $sale->status === 'pending' && $user instanceof User
            && ((string) $sale->sold_by === (string) $user->id || $user->hasPermission('sales.cancel'));
        $customersForAttach = $sale->customer_id === null
            ? Customer::where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone'])
            : collect();
        // For replacement: available devices (same product as any sale item) in allowed branches
        $productsInSale = $sale->items->pluck('product_id')->unique()->values()->all();
        $allowedBranchIds = $this->canAccessSale($user, $sale) && $user->branch_id
            ? Branch::selfAndDescendantIds($user->branch_id)
            : null;
        $availableDevicesForReplacement = Device::with(['product', 'branch'])
            ->where('status', 'available')
            ->whereIn('product_id', $productsInSale)
            ->when($allowedBranchIds !== null, fn ($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->orderBy('imei')
            ->get(['id', 'imei', 'product_id', 'branch_id']);
        $canReplaceDevice = $canEditSale && in_array($sale->status, ['pending', 'completed'], true) && $sale->items->whereNotNull('device_id')->isNotEmpty();
        return view('sales.show', compact('sale', 'canEditSale', 'canCompleteSale', 'canCancelSale', 'customersForAttach', 'availableDevicesForReplacement', 'canReplaceDevice'));
    }

    /**
     * Cancel a pending sale. Allowed for the original initiator (sold_by) or anyone with sales.cancel permission.
     */
    public function cancel(Sale $sale)
    {
        $user = Auth::user();
        if (!$this->canAccessSale($user, $sale)) {
            abort(403, 'You do not have access to this sale. It belongs to another branch.');
        }
        if ($sale->status !== 'pending') {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Only pending sales can be cancelled.');
        }
        $isInitiator = $user instanceof User && (string) $sale->sold_by === (string) $user->id;
        $hasCancelPermission = $user instanceof User && $user->hasPermission('sales.cancel');
        if (!$isInitiator && !$hasCancelPermission) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Only the sale initiator or users with cancel permission can cancel this sale.');
        }

        $sale->update(['status' => 'cancelled']);

        // Free devices from disbursements so they can receive new disbursements; keep disbursement records for bookkeeping
        CustomerDisbursement::where('sale_id', $sale->id)->update(['device_id' => null]);

        $sale->freeDevicesForResale($user->id ?? null);

        ActivityLog::log(
            $user->id ?? null,
            'sale_cancelled',
            "Cancelled pending sale #{$sale->sale_number}",
            Sale::class,
            $sale->id,
            ['sale_number' => $sale->sale_number]
        );

        return redirect()->route('sales.show', $sale)->with('success', 'Sale cancelled. Devices have been returned to stock.');
    }

    /**
     * Complete a pending sale: require document upload, then set status to completed.
     */
    public function complete(Request $request, Sale $sale)
    {
        $user = Auth::user();
        if (!$this->canAccessSale($user, $sale)) {
            abort(403, 'You do not have access to this sale. It belongs to another branch.');
        }
        if ((string) $sale->sold_by !== (string) $user->id) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Only the person who initiated this sale can complete it.');
        }
        if ($sale->status !== 'pending') {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Only pending sales can be completed.');
        }
        if ($sale->hasPendingDisbursement()) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'This sale has a pending disbursement. Approve the disbursement request before completing the sale.');
        }

        $validated = $request->validate([
            'completion_document' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf'],
        ], [
            'completion_document.required' => 'A completion document is required to complete the sale.',
        ]);

        $file = $request->file('completion_document');
        if ($file && $file->isValid()) {
            $path = $file->store('sale-evidence', 'public');
            SaleAttachment::create([
                'sale_id' => $sale->id,
                'attachment_type' => SaleAttachment::TYPE_COMPLETION,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
            ]);
        }

        // Credit commission to the seller (User) when sale is completed – commissions tied to users, not field agents
        $sale->load('items');
        $totalCommission = (float) $sale->items->sum('commission_amount');
        $alreadyCredited = (float) ($sale->commission_credited_amount ?? 0);
        $toCredit = $totalCommission - $alreadyCredited;
        if ($toCredit > 0) {
            $sellerUser = User::find($sale->sold_by);
            if ($sellerUser) {
                $sellerUser->increment('total_commission_earned', $toCredit);
                $sellerUser->increment('commission_available_balance', $toCredit);
            }
        }

        $sale->update([
            'status' => 'completed',
            'commission_credited_at' => $totalCommission > 0 ? now() : $sale->commission_credited_at,
            'commission_credited_amount' => $totalCommission,
        ]);

        ActivityLog::log(
            Auth::id(),
            'sale_completed',
            "Completed sale #{$sale->sale_number}",
            Sale::class,
            $sale->id,
            ['sale_number' => $sale->sale_number]
        );

        return redirect()->route('sales.show', $sale)->with('success', 'Sale completed successfully.');
    }

    /**
     * Attach a customer to a sale that currently has no customer.
     */
    public function attachCustomer(Request $request, Sale $sale)
    {
        $user = Auth::user();
        if (!$this->canAccessSale($user, $sale)) {
            abort(403, 'You do not have access to this sale. It belongs to another branch.');
        }
        if ($sale->customer_id !== null) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'This sale already has a customer attached.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ], [
            'customer_id.required' => 'Please select a customer.',
        ]);

        $customerId = $validated['customer_id'];
        $sale->update(['customer_id' => $customerId]);

        Device::where('sale_id', $sale->id)->update(['customer_id' => $customerId]);

        ActivityLog::log(
            Auth::id(),
            'sale_customer_attached',
            "Attached customer to sale #{$sale->sale_number}",
            Sale::class,
            $sale->id,
            ['sale_number' => $sale->sale_number, 'customer_id' => $customerId]
        );

        return redirect()->route('sales.show', $sale)->with('success', 'Customer attached to sale.');
    }

    /**
     * Reopen a cancelled sale so the user can submit a new disbursement request.
     */
    public function reopen(Sale $sale)
    {
        $user = Auth::user();
        if (!$this->canAccessSale($user, $sale)) {
            abort(403, 'You do not have access to this sale. It belongs to another branch.');
        }
        if ($sale->status !== 'cancelled') {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Only cancelled sales can be reopened.');
        }

        $sale->update(['status' => 'pending']);

        ActivityLog::log(
            Auth::id(),
            'sale_reopened',
            "Reopened sale #{$sale->sale_number} (can submit new disbursement request)",
            Sale::class,
            $sale->id,
            ['sale_number' => $sale->sale_number]
        );

        return redirect()->route('sales.show', $sale)->with('success', 'Sale reopened. You can now create a new disbursement request for this sale.');
    }

    /**
     * Change or replace device on a sale. Works for both:
     * - Pending: change device before completion (e.g. wrong device selected, issue found).
     * - Completed: replace device after customer return (genuine case).
     */
    public function replaceDevice(Request $request, Sale $sale)
    {
        $user = Auth::user();
        if (!$this->canAccessSale($user, $sale)) {
            abort(403, 'You do not have access to this sale.');
        }
        if (!in_array($sale->status, ['pending', 'completed'], true)) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Only pending or completed sales can have devices changed or replaced.');
        }

        $validated = $request->validate([
            'original_device_id' => 'required|exists:devices,id',
            'replacement_device_id' => 'required|exists:devices,id|different:original_device_id',
            'reason' => 'nullable|string|max:2000',
        ], [
            'original_device_id.required' => 'Select the device being returned by the customer.',
            'replacement_device_id.required' => 'Select the replacement device.',
        ]);

        $originalDevice = Device::with('product')->findOrFail($validated['original_device_id']);
        $replacementDevice = Device::with('product')->findOrFail($validated['replacement_device_id']);

        $saleItemWithDevice = $sale->items()->where('device_id', $originalDevice->id)->first();
        if (!$saleItemWithDevice) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'The selected device is not part of this sale.');
        }
        if ($originalDevice->product_id !== $replacementDevice->product_id) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Replacement device must be the same product.');
        }
        if ($replacementDevice->status !== 'available') {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'The replacement device is not available.');
        }

        $userId = $user->id ?? null;
        $reason = $validated['reason'] ? trim($validated['reason']) : ($sale->status === 'completed' ? 'Device replacement (genuine case)' : 'Device changed before sale completion');

        DB::transaction(function () use ($sale, $originalDevice, $replacementDevice, $saleItemWithDevice, $reason, $userId) {
            DeviceReplacement::create([
                'sale_id' => $sale->id,
                'original_device_id' => $originalDevice->id,
                'replacement_device_id' => $replacementDevice->id,
                'reason' => $reason,
                'replaced_by' => $userId,
            ]);

            $branchId = $sale->branch_id;
            $customerId = $sale->customer_id;
            $productId = $originalDevice->product_id;

            // Return original device to stock
            $originalDevice->update([
                'sale_id' => null,
                'customer_id' => null,
                'status' => 'available',
                'sold_by_user_id' => null,
                'has_received_disbursement' => false,
            ]);
            $returnMovement = InventoryMovementService::recordSaleCancellation(
                $originalDevice->branch_id,
                $productId,
                1,
                $sale->id,
                $userId
            );
            StockAdjustment::create([
                'branch_id' => $originalDevice->branch_id,
                'product_id' => $productId,
                'stock_take_id' => null,
                'adjustment_type' => 'correction',
                'quantity_before' => $returnMovement->quantity_before,
                'quantity_after' => $returnMovement->quantity_after,
                'adjustment_amount' => 1,
                'reason' => "Replacement return – Sale #{$sale->sale_number} (device returned)",
                'adjusted_by' => $userId,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            // Update sale item and disbursement to point to replacement device
            $saleItemWithDevice->update(['device_id' => $replacementDevice->id]);
            CustomerDisbursement::where('sale_id', $sale->id)->where('device_id', $originalDevice->id)
                ->update(['device_id' => $replacementDevice->id]);

            // Assign replacement device to sale
            $replacementDevice->update([
                'sale_id' => $sale->id,
                'customer_id' => $customerId,
                'branch_id' => $branchId,
                'status' => 'sold',
                'sold_by_user_id' => $sale->sold_by,
            ]);
            $saleMovement = InventoryMovementService::recordSale(
                $replacementDevice->branch_id,
                $productId,
                1,
                $sale->id,
                $userId
            );
            StockAdjustment::create([
                'branch_id' => $replacementDevice->branch_id,
                'product_id' => $productId,
                'stock_take_id' => null,
                'adjustment_type' => 'sale',
                'quantity_before' => $saleMovement->quantity_before,
                'quantity_after' => $saleMovement->quantity_after,
                'adjustment_amount' => -1,
                'reason' => "Replacement – Sale #{$sale->sale_number}",
                'adjusted_by' => $userId,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);
        });

        ActivityLog::log(
            $userId,
            'device_replaced',
            "Replaced device on sale #{$sale->sale_number} (returned IMEI: {$originalDevice->imei}, replacement: {$replacementDevice->imei})",
            Sale::class,
            $sale->id,
            ['sale_number' => $sale->sale_number, 'original_device_id' => $originalDevice->id, 'replacement_device_id' => $replacementDevice->id]
        );

        $message = $sale->status === 'completed'
            ? 'Device replaced successfully. The returned device is back in stock; the replacement is now linked to this sale.'
            : 'Device changed successfully. The previous device is back in stock; the new device is now linked to this sale.';
        return redirect()->route('sales.show', $sale)->with('success', $message);
    }

    public function downloadEvidence(SaleAttachment $attachment)
    {
        $user = Auth::user();
        $sale = $attachment->sale;

        if (!$this->canAccessSale($user, $sale)) {
            abort(403, 'You do not have access to this attachment.');
        }

        $filePath = storage_path('app/public/' . $attachment->file_path);
        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath, $attachment->file_name);
    }

    /**
     * Whether the user can access this sale (own branch or a child branch).
     * Field agents are scoped by items in index; for show/edit we allow if sale's branch is in user's branch tree.
     */
    private function canAccessSale($user, Sale $sale): bool
    {
        if (!$user) {
            return false;
        }
        if (!$user->branch_id) {
            return true; // admin / no branch
        }
        $allowedBranchIds = Branch::selfAndDescendantIds($user->branch_id);
        return in_array($sale->branch_id, $allowedBranchIds, true);
    }
}
