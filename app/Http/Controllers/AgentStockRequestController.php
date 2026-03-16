<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AgentStockRequest;
use App\Models\FieldAgentStock;
use App\Models\BranchStock;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Mail\StockActivityMail;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class AgentStockRequestController extends Controller
{
    /**
     * Index: agents see their allocations + their requests; branch staff see incoming agent requests.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tab = $request->get('tab', 'my-requests');

        // Field agents can only send; branch staff can only receive. Enforce tab by role.
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        if ($isFieldAgent && $tab === 'incoming') {
            return redirect()->route('agent-stock-requests.index', $request->only('status'));
        }
        if (!$isFieldAgent && $tab === 'my-requests') {
            return redirect()->route('agent-stock-requests.index', ['tab' => 'incoming'] + $request->only('status'));
        }

        $myAllocations = collect();
        $myRequests = collect();
        $incomingRequests = collect();
        $maxFulfillByRequest = [];
        $stats = [
            'my_pending' => 0,
            'my_approved' => 0,
            'my_rejected' => 0,
            'incoming_pending' => 0,
        ];
        $products = Product::where('is_active', true)->orderBy('name')->get();

        // Field agent: show their stock allocations and their requests
        if ($user->fieldAgentProfile && $user->branch_id) {
            $myAllocations = FieldAgentStock::with(['product', 'branch'])
                ->where('field_agent_id', $user->id)
                ->where('quantity', '>', 0)
                ->orderBy('updated_at', 'desc')
                ->get();
            $myRequests = AgentStockRequest::with(['product', 'branch', 'creator'])
                ->where('field_agent_id', $user->id)
                ->latest()
                ->paginate(10, ['*'], 'my_page')
                ->withQueryString();
            $stats['my_pending'] = AgentStockRequest::where('field_agent_id', $user->id)
                ->whereIn('status', ['pending', 'partially_fulfilled'])->whereNull('closed_at')->count();
            $stats['my_approved'] = AgentStockRequest::where('field_agent_id', $user->id)->where('status', 'approved')->count();
            $stats['my_rejected'] = AgentStockRequest::where('field_agent_id', $user->id)->where('status', 'rejected')->count();
        }

        // Branch staff (with agent-stock-requests permission): show incoming agent requests for their branch
        if ($user->branch_id && $user->hasPermission('agent-stock-requests.view')) {
            $incomingQuery = AgentStockRequest::with(['fieldAgent', 'product', 'creator'])
                ->where('branch_id', $user->branch_id)
                ->latest();
            if ($request->filled('status')) {
                $incomingQuery->where('status', $request->status);
            }
            $incomingRequests = $incomingQuery->paginate(10, ['*'], 'incoming_page')->withQueryString();
            $stats['incoming_pending'] = AgentStockRequest::where('branch_id', $user->branch_id)
                ->whereIn('status', ['pending', 'partially_fulfilled'])->whereNull('closed_at')->count();

            $branchStocks = BranchStock::where('branch_id', $user->branch_id)
                ->whereIn('product_id', $incomingRequests->pluck('product_id')->unique()->filter())
                ->get()
                ->keyBy('product_id');
            foreach ($incomingRequests as $req) {
                $bs = $branchStocks->get($req->product_id);
                $available = $bs ? (int) $bs->available_quantity : 0;
                $maxFulfillByRequest[$req->id] = min($available, $req->remainderQuantity());
            }
        }

        // Only branch staff (non–field agents) who can receive see incoming; field agents can only send
        $canReceiveRequests = !$isFieldAgent && $user->hasPermission('agent-stock-requests.view');

        return view('agent-stock-requests.index', compact(
            'myAllocations',
            'myRequests',
            'incomingRequests',
            'stats',
            'tab',
            'products',
            'maxFulfillByRequest',
            'canReceiveRequests',
            'isFieldAgent'
        ));
    }

    /**
     * Create form: agent requests additional stock from their branch only.
     */
    public function create()
    {
        $user = Auth::user();
        if (!$user->fieldAgentProfile || !$user->branch_id) {
            return redirect()->route('agent-stock-requests.index')
                ->withErrors(['branch' => 'You must be a field agent assigned to a branch to request stock.']);
        }
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $branch = Branch::find($user->branch_id);
        return view('agent-stock-requests.create', compact('products', 'branch'));
    }

    /**
     * Store: agent submits request to their branch only.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->fieldAgentProfile || !$user->branch_id) {
            return redirect()->route('agent-stock-requests.index')
                ->withErrors(['branch' => 'You must be a field agent assigned to a branch to request stock.']);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity_requested' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $agentStockRequest = AgentStockRequest::create([
            'field_agent_id' => $user->id,
            'branch_id' => $user->branch_id,
            'product_id' => $validated['product_id'],
            'quantity_requested' => $validated['quantity_requested'],
            'notes' => $validated['notes'] ?? null,
            'status' => AgentStockRequest::STATUS_PENDING,
            'created_by' => $user->id,
        ]);
        $agentStockRequest->load(['product', 'branch']);

        $branchStaff = User::usersWithAgentStockRequestPermission([$user->branch_id]);
        if ($branchStaff->isNotEmpty()) {
            $productName = $agentStockRequest->product->name ?? 'product';
            $title = 'New agent stock request';
            $message = $user->name . ' requested ' . $agentStockRequest->quantity_requested . ' units of ' . $productName . '. Review in Agent Stock Requests → Incoming requests.';
            $url = route('agent-stock-requests.index', ['tab' => 'incoming']);
            Notification::send($branchStaff, new AppNotification($title, $message, $url, 'agent_stock_request_created', ['agent_stock_request_id' => $agentStockRequest->id]));
            $emails = $branchStaff->pluck('email')->filter()->unique()->values()->all();
            if (!empty($emails)) {
                Mail::to($emails)->send(new StockActivityMail($title, $message, $url, 'View incoming requests'));
            }
        }

        return redirect()->route('agent-stock-requests.index', ['tab' => 'my-requests'])
            ->with('success', 'Stock request submitted. Your branch will review and can approve, partially approve, or reject.');
    }

    /**
     * Approve (full or partial): branch staff only. Transfers stock from branch to agent allocation.
     */
    public function approve(Request $request, AgentStockRequest $agentStockRequest)
    {
        $user = Auth::user();
        if (!$user->branch_id || $agentStockRequest->branch_id !== $user->branch_id) {
            abort(403, 'Only your branch can approve this request.');
        }
        if (!$user->hasPermission('agent-stock-requests.create')) {
            abort(403, 'You do not have permission to approve agent stock requests.');
        }
        if (!$agentStockRequest->canFulfillMore()) {
            return back()->withErrors(['status' => 'This request cannot be fulfilled further.']);
        }

        $remainder = $agentStockRequest->remainderQuantity();
        $branchStock = BranchStock::where('branch_id', $agentStockRequest->branch_id)
            ->where('product_id', $agentStockRequest->product_id)
            ->first();
        $available = $branchStock ? (int) $branchStock->available_quantity : 0;
        $maxFulfill = min($available, $remainder);
        if ($maxFulfill < 1) {
            return back()->withErrors(['quantity' => 'Insufficient branch stock to fulfill any of this request.']);
        }

        $validated = $request->validate([
            'quantity_fulfilling' => ['nullable', 'integer', 'min:1', 'max:' . $maxFulfill],
            'fulfillment_notes' => ['nullable', 'string', 'max:500'],
        ]);
        $quantityFulfilling = isset($validated['quantity_fulfilling']) ? (int) $validated['quantity_fulfilling'] : $maxFulfill;

        DB::transaction(function () use ($agentStockRequest, $quantityFulfilling, $user, $branchStock) {
            $branchStock->quantity = max(0, (int) $branchStock->quantity - $quantityFulfilling);
            $branchStock->save();

            $agentStock = FieldAgentStock::firstOrCreate(
                [
                    'field_agent_id' => $agentStockRequest->field_agent_id,
                    'branch_id' => $agentStockRequest->branch_id,
                    'product_id' => $agentStockRequest->product_id,
                ],
                ['quantity' => 0]
            );
            $agentStock->increment('quantity', $quantityFulfilling);

            $newFulfilled = $agentStockRequest->quantity_fulfilled + $quantityFulfilling;
            $isFullyFulfilled = $newFulfilled >= $agentStockRequest->quantity_requested;
            $update = [
                'quantity_fulfilled' => $newFulfilled,
                'status' => $isFullyFulfilled ? AgentStockRequest::STATUS_APPROVED : AgentStockRequest::STATUS_PARTIALLY_FULFILLED,
            ];
            if ((int) $agentStockRequest->quantity_fulfilled === 0) {
                $update['approved_by'] = $user->id;
                $update['approved_at'] = now();
            }
            $agentStockRequest->update($update);
        });

        $agentStockRequest->load(['product']);
        $fieldAgent = $agentStockRequest->fieldAgent;
        if ($fieldAgent) {
            $productName = $agentStockRequest->product->name ?? 'product';
            $fresh = $agentStockRequest->fresh();
            if ($fresh->isApproved()) {
                $title = 'Stock request approved';
                $message = 'Your request for ' . $agentStockRequest->quantity_requested . ' units of ' . $productName . ' has been fully approved. Stock has been added to your allocation.';
            } else {
                $title = 'Stock request partially approved';
                $message = $quantityFulfilling . ' units of ' . $productName . ' have been added to your allocation. Your branch may fulfill the remainder later.';
            }
            $url = route('agent-stock-requests.index', ['tab' => 'my-requests']);
            $fieldAgent->notify(new AppNotification($title, $message, $url, 'agent_stock_request_approved', ['agent_stock_request_id' => $agentStockRequest->id]));
            if ($fieldAgent->email) {
                Mail::to($fieldAgent->email)->send(new StockActivityMail($title, $message, $url, 'View my requests'));
            }
        }

        $message = $agentStockRequest->fresh()->isApproved()
            ? 'Request fully approved. Stock has been added to the agent\'s allocation.'
            : 'Partially approved. ' . $quantityFulfilling . ' units added to the agent. You can fulfill the remainder from Incoming requests.';
        return redirect()->route('agent-stock-requests.index', ['tab' => 'incoming'])->with('success', $message);
    }

    /**
     * Reject: branch staff only.
     */
    public function reject(Request $request, AgentStockRequest $agentStockRequest)
    {
        $user = Auth::user();
        if (!$user->branch_id || $agentStockRequest->branch_id !== $user->branch_id) {
            abort(403, 'Only your branch can reject this request.');
        }
        if (!$user->hasPermission('agent-stock-requests.create')) {
            abort(403, 'You do not have permission to reject agent stock requests.');
        }
        if (!$agentStockRequest->isPending()) {
            return back()->withErrors(['status' => 'This request can no longer be rejected.']);
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $agentStockRequest->update([
            'status' => AgentStockRequest::STATUS_REJECTED,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        $fieldAgent = $agentStockRequest->fieldAgent;
        if ($fieldAgent) {
            $agentStockRequest->load(['product']);
            $productName = $agentStockRequest->product->name ?? 'product';
            $title = 'Stock request rejected';
            $reason = $validated['rejection_reason'] ?? null;
            $message = 'Your request for ' . $agentStockRequest->quantity_requested . ' units of ' . $productName . ' was rejected by your branch.' . ($reason ? ' Reason: ' . Str::limit($reason, 100) : '');
            $url = route('agent-stock-requests.index', ['tab' => 'my-requests']);
            $fieldAgent->notify(new AppNotification($title, $message, $url, 'agent_stock_request_rejected', ['agent_stock_request_id' => $agentStockRequest->id]));
            if ($fieldAgent->email) {
                Mail::to($fieldAgent->email)->send(new StockActivityMail($title, $message, $url, 'View my requests'));
            }
        }

        return redirect()->route('agent-stock-requests.index', ['tab' => 'incoming'])->with('success', 'Agent stock request rejected.');
    }

    /**
     * Close: branch staff only (no more fulfillments).
     */
    public function close(Request $request, AgentStockRequest $agentStockRequest)
    {
        $user = Auth::user();
        if (!$user->branch_id || $agentStockRequest->branch_id !== $user->branch_id) {
            abort(403, 'Only your branch can close this request.');
        }
        if (!$user->hasPermission('agent-stock-requests.create')) {
            abort(403, 'You do not have permission to close agent stock requests.');
        }
        if ($agentStockRequest->isClosed()) {
            return back()->withErrors(['status' => 'This request is already closed.']);
        }
        if (!$agentStockRequest->isPending() && !$agentStockRequest->isPartiallyFulfilled()) {
            return back()->withErrors(['status' => 'Only pending or partially fulfilled requests can be closed.']);
        }

        $validated = $request->validate([
            'closed_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $agentStockRequest->update([
            'closed_at' => now(),
            'closed_by' => $user->id,
            'closed_reason' => $validated['closed_reason'] ?? null,
        ]);

        $fieldAgent = $agentStockRequest->fieldAgent;
        if ($fieldAgent) {
            $agentStockRequest->load(['product']);
            $productName = $agentStockRequest->product->name ?? 'product';
            $title = 'Stock request closed';
            $message = 'Your request for ' . $agentStockRequest->quantity_requested . ' units of ' . $productName . ' was closed by your branch. ' . $agentStockRequest->quantity_fulfilled . ' of ' . $agentStockRequest->quantity_requested . ' units were fulfilled.';
            $url = route('agent-stock-requests.index', ['tab' => 'my-requests']);
            $fieldAgent->notify(new AppNotification($title, $message, $url, 'agent_stock_request_closed', ['agent_stock_request_id' => $agentStockRequest->id]));
            if ($fieldAgent->email) {
                Mail::to($fieldAgent->email)->send(new StockActivityMail($title, $message, $url, 'View my requests'));
            }
        }

        return redirect()->route('agent-stock-requests.index', ['tab' => 'incoming'])->with('success', 'Request closed.');
    }
}
