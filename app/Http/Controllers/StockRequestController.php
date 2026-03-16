<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockRequest;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchStock;
use App\Models\Device;
use App\Models\User;
use App\Helpers\ImeiHelper;
use Maatwebsite\Excel\Facades\Excel;
use App\Mail\StockTransferActivityMail;
use App\Mail\StockActivityMail;
use App\Models\ActivityLog;
use App\Notifications\StockTransferActivityNotification;
use App\Notifications\AppNotification;
use App\Services\InventoryMovementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class StockRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Field agents get the agent-specific stock request experience (my allocations, request from my branch)
        if (!$user->relationLoaded('fieldAgentProfile')) {
            $user->load('fieldAgentProfile');
        }
        if ($user->fieldAgentProfile && $user->branch_id) {
            return redirect()->route('agent-stock-requests.index', $request->only('tab', 'status'));
        }

        if (!$user->branch_id) {
            return redirect()->route('stock-management.index')
                ->withErrors(['branch' => 'You must be assigned to a branch to view stock requests.']);
        }

        $tab = $request->get('tab', 'my');

        // My branch's requests (we requested from others)
        $myRequestsQuery = StockRequest::with(['requestingBranch', 'requestedFromBranch', 'product', 'creator', 'stockTransfer', 'stockTransfers'])
            ->where('requesting_branch_id', $user->branch_id);

        // Incoming requests (other branches requested from us; include pending and partially_fulfilled so they can fulfill/fulfill remainder)
        $incomingQuery = StockRequest::with(['requestingBranch', 'requestedFromBranch', 'product', 'creator', 'stockTransfers'])
            ->where('requested_from_branch_id', $user->branch_id);

        if ($request->filled('status')) {
            $status = $request->get('status');
            $myRequestsQuery->where('status', $status);
            $incomingQuery->where('status', $status);
        }

        $myRequests = $myRequestsQuery->latest()->paginate(10, ['*'], 'my_page')->withQueryString();
        $incomingRequests = $incomingQuery->latest()->paginate(10, ['*'], 'incoming_page')->withQueryString();

        $maxFulfillByRequest = [];
        if ($incomingRequests->isNotEmpty()) {
            $branchStocks = BranchStock::where('branch_id', $user->branch_id)
                ->whereIn('product_id', $incomingRequests->pluck('product_id')->unique()->values())
                ->get()
                ->keyBy('product_id');
            foreach ($incomingRequests as $req) {
                $available = (int) ($branchStocks->get($req->product_id)->available_quantity ?? 0);
                $maxFulfillByRequest[$req->id] = min($available, $req->remainderQuantity());
            }
        }

        $stats = [
            'my_pending' => StockRequest::where('requesting_branch_id', $user->branch_id)->whereIn('status', ['pending', 'partially_fulfilled'])->whereNull('closed_at')->count(),
            'my_approved' => StockRequest::where('requesting_branch_id', $user->branch_id)->where('status', 'approved')->count(),
            'my_rejected' => StockRequest::where('requesting_branch_id', $user->branch_id)->where('status', 'rejected')->count(),
            'incoming_pending' => StockRequest::where('requested_from_branch_id', $user->branch_id)->whereIn('status', ['pending', 'partially_fulfilled'])->whereNull('closed_at')->count(),
        ];

        $branches = Branch::where('is_active', true)->where('id', '!=', $user->branch_id)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('stock-requests.index', compact('myRequests', 'incomingRequests', 'stats', 'tab', 'branches', 'products', 'maxFulfillByRequest'));
    }

    public function show(StockRequest $stockRequest)
    {
        $user = auth()->user();
        if (!$user->branch_id) {
            return redirect()->route('stock-management.index')
                ->withErrors(['branch' => 'You must be assigned to a branch to view stock requests.']);
        }
        $isRequestingBranch = (string) $stockRequest->requesting_branch_id === (string) $user->branch_id;
        $isRequestedFromBranch = (string) $stockRequest->requested_from_branch_id === (string) $user->branch_id;
        if (!$isRequestingBranch && !$isRequestedFromBranch) {
            abort(403, 'You can only view stock requests for your branch.');
        }

        $stockRequest->load([
            'requestingBranch', 'requestedFromBranch', 'product', 'creator',
            'approvedByUser', 'rejectedByUser', 'stockTransfer', 'stockTransfers',
        ]);

        $maxFulfill = null;
        $availableQuantity = null;
        if ($isRequestedFromBranch && $stockRequest->canFulfillMore()) {
            $fromStock = BranchStock::where('branch_id', $user->branch_id)
                ->where('product_id', $stockRequest->product_id)
                ->first();
            $available = $fromStock ? (int) $fromStock->available_quantity : 0;
            $maxFulfill = min($available, $stockRequest->remainderQuantity());
            $availableQuantity = $available;
        } elseif ($isRequestedFromBranch) {
            $fromStock = BranchStock::where('branch_id', $user->branch_id)
                ->where('product_id', $stockRequest->product_id)
                ->first();
            $availableQuantity = $fromStock ? (int) $fromStock->available_quantity : 0;
        }

        return view('stock-requests.show', compact('stockRequest', 'isRequestingBranch', 'isRequestedFromBranch', 'maxFulfill', 'availableQuantity'));
    }

    public function create()
    {
        $user = auth()->user();

        // Field agents use the agent-specific request form (request from their branch only)
        if (!$user->relationLoaded('fieldAgentProfile')) {
            $user->load('fieldAgentProfile');
        }
        if ($user->fieldAgentProfile && $user->branch_id) {
            return redirect()->route('agent-stock-requests.create');
        }

        if (!$user->branch_id) {
            return redirect()->route('stock-requests.index')->withErrors(['branch' => 'You must be assigned to a branch to request stock.']);
        }

        $branches = Branch::where('is_active', true)->where('id', '!=', $user->branch_id)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('stock-requests.create', compact('branches', 'products'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->branch_id) {
            return redirect()->route('stock-requests.index')->withErrors(['branch' => 'You must be assigned to a branch to request stock.']);
        }

        $validated = $request->validate([
            'requested_from_branch_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'quantity_requested' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['requested_from_branch_id'] === $user->branch_id) {
            return back()->withErrors(['requested_from_branch_id' => 'You cannot request stock from your own branch.'])->withInput();
        }

        $stockRequest = StockRequest::create([
            'requesting_branch_id' => $user->branch_id,
            'requested_from_branch_id' => $validated['requested_from_branch_id'],
            'product_id' => $validated['product_id'],
            'quantity_requested' => $validated['quantity_requested'],
            'notes' => $validated['notes'] ?? null,
            'status' => StockRequest::STATUS_PENDING,
            'created_by' => $user->id,
        ]);

        $stockRequest->load(['requestingBranch', 'requestedFromBranch', 'product']);

        // Notify only users with stock-requests.view in the target branch (requested-from)
        $users = User::usersWithStockRequestPermission([$stockRequest->requested_from_branch_id]);
        if ($users->isNotEmpty()) {
            $title = 'Stock request from ' . $stockRequest->requestingBranch->name;
            $message = $stockRequest->requestingBranch->name . ' needs ' . $stockRequest->quantity_requested . ' units of ' . $stockRequest->product->name . '. Please approve or reject in Stock Requests → Incoming requests.';
            $url = route('stock-requests.index', ['tab' => 'incoming']);
            Notification::send($users, new AppNotification($title, $message, $url, 'stock_request_created', ['stock_request_id' => $stockRequest->id]));
            $emails = $users->pluck('email')->filter()->unique()->values()->all();
            if (!empty($emails)) {
                Mail::to($emails)->send(new StockActivityMail($title, $message, $url, 'View incoming requests'));
            }
        }

        return redirect()->route('stock-requests.index', ['tab' => 'my'])->with('success', 'Stock request submitted. The other branch will be notified and can approve or reject.');
    }

    public function approve(Request $request, StockRequest $stockRequest)
    {
        $user = auth()->user();
        if (!$user->branch_id || $stockRequest->requested_from_branch_id !== $user->branch_id) {
            abort(403, 'Only the branch that was requested from can approve this request.');
        }
        if (!$stockRequest->canFulfillMore()) {
            return back()->withErrors(['status' => 'This request cannot be fulfilled further (already fully fulfilled or rejected).']);
        }

        $remainder = $stockRequest->remainderQuantity();
        $fromBranchId = $stockRequest->requested_from_branch_id;
        $toBranchId = $stockRequest->requesting_branch_id;
        $productId = $stockRequest->product_id;

        $fromStock = BranchStock::where('branch_id', $fromBranchId)->where('product_id', $productId)->first();
        $available = $fromStock ? (int) $fromStock->available_quantity : 0;
        $maxFulfill = min($available, $remainder);
        if ($maxFulfill < 1) {
            return back()->withErrors(['quantity' => 'Insufficient stock available to fulfill any of this request.']);
        }

        $validated = $request->validate([
            'quantity_fulfilling' => ['nullable', 'integer', 'min:1', 'max:' . $maxFulfill],
            'fulfillment_reason' => ['nullable', 'string', 'max:500'],
            'imeis' => ['nullable', 'string', 'max:2000'],
            'imei_file' => ['nullable', 'file', 'max:2048'],
        ]);
        $quantityFulfilling = isset($validated['quantity_fulfilling']) ? (int) $validated['quantity_fulfilling'] : $maxFulfill;
        $fulfillmentReason = trim($validated['fulfillment_reason'] ?? '');
        $baseNotes = 'Fulfilling stock request from ' . $stockRequest->requestingBranch->name;
        $transferNotes = $fulfillmentReason !== '' ? $baseNotes . '. Reason: ' . $fulfillmentReason : $baseNotes;

        $imeis = $this->collectImeisFromRequest($request);
        if (!empty($imeis)) {
            $validation = ImeiHelper::validateImeis($imeis);
            if (!empty($validation['invalid'])) {
                $messages = [];
                foreach ($validation['invalid'] as $val => $reason) {
                    $messages[] = $val . ': ' . $reason;
                }
                return back()->withErrors(['imeis' => 'Invalid IMEI(s): ' . implode(' ', array_slice($messages, 0, 5)) . (count($messages) > 5 ? '…' : '')]);
            }
            $imeis = $validation['valid'];
            if (count($imeis) > $quantityFulfilling) {
                return back()->withErrors(['imeis' => 'Number of IMEIs (' . count($imeis) . ') cannot exceed quantity to send (' . $quantityFulfilling . ').']);
            }
            foreach ($imeis as $imeiDigits) {
                $device = Device::where('imei', $imeiDigits)->first();
                if ($device && ((string) $device->branch_id !== (string) $fromBranchId || (string) $device->product_id !== (string) $productId)) {
                    return back()->withErrors(['imeis' => 'IMEI ' . $imeiDigits . ' is at another branch or is a different product. Only devices at your branch for this product can be attached.']);
                }
            }
        }

        // Each fulfillment (full or partial) creates a separate transfer; all are linked to the same request via stock_request_id.
        $transfer = null;
        DB::transaction(function () use ($stockRequest, $fromBranchId, $toBranchId, $productId, $quantityFulfilling, $user, $fromStock, $transferNotes, $imeis, &$transfer) {
            $transfer = StockTransfer::create([
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'product_id' => $productId,
                'quantity' => $quantityFulfilling,
                'status' => 'pending',
                'created_by' => $user->id,
                'stock_request_id' => $stockRequest->id,
                'notes' => $transferNotes,
            ]);

            StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'product_id' => $productId,
                'quantity' => $quantityFulfilling,
            ]);

            InventoryMovementService::recordTransferOut($fromBranchId, $productId, $quantityFulfilling, $transfer->id, $user->id);

            ActivityLog::log(
                $user->id,
                'stock_transfer_created',
                "Created stock transfer #{$transfer->transfer_number} for {$quantityFulfilling} units (from approved stock request)",
                StockTransfer::class,
                $transfer->id,
                ['transfer_number' => $transfer->transfer_number, 'quantity' => $quantityFulfilling, 'stock_request_id' => $stockRequest->id]
            );

            $newFulfilled = $stockRequest->quantity_fulfilled + $quantityFulfilling;
            $isFullyFulfilled = $newFulfilled >= $stockRequest->quantity_requested;
            $update = [
                'quantity_fulfilled' => $newFulfilled,
                'status' => $isFullyFulfilled ? StockRequest::STATUS_APPROVED : StockRequest::STATUS_PARTIALLY_FULFILLED,
                'stock_transfer_id' => $transfer->id,
            ];
            if ((int) $stockRequest->quantity_fulfilled === 0) {
                $update['approved_by'] = $user->id;
                $update['approved_at'] = now();
            }
            $stockRequest->update($update);

            // Attach IMEIs (devices) to the transfer; auto-register any not yet in the system (stock_counted to avoid double-adjusting)
            foreach ($imeis as $imeiDigits) {
                $device = Device::firstOrCreate(
                    ['imei' => $imeiDigits],
                    [
                        'product_id' => $productId,
                        'branch_id' => $fromBranchId,
                        'status' => 'available',
                        'stock_counted' => true,
                    ]
                );
                if ((string) $device->branch_id !== (string) $fromBranchId || (string) $device->product_id !== (string) $productId) {
                    $device->update(['branch_id' => $fromBranchId, 'product_id' => $productId, 'stock_counted' => true]);
                }
                $transfer->transferDevices()->syncWithoutDetaching([$device->id => ['received_at' => null]]);
            }
        });

        if (!$transfer) {
            return back()->withErrors(['error' => 'Failed to create transfer.']);
        }
        $stockRequest->refresh();

        // Same notifications as StockTransferController::store – exact same flow
        $users = $transfer->getNotificationUsers();
        if ($users->isNotEmpty()) {
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockTransferActivityMail($transfer, 'created', []));
            Notification::send($users, new StockTransferActivityNotification($transfer, 'created', []));
        }

        $message = $stockRequest->isApproved()
            ? 'Request fully fulfilled. A new stock transfer has been created. The requesting branch can receive it when it arrives.'
            : 'Partially fulfilled. A new stock transfer has been created for ' . $quantityFulfilling . ' units (linked to this request). You can fulfill the remainder from Incoming requests.';

        return redirect()->route('stock-requests.index', ['tab' => 'incoming'])->with('success', $message);
    }

    public function reject(Request $request, StockRequest $stockRequest)
    {
        $user = auth()->user();
        if (!$user->branch_id || $stockRequest->requested_from_branch_id !== $user->branch_id) {
            abort(403, 'Only the branch that was requested from can reject this request.');
        }
        if (!$stockRequest->isPending()) {
            return back()->withErrors(['status' => 'This request can no longer be rejected.']);
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $stockRequest->update([
            'status' => StockRequest::STATUS_REJECTED,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        $stockRequest->load(['requestingBranch', 'requestedFromBranch', 'product']);
        // Notify only users with stock-requests.view in the requestor branch
        $users = User::usersWithStockRequestPermission([$stockRequest->requesting_branch_id]);
        if ($users->isNotEmpty()) {
            $reason = $validated['rejection_reason'] ?? '';
            $title = 'Stock request rejected';
            $message = $stockRequest->requestedFromBranch->name . ' rejected your request for ' . $stockRequest->quantity_requested . ' units of ' . $stockRequest->product->name . '.' . ($reason !== '' ? ' Reason: ' . \Illuminate\Support\Str::limit($reason, 100) : '');
            $url = route('stock-requests.index', ['tab' => 'my']);
            Notification::send($users, new AppNotification($title, $message, $url, 'stock_request_rejected', ['stock_request_id' => $stockRequest->id]));
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockActivityMail($title, $message, $url, 'View requests'));
        }

        return redirect()->route('stock-requests.index', ['tab' => 'incoming'])->with('success', 'Stock request rejected.');
    }

    /**
     * Close a partially fulfilled (or pending) request so no more fulfillments are expected.
     */
    public function close(Request $request, StockRequest $stockRequest)
    {
        $user = auth()->user();
        if (!$user->branch_id || $stockRequest->requested_from_branch_id !== $user->branch_id) {
            abort(403, 'Only the branch that was requested from can close this request.');
        }
        if ($stockRequest->isClosed()) {
            return back()->withErrors(['status' => 'This request is already closed.']);
        }
        if (!$stockRequest->isPending() && !$stockRequest->isPartiallyFulfilled()) {
            return back()->withErrors(['status' => 'Only pending or partially fulfilled requests can be closed.']);
        }

        $validated = $request->validate([
            'closed_reason' => ['nullable', 'string', 'max:500'],
        ]);
        $closedReason = trim($validated['closed_reason'] ?? '');

        $stockRequest->update([
            'closed_at' => now(),
            'closed_by' => $user->id,
            'closed_reason' => $closedReason !== '' ? $closedReason : null,
        ]);

        $stockRequest->load(['requestingBranch', 'requestedFromBranch', 'product']);
        $users = User::usersWithStockRequestPermission([$stockRequest->requesting_branch_id]);
        if ($users->isNotEmpty()) {
            $title = 'Stock request closed';
            $fulfilled = (int) $stockRequest->quantity_fulfilled;
            $requested = (int) $stockRequest->quantity_requested;
            $message = $stockRequest->requestedFromBranch->name . ' closed this request. You received ' . $fulfilled . ' of ' . $requested . ' units of ' . $stockRequest->product->name . '. No further units will be sent.';
            if ($closedReason !== '') {
                $message .= ' Reason: ' . \Illuminate\Support\Str::limit($closedReason, 100);
            }
            $url = route('stock-requests.index', ['tab' => 'my']);
            Notification::send($users, new AppNotification($title, $message, $url, 'stock_request_closed', ['stock_request_id' => $stockRequest->id]));
            Mail::to($users->pluck('email')->filter()->unique()->values()->all())->send(new StockActivityMail($title, $message, $url, 'View requests'));
        }

        return redirect()->route('stock-requests.index', ['tab' => 'incoming'])->with('success', 'Request closed. The requesting branch has been notified.');
    }

    protected function normalizeImei(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        $value = trim($value);
        return preg_replace('/\D/', '', $value);
    }

    protected function parseImeisFromRequest(?string $value): array
    {
        $value = $value ?? '';
        if ($value === '') {
            return [];
        }
        $lines = preg_split('/[\r\n,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        $imeis = [];
        foreach ($lines as $line) {
            $imei = $this->normalizeImei($line);
            if ($imei !== '' && strlen($imei) === 15) {
                $imeis[] = $imei;
            }
        }
        return array_values(array_unique($imeis));
    }

    protected function parseImeisFromUploadedFile($file): array
    {
        if (!$file || !$file->isValid()) {
            return [];
        }
        $path = $file->getRealPath();
        $ext = strtolower($file->getClientOriginalExtension() ?? '');
        $rows = [];
        if ($ext === 'csv' || $ext === 'txt') {
            $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                return [];
            }
            foreach ($lines as $line) {
                $rows[] = str_getcsv($line);
            }
        } else {
            try {
                $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                    public function array(array $array) { return $array; }
                }, $file);
                $rows = $data[0] ?? [];
            } catch (\Throwable $e) {
                return [];
            }
        }
        if (empty($rows)) {
            return [];
        }
        $header = $rows[0];
        $imeiColIndex = 0;
        foreach ($header as $i => $cell) {
            if (stripos(preg_replace('/\s+/', '', (string) $cell), 'imei') !== false) {
                $imeiColIndex = $i;
                break;
            }
        }
        $imeis = [];
        for ($r = 1; $r < count($rows); $r++) {
            $val = $rows[$r][$imeiColIndex] ?? null;
            if ($val !== null && $val !== '') {
                $imei = $this->normalizeImei((string) $val);
                if (strlen($imei) === 15) {
                    $imeis[] = $imei;
                }
            }
        }
        return array_values(array_unique($imeis));
    }

    protected function collectImeisFromRequest(Request $request): array
    {
        $fromText = $this->parseImeisFromRequest($request->input('imeis'));
        $fromFile = $request->hasFile('imei_file') ? $this->parseImeisFromUploadedFile($request->file('imei_file')) : [];
        $merged = array_merge($fromText, $fromFile);
        return array_values(array_unique($merged));
    }
}
