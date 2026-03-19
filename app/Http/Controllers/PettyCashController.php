<?php

namespace App\Http\Controllers;

use App\Helpers\SmsHelper;
use App\Mail\StockActivityMail;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\PettyCashFund;
use App\Models\PettyCashRequest;
use App\Models\PettyCashCategory;
use App\Models\PettyCashReplenishment;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class PettyCashController extends Controller
{
    protected function allowedBranchIds(): ?array
    {
        $user = Auth::user();
        return $user && $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
    }

    public function index(Request $request)
    {
        $allowedBranchIds = $this->allowedBranchIds();
        $funds = PettyCashFund::with(['branch', 'custodian'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->where('is_active', true)
            ->orderBy('branch_id')
            ->get();

        $query = PettyCashRequest::with(['fund.branch', 'categoryRelation', 'requestedByUser', 'approvedByUser'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereHas('fund', fn($f) => $f->whereIn('branch_id', $allowedBranchIds)))
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('branch_id')) {
            $query->whereHas('fund', fn($f) => $f->where('branch_id', $request->branch_id));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $filteredIds = (clone $query)->pluck('id')->all();
        $baseFiltered = PettyCashRequest::whereIn('id', $filteredIds);
        $stats = [
            'total' => count($filteredIds),
            'pending' => (clone $baseFiltered)->where('status', PettyCashRequest::STATUS_PENDING)->count(),
            'disbursed_this_month' => (clone $baseFiltered)->where('status', PettyCashRequest::STATUS_DISBURSED)
                ->whereMonth('disbursed_at', now()->month)
                ->whereYear('disbursed_at', now()->year)
                ->sum('amount'),
            'approved_pending_disburse' => (clone $baseFiltered)->where('status', PettyCashRequest::STATUS_APPROVED)->count(),
        ];

        $requests = $query->paginate(15)->withQueryString();

        $user = Auth::user();
        $user?->loadMissing('roleModel');
        $canRequest = $user && $user->hasPermission('petty-cash.request');
        $canApprove = $user && $user->hasPermission('petty-cash.approve');
        $canCustodian = $user && $user->hasPermission('petty-cash.custodian');
        $canReplenish = $user && $user->hasPermission('petty-cash.replenish');
        $canManageFunds = $user && $user->hasPermission('petty-cash.manage-funds');
        $hasPendingRequest = $user && PettyCashRequest::where('requested_by', $user->id)->where('status', PettyCashRequest::STATUS_PENDING)->exists();
        $requestNeedingProof = $user ? PettyCashRequest::where('requested_by', $user->id)
            ->where('status', PettyCashRequest::STATUS_DISBURSED)
            ->whereNull('proof_of_expenditure_path')
            ->latest()
            ->first() : null;
        $hasDisbursedWithoutProof = $requestNeedingProof !== null;

        $branchesForFilter = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Branch::where('is_active', true)->orderBy('name')->get();

        return view('petty-cash.index', compact(
            'funds',
            'requests',
            'stats',
            'canRequest',
            'canApprove',
            'canCustodian',
            'canReplenish',
            'canManageFunds',
            'hasPendingRequest',
            'hasDisbursedWithoutProof',
            'requestNeedingProof',
            'branchesForFilter'
        ));
    }

    public function createRequest()
    {
        if (!Auth::user()?->hasPermission('petty-cash.request')) {
            abort(403, 'You do not have permission to request petty cash.');
        }
        if (PettyCashRequest::where('requested_by', Auth::id())->where('status', PettyCashRequest::STATUS_PENDING)->exists()) {
            return redirect()->route('petty-cash.index')->with('error', 'You already have a pending petty cash request. Wait for it to be accepted or rejected before submitting another.');
        }
        if (PettyCashRequest::where('requested_by', Auth::id())->where('status', PettyCashRequest::STATUS_DISBURSED)->whereNull('proof_of_expenditure_path')->exists()) {
            return redirect()->route('petty-cash.index')->with('error', 'You must upload proof of expenditure for your last disbursed petty cash request before you can submit a new one.');
        }
        $allowedBranchIds = $this->allowedBranchIds();
        $funds = PettyCashFund::with('branch')
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->where('is_active', true)
            ->orderBy('branch_id')
            ->get();
        if ($funds->isEmpty()) {
            return redirect()->route('petty-cash.index')->with('error', 'No petty cash fund is available for your branch(es). Contact an administrator.');
        }
        $categories = PettyCashCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        return view('petty-cash.create-request', compact('funds', 'categories'));
    }

    public function storeRequest(Request $request)
    {
        if (!Auth::user()?->hasPermission('petty-cash.request')) {
            abort(403, 'You do not have permission to request petty cash.');
        }
        if (PettyCashRequest::where('requested_by', Auth::id())->where('status', PettyCashRequest::STATUS_PENDING)->exists()) {
            return redirect()->route('petty-cash.index')->with('error', 'You already have a pending petty cash request. Wait for it to be accepted or rejected before submitting another.');
        }
        if (PettyCashRequest::where('requested_by', Auth::id())->where('status', PettyCashRequest::STATUS_DISBURSED)->whereNull('proof_of_expenditure_path')->exists()) {
            return redirect()->route('petty-cash.index')->with('error', 'You must upload proof of expenditure for your last disbursed petty cash request before you can submit a new one.');
        }
        $allowedBranchIds = $this->allowedBranchIds();
        $validated = $request->validate([
            'petty_cash_fund_id' => 'required|exists:petty_cash_funds,id',
            'amount' => 'required|numeric|min:0.01',
            'petty_cash_category_id' => 'nullable|exists:petty_cash_categories,id',
            'reason' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:5120',
        ]);
        $fund = PettyCashFund::findOrFail($validated['petty_cash_fund_id']);
        if ($allowedBranchIds !== null && !in_array($fund->branch_id, $allowedBranchIds)) {
            abort(403, 'You cannot request from that fund.');
        }
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('petty-cash-requests', 'public');
        }
        $category = isset($validated['petty_cash_category_id']) ? PettyCashCategory::find($validated['petty_cash_category_id']) : null;
        $pettyCashRequest = PettyCashRequest::create([
            'petty_cash_fund_id' => $validated['petty_cash_fund_id'],
            'requested_by' => Auth::id(),
            'amount' => $validated['amount'],
            'petty_cash_category_id' => $validated['petty_cash_category_id'] ?? null,
            'category' => $category?->slug,
            'reason' => $validated['reason'] ?? null,
            'attachment_path' => $attachmentPath,
            'status' => PettyCashRequest::STATUS_PENDING,
        ]);
        ActivityLog::log(
            Auth::id(),
            'petty_cash_request_created',
            "Petty cash request of {$pettyCashRequest->amount} for " . $fund->branch->name,
            PettyCashRequest::class,
            $pettyCashRequest->id,
            ['amount' => $pettyCashRequest->amount, 'fund_id' => $fund->id]
        );

        $recipients = User::usersWithPettyCashApproveOrCustodianPermission($fund->branch_id)
            ->reject(fn ($u) => $u->id === Auth::id());
        if ($recipients->isNotEmpty()) {
            $branchName = $fund->branch->name;
            $amount = $pettyCashRequest->fund->currency . ' ' . number_format((float) $pettyCashRequest->amount, 2);
            $requesterName = Auth::user()->name ?? 'A user';
            $title = 'New petty cash request';
            $message = "{$requesterName} requested {$amount} for {$branchName}. Approval and disbursement required.";
            $url = route('petty-cash.show-request', $pettyCashRequest);

            // Internal (in-app) notifications – bell icon and /notifications page
            Notification::send($recipients, new AppNotification($title, $message, $url, 'petty_cash_request_created', [
                'petty_cash_request_id' => $pettyCashRequest->id,
                'amount' => (float) $pettyCashRequest->amount,
                'branch_id' => $fund->branch_id,
            ]));

            $emails = $recipients->pluck('email')->filter()->unique()->values();
            foreach ($emails as $email) {
                Mail::to($email)->queue(new StockActivityMail($title, $message, $url, 'View request'));
            }

            $smsMessage = "Petty cash: {$requesterName} requested {$amount} for {$branchName}. View: " . $url;
            foreach ($recipients as $user) {
                if (!empty($user->phone)) {
                    SmsHelper::send($user->phone, $smsMessage);
                }
            }
        }

        return redirect()->route('petty-cash.index')->with('success', 'Petty cash request submitted. It requires approval.');
    }

    public function showRequest(PettyCashRequest $pettyCashRequest)
    {
        $pettyCashRequest->load(['fund.branch', 'categoryRelation', 'requestedByUser', 'approvedByUser', 'rejectedByUser', 'disbursedByUser']);
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($pettyCashRequest->fund->branch_id, $allowedBranchIds)) {
            abort(403, 'You cannot view this request.');
        }
        $user = Auth::user();
        $user?->loadMissing('roleModel');
        $canApprove = $user && $user->hasPermission('petty-cash.approve');
        $canCustodian = $user && $user->hasPermission('petty-cash.custodian');
        $isCustodianForFund = $pettyCashRequest->fund->custodian_user_id === $user?->id;
        $canMarkAsPaid = $canCustodian && $pettyCashRequest->isApproved() && $user && $user->id !== $pettyCashRequest->requested_by;
        $isApplicant = $user && $user->id === $pettyCashRequest->requested_by;
        $canUploadProof = $isApplicant && $pettyCashRequest->isDisbursed() && ! $pettyCashRequest->hasProofOfExpenditure();
        return view('petty-cash.show-request', compact('pettyCashRequest', 'canApprove', 'canCustodian', 'isCustodianForFund', 'canMarkAsPaid', 'canUploadProof', 'isApplicant'));
    }

    public function downloadAttachment(PettyCashRequest $pettyCashRequest)
    {
        $pettyCashRequest->load('fund.branch');
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($pettyCashRequest->fund->branch_id, $allowedBranchIds)) {
            abort(403, 'You cannot access this request.');
        }
        if (!$pettyCashRequest->attachment_path) {
            abort(404, 'No attachment for this request.');
        }
        $path = storage_path('app/public/' . $pettyCashRequest->attachment_path);
        if (!is_file($path)) {
            abort(404, 'Attachment file not found.');
        }
        return response()->download($path, basename($pettyCashRequest->attachment_path));
    }

    public function uploadProofOfExpenditure(Request $request, PettyCashRequest $pettyCashRequest)
    {
        $pettyCashRequest->load('fund.branch');
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($pettyCashRequest->fund->branch_id, $allowedBranchIds)) {
            abort(403, 'You cannot access this request.');
        }
        if (Auth::id() !== $pettyCashRequest->requested_by) {
            abort(403, 'Only the applicant can upload proof of expenditure.');
        }
        if (!$pettyCashRequest->isDisbursed()) {
            return redirect()->route('petty-cash.show-request', $pettyCashRequest)->with('error', 'Proof can only be uploaded after the request is disbursed.');
        }
        if ($pettyCashRequest->hasProofOfExpenditure()) {
            return redirect()->route('petty-cash.show-request', $pettyCashRequest)->with('error', 'Proof of expenditure has already been uploaded.');
        }
        $validated = $request->validate([
            'proof_of_expenditure' => 'required|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:5120',
        ]);
        $path = $request->file('proof_of_expenditure')->store('petty-cash-proof-of-expenditure', 'public');
        $pettyCashRequest->update([
            'proof_of_expenditure_path' => $path,
            'proof_of_expenditure_uploaded_at' => now(),
        ]);
        ActivityLog::log(
            Auth::id(),
            'petty_cash_proof_uploaded',
            "Uploaded proof of expenditure for petty cash request of {$pettyCashRequest->amount}",
            PettyCashRequest::class,
            $pettyCashRequest->id,
            ['amount' => $pettyCashRequest->amount]
        );
        return redirect()->route('petty-cash.show-request', $pettyCashRequest)->with('success', 'Proof of expenditure uploaded. You can now submit new petty cash requests.');
    }

    public function downloadProofOfExpenditure(PettyCashRequest $pettyCashRequest)
    {
        $pettyCashRequest->load('fund.branch');
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($pettyCashRequest->fund->branch_id, $allowedBranchIds)) {
            abort(403, 'You cannot access this request.');
        }
        if (!$pettyCashRequest->proof_of_expenditure_path) {
            abort(404, 'No proof of expenditure file for this request.');
        }
        $path = storage_path('app/public/' . $pettyCashRequest->proof_of_expenditure_path);
        if (!is_file($path)) {
            abort(404, 'Proof of expenditure file not found.');
        }
        return response()->download($path, 'proof-of-expenditure-' . $pettyCashRequest->id . '-' . basename($pettyCashRequest->proof_of_expenditure_path));
    }

    public function approve(PettyCashRequest $pettyCashRequest)
    {
        if (!Auth::user()?->hasPermission('petty-cash.approve')) {
            abort(403, 'You do not have permission to approve petty cash requests.');
        }
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($pettyCashRequest->fund->branch_id, $allowedBranchIds)) {
            abort(403, 'You cannot approve this request.');
        }
        if (!$pettyCashRequest->isPending()) {
            return redirect()->route('petty-cash.show-request', $pettyCashRequest)->with('error', 'Only pending requests can be approved.');
        }
        DB::transaction(function () use ($pettyCashRequest) {
            $pettyCashRequest->update([
                'status' => PettyCashRequest::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);
            ActivityLog::log(
                Auth::id(),
                'petty_cash_request_approved',
                "Approved petty cash request of {$pettyCashRequest->amount}",
                PettyCashRequest::class,
                $pettyCashRequest->id,
                ['amount' => $pettyCashRequest->amount]
            );
        });
        return redirect()->route('petty-cash.show-request', $pettyCashRequest)->with('success', 'Request approved. Custodian can now disburse.');
    }

    public function reject(Request $request, PettyCashRequest $pettyCashRequest)
    {
        if (!Auth::user()?->hasPermission('petty-cash.approve')) {
            abort(403, 'You do not have permission to reject petty cash requests.');
        }
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($pettyCashRequest->fund->branch_id, $allowedBranchIds)) {
            abort(403, 'You cannot reject this request.');
        }
        if (!$pettyCashRequest->isPending()) {
            return redirect()->route('petty-cash.show-request', $pettyCashRequest)->with('error', 'Only pending requests can be rejected.');
        }
        $validated = $request->validate(['rejection_reason' => 'nullable|string|max:1000']);
        DB::transaction(function () use ($pettyCashRequest, $validated) {
            $pettyCashRequest->update([
                'status' => PettyCashRequest::STATUS_REJECTED,
                'rejected_at' => now(),
                'rejected_by' => Auth::id(),
                'rejection_reason' => $validated['rejection_reason'] ?? null,
            ]);
            ActivityLog::log(
                Auth::id(),
                'petty_cash_request_rejected',
                "Rejected petty cash request of {$pettyCashRequest->amount}",
                PettyCashRequest::class,
                $pettyCashRequest->id,
                ['amount' => $pettyCashRequest->amount]
            );
        });
        return redirect()->route('petty-cash.show-request', $pettyCashRequest)->with('success', 'Request rejected.');
    }

    public function disburse(Request $request, PettyCashRequest $pettyCashRequest)
    {
        $user = Auth::user();
        if (!$user->hasPermission('petty-cash.custodian')) {
            abort(403, 'You do not have permission to disburse petty cash.');
        }
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($pettyCashRequest->fund->branch_id, $allowedBranchIds)) {
            abort(403, 'You cannot disburse this request.');
        }
        if ($pettyCashRequest->requested_by === $user->id) {
            abort(403, 'You cannot mark your own request as paid.');
        }
        if (!$pettyCashRequest->isApproved()) {
            return redirect()->route('petty-cash.show-request', $pettyCashRequest)->with('error', 'Only approved requests can be disbursed.');
        }
        $fund = $pettyCashRequest->fund;
        if ($fund->current_balance < $pettyCashRequest->amount) {
            return redirect()->route('petty-cash.show-request', $pettyCashRequest)->with('error', 'Insufficient fund balance. Request replenishment first.');
        }
        $request->validate(['receipt_attachment_path' => 'nullable|string|max:500']);
        DB::transaction(function () use ($pettyCashRequest, $request) {
            $pettyCashRequest->update([
                'status' => PettyCashRequest::STATUS_DISBURSED,
                'disbursed_at' => now(),
                'disbursed_by' => Auth::id(),
                'receipt_attachment_path' => $request->input('receipt_attachment_path'),
            ]);
            $pettyCashRequest->fund->decrement('current_balance', $pettyCashRequest->amount);
            ActivityLog::log(
                Auth::id(),
                'petty_cash_disbursed',
                "Disbursed petty cash request of {$pettyCashRequest->amount}",
                PettyCashRequest::class,
                $pettyCashRequest->id,
                ['amount' => $pettyCashRequest->amount]
            );
        });

        $pettyCashRequest->load(['fund.branch', 'requestedByUser']);
        $applicant = $pettyCashRequest->requestedByUser;
        if ($applicant) {
            $amount = $pettyCashRequest->fund->currency . ' ' . number_format((float) $pettyCashRequest->amount, 2);
            $branchName = $pettyCashRequest->fund->branch->name;
            $title = 'Petty cash disbursed – upload proof of expenditure';
            $message = "Your petty cash request of {$amount} for {$branchName} has been disbursed. Please upload proof of expenditure so you can submit new requests.";
            $url = route('petty-cash.show-request', $pettyCashRequest);

            $applicant->notify(new AppNotification($title, $message, $url, 'petty_cash_disbursed_upload_proof', [
                'petty_cash_request_id' => $pettyCashRequest->id,
                'amount' => (float) $pettyCashRequest->amount,
            ]));

            if ($applicant->email) {
                Mail::to($applicant->email)->queue(new StockActivityMail($title, $message, $url, 'Upload proof'));
            }

            if (! empty($applicant->phone)) {
                $smsMessage = "Petty cash disbursed: {$amount} for {$branchName}. Upload proof of expenditure: " . $url;
                SmsHelper::send($applicant->phone, $smsMessage);
            }
        }

        return redirect()->route('petty-cash.show-request', $pettyCashRequest)->with('success', 'Disbursement recorded.');
    }

    public function replenishForm(PettyCashFund $pettyCashFund)
    {
        if (!Auth::user()?->hasPermission('petty-cash.replenish')) {
            abort(403, 'You do not have permission to replenish petty cash.');
        }
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($pettyCashFund->branch_id, $allowedBranchIds)) {
            abort(403, 'You cannot replenish this fund.');
        }
        return view('petty-cash.replenish', compact('pettyCashFund'));
    }

    public function replenishStore(Request $request, PettyCashFund $pettyCashFund)
    {
        if (!Auth::user()?->hasPermission('petty-cash.replenish')) {
            abort(403, 'You do not have permission to replenish petty cash.');
        }
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($pettyCashFund->branch_id, $allowedBranchIds)) {
            abort(403, 'You cannot replenish this fund.');
        }
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
            'reference' => 'nullable|string|max:255',
        ]);
        DB::transaction(function () use ($pettyCashFund, $validated) {
            PettyCashReplenishment::create([
                'petty_cash_fund_id' => $pettyCashFund->id,
                'amount' => $validated['amount'],
                'replenished_by' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
                'reference' => $validated['reference'] ?? null,
            ]);
            $pettyCashFund->increment('current_balance', $validated['amount']);
            ActivityLog::log(
                Auth::id(),
                'petty_cash_replenished',
                "Replenished petty cash fund " . $pettyCashFund->branch->name . " by {$validated['amount']}",
                PettyCashFund::class,
                $pettyCashFund->id,
                ['amount' => $validated['amount']]
            );
        });
        return redirect()->route('petty-cash.funds.show', $pettyCashFund)->with('success', 'Fund replenished.');
    }

    public function reconciliation(PettyCashFund $pettyCashFund)
    {
        if (!Auth::user()?->hasPermission('petty-cash.view')) {
            abort(403);
        }
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($pettyCashFund->branch_id, $allowedBranchIds)) {
            abort(403);
        }
        $pettyCashFund->load(['branch', 'custodian']);
        $disbursedRequests = $pettyCashFund->requests()->where('status', PettyCashRequest::STATUS_DISBURSED)->orderByDesc('disbursed_at')->limit(50)->get();
        $replenishments = $pettyCashFund->replenishments()->with('replenishedByUser')->latest()->limit(20)->get();
        return view('petty-cash.reconciliation', compact('pettyCashFund', 'disbursedRequests', 'replenishments'));
    }

    public function fundsIndex()
    {
        if (!Auth::user()?->hasPermission('petty-cash.manage-funds')) {
            abort(403);
        }
        $allowedBranchIds = $this->allowedBranchIds();
        $funds = PettyCashFund::with(['branch', 'custodian'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->orderBy('branch_id')
            ->get();
        $branches = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Branch::where('is_active', true)->orderBy('name')->get();
        $users = \App\Models\User::whereNull('suspended_at')->orderBy('name')->get(['id', 'name']);
        $canReplenish = Auth::user()?->hasPermission('petty-cash.replenish');
        return view('petty-cash.funds-index', compact('funds', 'branches', 'users', 'canReplenish'));
    }

    public function fundsShow(PettyCashFund $pettyCashFund)
    {
        if (!Auth::user()?->hasPermission('petty-cash.view')) {
            abort(403);
        }
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($pettyCashFund->branch_id, $allowedBranchIds)) {
            abort(403);
        }
        $pettyCashFund->load(['branch', 'custodian']);
        $disbursedRequests = $pettyCashFund->requests()
            ->where('status', PettyCashRequest::STATUS_DISBURSED)
            ->with(['categoryRelation', 'requestedByUser', 'disbursedByUser'])
            ->orderByDesc('disbursed_at')
            ->limit(30)
            ->get();
        $replenishments = $pettyCashFund->replenishments()
            ->with('replenishedByUser')
            ->latest()
            ->limit(30)
            ->get();
        $canReplenish = Auth::user()?->hasPermission('petty-cash.replenish');
        $canManageFunds = Auth::user()?->hasPermission('petty-cash.manage-funds');
        return view('petty-cash.funds-show', compact('pettyCashFund', 'disbursedRequests', 'replenishments', 'canReplenish', 'canManageFunds'));
    }

    public function fundsCreate()
    {
        if (!Auth::user()?->hasPermission('petty-cash.manage-funds')) {
            abort(403);
        }
        $allowedBranchIds = $this->allowedBranchIds();
        $branches = $allowedBranchIds !== null
            ? Branch::whereIn('id', $allowedBranchIds)->where('is_active', true)->orderBy('name')->get()
            : Branch::where('is_active', true)->orderBy('name')->get();
        $branchIdsWithFund = PettyCashFund::whereIn('branch_id', $branches->pluck('id'))->pluck('branch_id')->all();
        $branchesWithoutFund = $branches->filter(fn($b) => !in_array($b->id, $branchIdsWithFund));
        $users = \App\Models\User::whereNull('suspended_at')->orderBy('name')->get(['id', 'name']);
        return view('petty-cash.funds-create', compact('branchesWithoutFund', 'users'));
    }

    public function fundsEdit(PettyCashFund $pettyCashFund)
    {
        if (!Auth::user()?->hasPermission('petty-cash.manage-funds')) {
            abort(403);
        }
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($pettyCashFund->branch_id, $allowedBranchIds)) {
            abort(403);
        }
        $pettyCashFund->load('branch');
        $users = \App\Models\User::whereNull('suspended_at')->orderBy('name')->get(['id', 'name']);
        return view('petty-cash.funds-edit', compact('pettyCashFund', 'users'));
    }

    public function fundsStore(Request $request)
    {
        if (!Auth::user()?->hasPermission('petty-cash.manage-funds')) {
            abort(403);
        }
        $allowedBranchIds = $this->allowedBranchIds();
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'fund_limit' => 'required|numeric|min:0',
            'custodian_user_id' => 'nullable|exists:users,id',
            'currency' => 'nullable|string|max:10',
        ]);
        if ($allowedBranchIds !== null && !in_array($validated['branch_id'], $allowedBranchIds)) {
            abort(403);
        }
        if (PettyCashFund::where('branch_id', $validated['branch_id'])->exists()) {
            return back()->withErrors(['branch_id' => 'A fund already exists for this branch.'])->withInput();
        }
        PettyCashFund::create([
            'branch_id' => $validated['branch_id'],
            'fund_limit' => $validated['fund_limit'],
            'current_balance' => 0,
            'custodian_user_id' => $validated['custodian_user_id'] ?? null,
            'currency' => $validated['currency'] ?? config('app.currency_symbol'),
        ]);
        return redirect()->route('petty-cash.funds.index')->with('success', 'Fund created.');
    }

    public function fundsUpdate(Request $request, PettyCashFund $pettyCashFund)
    {
        if (!Auth::user()?->hasPermission('petty-cash.manage-funds')) {
            abort(403);
        }
        $allowedBranchIds = $this->allowedBranchIds();
        if ($allowedBranchIds !== null && !in_array($pettyCashFund->branch_id, $allowedBranchIds)) {
            abort(403);
        }
        $validated = $request->validate([
            'fund_limit' => 'required|numeric|min:0',
            'custodian_user_id' => 'nullable|exists:users,id',
            'currency' => 'nullable|string|max:10',
            'is_active' => 'nullable|boolean',
        ]);
        $pettyCashFund->update([
            'fund_limit' => $validated['fund_limit'],
            'custodian_user_id' => $validated['custodian_user_id'] ?? null,
            'currency' => $validated['currency'] ?? $pettyCashFund->currency,
            'is_active' => $request->boolean('is_active', true),
        ]);
        return redirect()->route('petty-cash.funds.show', $pettyCashFund)->with('success', 'Fund updated.');
    }

    public function categoriesIndex()
    {
        if (!Auth::user()?->hasPermission('petty-cash.manage-funds')) {
            abort(403);
        }
        $categories = PettyCashCategory::withCount('requests')
            ->withSum('requests', 'amount')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        return view('petty-cash.categories-index', compact('categories'));
    }

    public function categoryShow(PettyCashCategory $pettyCashCategory)
    {
        if (!Auth::user()?->hasPermission('petty-cash.manage-funds')) {
            abort(403);
        }
        $allowedBranchIds = $this->allowedBranchIds();
        $requests = $pettyCashCategory->requests()
            ->with(['fund.branch', 'requestedByUser', 'approvedByUser'])
            ->when($allowedBranchIds !== null, fn ($q) => $q->whereHas('fund', fn ($f) => $f->whereIn('branch_id', $allowedBranchIds)))
            ->latest()
            ->paginate(15)
            ->withQueryString();
        return view('petty-cash.categories-show', compact('pettyCashCategory', 'requests'));
    }

    public function categoryStore(Request $request)
    {
        if (!Auth::user()?->hasPermission('petty-cash.manage-funds')) {
            abort(403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'description' => 'nullable|string|max:500',
        ]);
        $slug = PettyCashCategory::slugFromName($validated['name']);
        if (PettyCashCategory::where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'A category with this name already exists.'])->withInput();
        }
        $maxOrder = (int) PettyCashCategory::max('sort_order');
        PettyCashCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'sort_order' => $maxOrder + 1,
            'is_active' => true,
        ]);
        return redirect()->route('petty-cash.categories.index')->with('success', 'Category created.');
    }

    public function categoryEdit(PettyCashCategory $pettyCashCategory)
    {
        if (!Auth::user()?->hasPermission('petty-cash.manage-funds')) {
            abort(403);
        }
        return view('petty-cash.categories-edit', compact('pettyCashCategory'));
    }

    public function categoryUpdate(Request $request, PettyCashCategory $pettyCashCategory)
    {
        if (!Auth::user()?->hasPermission('petty-cash.manage-funds')) {
            abort(403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $newSlug = PettyCashCategory::slugFromName($validated['name']);
        if ($newSlug !== $pettyCashCategory->slug && PettyCashCategory::where('slug', $newSlug)->exists()) {
            return back()->withErrors(['name' => 'A category with this name already exists.'])->withInput();
        }
        $pettyCashCategory->update([
            'name' => $validated['name'],
            'slug' => $newSlug,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'],
        ]);
        return redirect()->route('petty-cash.categories.index')->with('success', 'Category updated.');
    }

    public function export(Request $request)
    {
        if (!Auth::user()?->hasPermission('petty-cash.view')) {
            abort(403);
        }
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\PettyCashRequestsExport($request),
            'petty-cash-requests-' . now()->format('Y-m-d-His') . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }
}
