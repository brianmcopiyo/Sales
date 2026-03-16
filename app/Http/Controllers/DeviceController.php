<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Device;
use App\Models\DeviceStatusLog;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\BranchStock;
use App\Models\ProductRegionPrice;
use App\Models\CustomerDisbursement;
use App\Models\FieldAgent;
use App\Models\StockAdjustment;
use App\Services\InventoryMovementService;
use App\Exports\DevicesExport;
use App\Exports\OverstayedDevicesExport;
use App\Imports\DevicesImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        $query = Device::with(['product', 'customer', 'branch', 'sale.soldBy']);

        $branchFilter = $request->get('branch_id') ?? $request->get('branch');
        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;

        if ($isFieldAgent) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($branchFilter && ($allowedBranchIds === null || in_array($branchFilter, $allowedBranchIds, true))) {
            $query->where('branch_id', $branchFilter);
        } elseif ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where('imei', 'like', "%{$term}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $devices = $query->latest()->paginate(15)->withQueryString();

        $statsQuery = Device::query();
        if ($isFieldAgent) {
            $statsQuery->where('branch_id', $user->branch_id);
        } elseif ($branchFilter && ($allowedBranchIds === null || in_array($branchFilter, $allowedBranchIds, true))) {
            $statsQuery->where('branch_id', $branchFilter);
        } elseif ($user->branch_id) {
            $statsQuery->where('branch_id', $user->branch_id);
        }
        if ($request->filled('search')) {
            $term = $request->get('search');
            $statsQuery->where('imei', 'like', "%{$term}%");
        }
        if ($request->filled('status')) {
            $statsQuery->where('status', $request->get('status'));
        }
        if ($request->filled('product_id')) {
            $statsQuery->where('product_id', $request->get('product_id'));
        }
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('created_at', '<=', $request->get('date_to'));
        }
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'available' => (clone $statsQuery)->where('status', 'available')->count(),
            'sold' => (clone $statsQuery)->where('status', 'sold')->count(),
            'assigned' => (clone $statsQuery)->whereNotNull('customer_id')->count(),
        ];

        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);
        $branches = $allowedBranchIds
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('devices.index', compact('devices', 'stats', 'products', 'branches'));
    }

    /**
     * Overstayed devices: unsold devices that have been in stock longer than the given threshold (days).
     */
    public function overstayed(Request $request)
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
        $branchFilter = $request->get('branch_id') ?? $request->get('branch');
        $days = max(1, (int) $request->get('days', 5));
        $cutoff = now()->subDays($days);

        $baseQuery = Device::query()
            ->whereIn('status', ['available', 'assigned'])
            ->where('created_at', '<=', $cutoff);

        if ($isFieldAgent) {
            $baseQuery->where('branch_id', $user->branch_id);
        } elseif ($branchFilter && ($allowedBranchIds === null || in_array($branchFilter, $allowedBranchIds, true))) {
            $baseQuery->where('branch_id', $branchFilter);
        } elseif ($user->branch_id) {
            $baseQuery->whereIn('branch_id', $allowedBranchIds ?? []);
        }

        if ($request->filled('search')) {
            $term = $request->get('search');
            $baseQuery->where('imei', 'like', "%{$term}%");
        }
        if ($request->filled('product_id')) {
            $baseQuery->where('product_id', $request->get('product_id'));
        }

        $devices = (clone $baseQuery)
            ->with(['product', 'branch'])
            ->orderBy('created_at')
            ->paginate(20)
            ->withQueryString();

        // Add days_in_stock to each device (whole days only; Carbon v3 diffInDays returns float)
        $devices->getCollection()->transform(function ($device) {
            $device->days_in_stock = (int) $device->created_at->diffInDays(now());
            return $device;
        });

        // Analytics
        $statsQuery = clone $baseQuery;
        $totalOverstayed = $statsQuery->count();

        $byBranch = (clone $baseQuery)->select('branch_id')->selectRaw('count(*) as count')
            ->groupBy('branch_id')->with('branch:id,name')->get()->keyBy('branch_id');

        $byProduct = (clone $baseQuery)->select('product_id')->selectRaw('count(*) as count')
            ->groupBy('product_id')->with('product:id,name')->get()->keyBy('product_id');

        $now = now();
        $ageFirst = $days < 30
            ? (clone $baseQuery)->where('created_at', '>', $now->copy()->subDays(30))->count()
            : 0;
        $age30_60 = (clone $baseQuery)->where('created_at', '<=', $now->copy()->subDays(30))->where('created_at', '>', $now->copy()->subDays(60))->count();
        $age60_90 = (clone $baseQuery)->where('created_at', '<=', $now->copy()->subDays(60))->where('created_at', '>', $now->copy()->subDays(90))->count();
        $age90Plus = (clone $baseQuery)->where('created_at', '<=', $now->copy()->subDays(90))->count();

        $stats = [
            'total' => $totalOverstayed,
            'by_branch' => $byBranch,
            'by_product' => $byProduct,
            'age_first' => $ageFirst,
            'age_30_60' => $age30_60,
            'age_60_90' => $age60_90,
            'age_90_plus' => $age90Plus,
        ];

        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);
        $branches = $allowedBranchIds
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('devices.overstayed', compact('devices', 'stats', 'products', 'branches', 'days'));
    }

    public function export(Request $request)
    {
        $filename = 'devices-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new DevicesExport($request), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Export overstayed devices to Excel (respects same filters as overstayed page).
     */
    public function exportOverstayed(Request $request)
    {
        $filename = 'overstayed-devices-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new OverstayedDevicesExport($request), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    public function importForm()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);
        return view('devices.import', compact('products'));
    }

    public function importSubmit(Request $request)
    {
        $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'product_id' => 'nullable|exists:products,id',
            'imeis' => 'nullable|string|max:10000',
            'file' => 'nullable|file|mimes:csv,xlsx,xls|max:5120',
        ]);

        $user = Auth::user();
        $targetBranchId = $request->input('branch_id') ?: $user->branch_id;
        if (!$targetBranchId) {
            return redirect()->route('devices.index')->withErrors(['branch_id' => 'You must select a branch or be assigned to a branch to import devices.']);
        }

        $targetBranch = Branch::find($targetBranchId);
        $targetBranchName = $targetBranch ? $targetBranch->name : 'Unknown';

        if ($request->hasFile('file')) {
            return $this->importFromFile($request, $user, $targetBranchId, $targetBranchName);
        }

        $productId = $request->input('product_id');
        if (!$productId) {
            return redirect()->route('devices.index')->withErrors(['product_id' => 'Select a product when entering IMEIs manually.']);
        }

        $imeiList = $this->parseImeisFromText($request->input('imeis', ''));
        if (empty($imeiList)) {
            return redirect()->route('devices.index')->withErrors(['imeis' => 'Provide IMEI numbers (one per line or comma-separated) or upload a CSV/Excel file.']);
        }

        $product = Product::findOrFail($productId);
        $imported = 0;
        $errors = [];
        $alreadyExisted = [];
        $added = [];
        foreach ($imeiList as $rowNum => $imei) {
            $imei = preg_replace('/\D/', '', $imei);
            if (strlen($imei) !== 15) {
                $errors[] = 'Row ' . ($rowNum + 1) . ': IMEI must be exactly 15 digits (got ' . strlen($imei) . ').';
                continue;
            }
            $existingDevice = Device::where('imei', $imei)->with('branch')->first();
            if ($existingDevice) {
                $branchName = $existingDevice->branch ? $existingDevice->branch->name : 'Unknown';
                $alreadyExisted[] = ['imei' => $imei, 'branch' => $branchName];
                $errors[] = 'Row ' . ($rowNum + 1) . ': IMEI ' . $imei . ' already exists.';
                continue;
            }
            try {
                Device::create([
                    'imei' => $imei,
                    'product_id' => $product->id,
                    'branch_id' => $targetBranchId,
                    'status' => 'available',
                ]);
                $imported++;
                $added[] = $imei;
            } catch (\Throwable $e) {
                $errors[] = 'Row ' . ($rowNum + 1) . ': ' . $e->getMessage();
            }
        }

        $importReport = [
            'already_existed' => $alreadyExisted,
            'added' => $added,
            'branch_name' => $targetBranchName,
        ];

        if (count($errors) > 0) {
            return redirect()->route('devices.index')
                ->with('import_errors', $errors)
                ->with('import_report', $importReport)
                ->with($imported > 0 ? 'success' : 'warning', $imported > 0 ? "{$imported} device(s) imported. Some rows had errors." : 'No devices were imported. Please fix the errors below.');
        }
        return redirect()->route('devices.index')
            ->with('import_report', $importReport)
            ->with('success', $imported . ' device(s) imported successfully.');
    }

    protected function importFromFile(Request $request, $user, string $targetBranchId, string $targetBranchName): \Illuminate\Http\RedirectResponse
    {
        $defaultProductId = $request->input('product_id');
        $import = new DevicesImport($targetBranchId, $defaultProductId);
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $f) {
                $errors[] = 'Row ' . $f->row() . ': ' . implode(', ', $f->errors());
            }
            return redirect()->route('devices.index')->withErrors(['file' => $errors]);
        }

        $imported = $import->getImportedCount();
        $errors = $import->getErrors();
        $addedRows = $import->getAdded();
        $addedToBranch = array_map(
            fn ($a) => $a['imei'],
            array_filter($addedRows, fn ($a) => $a['branch_id'] === $targetBranchId)
        );
        $importReport = [
            'already_existed' => $import->getAlreadyExisted(),
            'added' => array_values($addedToBranch),
            'branch_name' => $targetBranchName,
        ];

        if (count($errors) > 0) {
            return redirect()->route('devices.index')
                ->with('import_errors', $errors)
                ->with('import_report', $importReport)
                ->with($imported > 0 ? 'success' : 'warning', $imported > 0 ? "{$imported} device(s) imported. Some rows had errors." : 'No devices were imported. Please fix the errors below.');
        }
        return redirect()->route('devices.index')
            ->with('import_report', $importReport)
            ->with('success', $imported . ' device(s) imported successfully.');
    }

    protected function parseImeisFromText(string $text): array
    {
        $lines = preg_split('/[\r\n,]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_filter(array_map('trim', $lines)));
    }

    public function downloadSampleCsv(): BinaryFileResponse
    {
        $path = resource_path('samples/devices-import-sample-imei.csv');
        return response()->download($path, 'devices-import-sample.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function downloadFullSampleCsv(): BinaryFileResponse
    {
        $path = resource_path('samples/devices-import-sample-full.csv');
        return response()->download($path, 'devices-import-sample-full.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Download sample CSV for IMEI reconciliation (single column: imei).
     */
    public function downloadReconcileImeiSample(): BinaryFileResponse
    {
        $path = resource_path('samples/reconcile-imei-sample.csv');
        return response()->download($path, 'reconcile-imei-sample.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function create()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        return view('devices.create', compact('products', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'imei' => 'required|string|regex:/^[0-9]{15}$/|unique:devices,imei',
            'product_id' => 'required|exists:products,id',
            'customer_id' => 'nullable|exists:customers,id',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        if (!$user->branch_id) {
            return back()->withErrors(['branch' => 'You must be assigned to a branch to create devices.'])->withInput();
        }

        // Set branch_id to the logged-in user's branch
        $validated['branch_id'] = $user->branch_id;

        // Status: assigned when a customer is attached on creation, otherwise available
        $validated['status'] = !empty($validated['customer_id']) ? 'assigned' : 'available';

        Device::create($validated);
        return redirect()->route('devices.index')->with('success', 'Device created successfully.');
    }

    /**
     * Allow access if the user has no branch (e.g. global admin) or the device's branch
     * is the user's branch or a descendant branch.
     */
    private function userCanAccessDevice($user, Device $device): bool
    {
        if (!$user->branch_id) {
            return true;
        }
        $allowedBranchIds = Branch::selfAndDescendantIds($user->branch_id);

        return in_array($device->branch_id, $allowedBranchIds, true);
    }

    public function show(Device $device)
    {
        $user = Auth::user();
        if (!$this->userCanAccessDevice($user, $device)) {
            abort(403, 'You do not have access to this device. It belongs to another branch.');
        }
        $device->load(['product', 'customer', 'sale.items', 'sale.soldBy', 'sale.customerDisbursements', 'saleItem', 'branch', 'soldBy', 'statusLogs.performedBy']);
        return view('devices.show', compact('device'));
    }

    public function edit(Device $device)
    {
        $user = Auth::user();
        if (!$this->userCanAccessDevice($user, $device)) {
            abort(403, 'You do not have access to this device. It belongs to another branch.');
        }
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        return view('devices.edit', compact('device', 'products', 'customers'));
    }

    public function update(Request $request, Device $device)
    {
        $user = Auth::user();
        if (!$this->userCanAccessDevice($user, $device)) {
            abort(403, 'You do not have access to this device. It belongs to another branch.');
        }
        $validated = $request->validate([
            'imei' => 'required|string|regex:/^[0-9]{15}$/|unique:devices,imei,' . $device->id,
            'product_id' => 'required|exists:products,id',
            'customer_id' => 'nullable|exists:customers,id',
            'notes' => 'nullable|string',
        ]);

        if ($device->isSold()) {
            return redirect()->route('devices.index')->withErrors(['error' => 'Cannot edit a sold device.']);
        }

        // Keep status in sync with customer: attached to customer => assigned, no customer => available
        $validated['status'] = !empty($validated['customer_id']) ? 'assigned' : 'available';
        $device->update($validated);
        return redirect()->route('devices.index')->with('success', 'Device updated successfully.');
    }

    /**
     * IMEI reconciliation: upload a file of valid IMEIs; delete devices not in the list.
     * Never deletes sold devices or devices attached to a sale.
     */
    public function reconcileImei(Request $request)
    {
        $user = Auth::user();
        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;

        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'scope' => 'required|in:general,product',
            'product_id' => 'required_if:scope,product|nullable|exists:products,id',
        ], [
            'file.required' => 'Upload a file containing your valid IMEI numbers.',
            'scope.required' => 'Choose General or Per product.',
            'product_id.required_if' => 'Select a product when using per-product reconciliation.',
        ]);

        $scope = $request->input('scope');
        $productId = $request->input('product_id');

        $validImeis = $this->parseImeiFile($request->file('file'));
        if (empty($validImeis)) {
            return redirect()->route('devices.index')
                ->withErrors(['file' => 'No valid IMEI numbers found in the file. Use CSV, Excel, or plain text (one IMEI per line or comma-separated).']);
        }

        // Only consider devices that are safe to delete: not sold, not attached to any sale, not in replacement history
        $query = Device::query()
            ->whereNull('sale_id')
            ->where('status', '!=', 'sold')
            ->whereDoesntHave('replacementsAsOriginal')
            ->whereDoesntHave('replacementsAsReplacement')
            ->whereNotIn('imei', $validImeis);

        if ($scope === 'product' && $productId) {
            $query->where('product_id', $productId);
        }

        if ($allowedBranchIds !== null) {
            $query->whereIn('branch_id', $allowedBranchIds);
        }

        $toDelete = $query->get();
        $deletedCount = 0;
        $deletedImeis = [];

        DB::transaction(function () use ($toDelete, $user, &$deletedCount, &$deletedImeis) {
            $userId = $user->id ?? null;
            foreach ($toDelete as $device) {
                if ($device->branch_id && $device->product_id) {
                    $movement = InventoryMovementService::record(
                        $device->branch_id,
                        $device->product_id,
                        'adjustment',
                        -1,
                        null,
                        null,
                        'IMEI reconciliation – device removed (not in master list)',
                        'Device IMEI: ' . $device->imei,
                        $userId
                    );
                    StockAdjustment::create([
                        'branch_id' => $device->branch_id,
                        'product_id' => $device->product_id,
                        'stock_take_id' => null,
                        'adjustment_type' => 'correction',
                        'quantity_before' => $movement->quantity_before,
                        'quantity_after' => $movement->quantity_after,
                        'adjustment_amount' => -1,
                        'reason' => 'IMEI reconciliation – device removed (not in master list): ' . $device->imei,
                        'adjusted_by' => $userId,
                        'approved_by' => $userId,
                        'approved_at' => now(),
                    ]);
                }
                $deletedImeis[] = $device->imei;
                $device->delete();
                $deletedCount++;
            }
        });

        $scopeLabel = $scope === 'product' && $productId ? 'for selected product' : 'all products';
        $message = $deletedCount === 0
            ? "No devices to remove ({$scopeLabel}). All devices are in your uploaded list (or are sold/attached to a sale and were skipped)."
            : "Reconciliation complete ({$scopeLabel}). {$deletedCount} device(s) removed (IMEIs not in master list). Sold devices and devices attached to a sale were not touched.";

        return redirect()->route('devices.index')
            ->with('success', $message)
            ->with('reconcile_deleted_count', $deletedCount)
            ->with('reconcile_deleted_imeis', $deletedImeis);
    }

    /**
     * Parse uploaded file to a set of normalized IMEI strings (trimmed, one per value).
     */
    private function parseImeiFile($file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $imeis = [];

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            try {
                $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                    public function array(array $array) { return $array; }
                }, $file);
                foreach ($data[0] ?? [] as $rowIndex => $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    foreach ($row as $cell) {
                        $val = trim((string) $cell);
                        if ($val === '' || strtolower($val) === 'imei') {
                            continue;
                        }
                        if (preg_match('/^\d{15}$/', $val) || strlen($val) >= 10) {
                            $imeis[$val] = true;
                        }
                    }
                }
                $imeis = array_keys($imeis);
            } catch (\Throwable $e) {
                $imeis = [];
            }
        } else {
            $content = file_get_contents($file->getRealPath());
            $lines = preg_split('/\r\n|\r|\n/', $content);
            foreach ($lines as $line) {
                foreach (array_map('trim', str_getcsv($line)) as $cell) {
                    $cell = trim($cell);
                    if ($cell !== '' && (preg_match('/^\d{15}$/', $cell) || strlen($cell) >= 10)) {
                        $imeis[$cell] = true;
                    }
                }
            }
            $imeis = array_keys($imeis);
        }

        return array_values(array_unique($imeis));
    }

    public function destroy(Device $device)
    {
        $user = Auth::user();
        if (!$this->userCanAccessDevice($user, $device)) {
            abort(403, 'You do not have access to this device. It belongs to another branch.');
        }
        // Prevent deletion if device is sold
        if ($device->isSold()) {
            return redirect()->route('devices.index')->withErrors(['error' => 'Cannot delete a sold device.']);
        }

        $device->delete();
        return redirect()->route('devices.index')->with('success', 'Device deleted successfully.');
    }

    /**
     * Update device status via link action; logs the user who performed the action.
     * Once sold, no further status changes are allowed.
     */
    public function updateStatus(Request $request, Device $device)
    {
        $user = Auth::user();
        if (!$this->userCanAccessDevice($user, $device)) {
            abort(403, 'You do not have access to this device. It belongs to another branch.');
        }
        if ($device->isSold()) {
            return back()->withErrors(['error' => 'Cannot change status of a sold device.']);
        }

        $validated = $request->validate([
            'status' => 'required|in:available,assigned,sold',
        ]);

        $user = Auth::user();
        $newStatus = $validated['status'];

        // "Sold" must be done via mark-sold form (creates sale record)
        if ($newStatus === 'sold') {
            return redirect()->route('devices.mark-sold.form', $device)
                ->with('info', 'Use the form below to record the sale with pricing and optional support.');
        }

        DB::transaction(function () use ($device, $newStatus, $user) {
            $device->update(['status' => $newStatus]);

            DeviceStatusLog::create([
                'device_id' => $device->id,
                'status' => $newStatus,
                'performed_by_user_id' => $user->id,
            ]);
        });

        return back()->with('success', 'Device marked as ' . $newStatus . '.');
    }

    /**
     * Show form to mark device as sold: creates a sale record with pricing and optional support.
     */
    public function markSoldForm(Device $device)
    {
        $user = Auth::user();
        if (!$this->userCanAccessDevice($user, $device)) {
            abort(403, 'You do not have access to this device. It belongs to another branch.');
        }
        if ($device->isSold()) {
            return redirect()->route('devices.show', $device)->withErrors(['error' => 'This device is already sold.']);
        }

        if (!$user->branch_id) {
            return back()->withErrors(['branch' => 'You must be assigned to a branch to sell devices.'])->withInput();
        }

        $device->load(['product', 'branch.region']);
        $branch = $device->branch;
        $regionId = $branch?->region_id;

        $defaultUnitPrice = 0;
        if ($regionId) {
            $prp = ProductRegionPrice::where('product_id', $device->product_id)
                ->where('region_id', $regionId)
                ->first();
            if ($prp) {
                $defaultUnitPrice = (float) $prp->selling_price;
            }
        }

        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('devices.mark-sold', compact('device', 'defaultUnitPrice', 'customers'));
    }

    /**
     * Process mark-as-sold: create sale, sale item, update device, optional disbursement, inventory.
     */
    public function markSoldSubmit(Request $request, Device $device)
    {
        $user = Auth::user();
        if (!$this->userCanAccessDevice($user, $device)) {
            abort(403, 'You do not have access to this device. It belongs to another branch.');
        }
        if ($device->isSold()) {
            return redirect()->route('devices.show', $device)->withErrors(['error' => 'This device is already sold.']);
        }

        if (!$user->branch_id) {
            return back()->withErrors(['branch' => 'You must be assigned to a branch to sell devices.'])->withInput();
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'unit_price' => 'required|numeric|min:0',
            'customer_support_amount' => 'nullable|numeric|min:0',
        ], [
            'customer_id.required' => 'A customer is required for every sale.',
        ]);

        $branch = $device->branch;
        $customerId = $validated['customer_id'];
        $unitPrice = (float) $validated['unit_price'];
        $customerSupportAmount = (float) ($validated['customer_support_amount'] ?? 0);

        DB::transaction(function () use ($device, $branch, $customerId, $unitPrice, $customerSupportAmount, $user) {
            $subtotal = $unitPrice;
            $total = $subtotal;

            $product = $device->product;
            $unitLicenseCost = (float) ($product->license_cost ?? 0);
            $totalLicenseCost = $unitLicenseCost * 1;

            $sale = Sale::create([
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'sold_by' => $user->id,
                'subtotal' => $subtotal,
                'tax' => 0,
                'discount' => 0,
                'total' => $total,
                'total_license_cost' => $totalLicenseCost,
                'status' => 'completed',
                'notes' => null,
            ]);

            // Commission tied to user (seller), not only field agents
            $commissionPerDevice = 0;
            $commissionAmount = 0;
            if ($branch->region_id) {
                $commissionPerDevice = (float) (ProductRegionPrice::where('product_id', $device->product_id)
                    ->where('region_id', $branch->region_id)
                    ->value('commission_per_device') ?? 0);
                $commissionAmount = $commissionPerDevice * 1;
            }

            $sale->items()->create([
                'product_id' => $device->product_id,
                'device_id' => $device->id,
                'field_agent_id' => $user->id,
                'quantity' => 1,
                'unit_price' => $unitPrice,
                'unit_license_cost' => $unitLicenseCost,
                'subtotal' => $unitPrice,
                'commission_per_device' => $commissionPerDevice,
                'commission_amount' => $commissionAmount,
            ]);

            $device->update([
                'sale_id' => $sale->id,
                'customer_id' => $customerId,
                'status' => 'sold',
                'sold_by_user_id' => $user->id,
                'sold_at' => now(),
            ]);

            DeviceStatusLog::create([
                'device_id' => $device->id,
                'status' => 'sold',
                'performed_by_user_id' => $user->id,
            ]);

            if ($customerSupportAmount > 0 && $customerId) {
                $customer = Customer::findOrFail($customerId);
                \App\Models\CustomerDisbursement::updateOrCreate(
                    [
                        'sale_id' => $sale->id,
                        'device_id' => $device->id,
                    ],
                    [
                        'customer_id' => $customerId,
                        'sale_id' => $sale->id,
                        'device_id' => $device->id,
                        'amount' => $customerSupportAmount,
                        'disbursement_phone' => $customer->phone ?? '',
                        'notes' => 'Support attached when marking device as sold',
                        'disbursed_by' => $user->id,
                        'status' => \App\Models\CustomerDisbursement::STATUS_PENDING,
                    ]
                );
                // total_disbursed and device flag applied only after approval
            }

            InventoryMovementService::recordSale(
                $branch->id,
                $device->product_id,
                1,
                $sale->id,
                $user->id
            );

            if ($commissionAmount > 0) {
                $user->increment('total_commission_earned', $commissionAmount);
                $user->increment('commission_available_balance', $commissionAmount);
            }
        });

        return redirect()->route('devices.show', $device)->with('success', 'Device marked as sold and sale record created.');
    }
}
