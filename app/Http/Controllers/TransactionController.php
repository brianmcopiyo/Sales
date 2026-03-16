<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\CustomerDisbursement;
use App\Models\Bill;
use App\Models\PettyCashRequest;
use App\Exports\TransactionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public const TYPES = ['sale', 'disbursement', 'license', 'bill', 'petty_cash'];

    public function index(Request $request)
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        $allowedBranchIds = !$isFieldAgent && $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;

        $typeFilter = $request->get('type', '');
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->get('date_from'))->startOfDay() : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->get('date_to'))->endOfDay() : null;
        $search = $request->get('search', '');
        $customerId = $request->get('customer_id', '');
        $statusFilter = $request->get('status', '');

        $rows = collect();

        // Sales
        if ($this->includeType('sale', $typeFilter) && $user->hasPermission('sales.view')) {
            $salesQuery = Sale::with(['customer', 'branch', 'soldBy']);
            if ($isFieldAgent) {
                $salesQuery->whereHas('items', fn($q) => $q->where('field_agent_id', $user->id));
            } else {
                $salesQuery->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds));
            }
            if ($search) {
                $salesQuery->where('sale_number', 'like', "%{$search}%");
            }
            if ($statusFilter) {
                $salesQuery->where('status', $statusFilter);
            }
            if ($customerId) {
                $salesQuery->where('customer_id', $customerId);
            }
            if ($dateFrom) {
                $salesQuery->where('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $salesQuery->where('created_at', '<=', $dateTo);
            }
            $sales = $salesQuery->latest()->limit(400)->get();
            foreach ($sales as $sale) {
                $rows->push((object)[
                    'type' => 'sale',
                    'date' => $sale->created_at,
                    'reference' => $sale->sale_number,
                    'description' => trim(($sale->customer?->name ?? 'N/A') . ' • ' . ($sale->branch?->name ?? '—')),
                    'amount' => (float) $sale->total,
                    'url' => route('sales.show', $sale),
                    'raw' => $sale,
                ]);
            }
        }

        // License (cost) – one row per sale with license cost
        if ($this->includeType('license', $typeFilter) && $user->hasPermission('sales.view')) {
            $licenseQuery = Sale::with(['customer', 'branch'])->where('status', 'completed')
                ->whereNotNull('total_license_cost')->where('total_license_cost', '>', 0);
            if ($isFieldAgent) {
                $licenseQuery->whereHas('items', fn($q) => $q->where('field_agent_id', $user->id));
            } else {
                $licenseQuery->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds));
            }
            if ($dateFrom) {
                $licenseQuery->where('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $licenseQuery->where('created_at', '<=', $dateTo);
            }
            $licenseSales = $licenseQuery->latest()->limit(400)->get();
            foreach ($licenseSales as $sale) {
                $rows->push((object)[
                    'type' => 'license',
                    'date' => $sale->created_at,
                    'reference' => 'License – ' . $sale->sale_number,
                    'description' => $sale->customer?->name ?? 'N/A',
                    'amount' => (float) $sale->total_license_cost,
                    'url' => route('sales.show', $sale),
                    'raw' => $sale,
                ]);
            }
        }

        // Customer disbursements
        if ($this->includeType('disbursement', $typeFilter) && $user->hasPermission('customer-disbursements.view')) {
            $disbQuery = CustomerDisbursement::with(['sale.customer', 'sale.branch', 'customer'])
                ->where('status', CustomerDisbursement::STATUS_APPROVED);
            $disbQuery->whereHas('sale');
            if ($allowedBranchIds !== null) {
                $disbQuery->whereHas('sale', fn($q) => $q->whereIn('branch_id', $allowedBranchIds));
            }
            if ($dateFrom) {
                $disbQuery->whereRaw('COALESCE(approved_at, created_at) >= ?', [$dateFrom]);
            }
            if ($dateTo) {
                $disbQuery->whereRaw('COALESCE(approved_at, created_at) <= ?', [$dateTo]);
            }
            $disbursements = $disbQuery->orderByRaw('COALESCE(approved_at, created_at) DESC')->limit(400)->get();
            foreach ($disbursements as $d) {
                $sale = $d->sale;
                $rows->push((object)[
                    'type' => 'disbursement',
                    'date' => $d->approved_at ?? $d->created_at,
                    'reference' => 'Disbursement – ' . ($sale?->sale_number ?? 'Sale'),
                    'description' => $d->customer?->name ?? $sale?->customer?->name ?? '—',
                    'amount' => (float) $d->amount,
                    'url' => $sale ? route('sales.show', $sale) : null,
                    'raw' => $d,
                ]);
            }
        }

        // Bills (paid)
        if ($this->includeType('bill', $typeFilter) && $user->hasPermission('bills.view')) {
            $billsQuery = Bill::with(['vendor', 'branch'])->paid();
            if ($allowedBranchIds !== null) {
                $billsQuery->where(function ($q) use ($allowedBranchIds) {
                    $q->whereIn('branch_id', $allowedBranchIds)->orWhereNull('branch_id');
                });
            }
            if ($dateFrom) {
                $billsQuery->where('paid_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $billsQuery->where('paid_at', '<=', $dateTo);
            }
            if ($search) {
                $billsQuery->where('invoice_number', 'like', "%{$search}%");
            }
            $bills = $billsQuery->latest('paid_at')->limit(400)->get();
            foreach ($bills as $bill) {
                $rows->push((object)[
                    'type' => 'bill',
                    'date' => $bill->paid_at,
                    'reference' => $bill->invoice_number ?? 'Bill #' . $bill->id,
                    'description' => $bill->vendor?->name ?? '—',
                    'amount' => (float) $bill->amount,
                    'url' => route('bills.show', $bill),
                    'raw' => $bill,
                ]);
            }
        }

        // Petty cash (disbursed)
        if ($this->includeType('petty_cash', $typeFilter) && $user->hasPermission('petty-cash.view')) {
            $pcQuery = PettyCashRequest::with(['fund', 'requestedByUser'])
                ->where('status', PettyCashRequest::STATUS_DISBURSED);
            if ($allowedBranchIds !== null) {
                $pcQuery->whereHas('fund', fn($f) => $f->whereIn('branch_id', $allowedBranchIds));
            }
            if ($dateFrom) {
                $pcQuery->where('disbursed_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $pcQuery->where('disbursed_at', '<=', $dateTo);
            }
            if ($search) {
                $pcQuery->where('reason', 'like', "%{$search}%");
            }
            $pettyCash = $pcQuery->latest('disbursed_at')->limit(400)->get();
            foreach ($pettyCash as $pc) {
                $rows->push((object)[
                    'type' => 'petty_cash',
                    'date' => $pc->disbursed_at,
                    'reference' => $pc->reason ? Str::limit($pc->reason, 40) : 'Petty cash #' . $pc->id,
                    'description' => $pc->category_name ?? '—',
                    'amount' => (float) $pc->amount,
                    'url' => route('petty-cash.show-request', $pc),
                    'raw' => $pc,
                ]);
            }
        }

        $rows = $rows->sortByDesc(fn($r) => $r->date?->timestamp ?? 0)->values();

        $perPage = 15;
        $page = (int) $request->get('page', 1);
        $total = $rows->count();
        $transactions = new LengthAwarePaginator(
            $rows->slice(($page - 1) * $perPage, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Stats (sales-based + cost to sell including bills & petty cash)
        $baseQuery = $isFieldAgent
            ? Sale::whereHas('items', fn($q) => $q->where('field_agent_id', $user->id))
            : Sale::query()->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds));
        $completedQuery = (clone $baseQuery)->where('status', 'completed');
        $completedIds = (clone $completedQuery)->pluck('id')->all();
        $licenseCost = (clone $completedQuery)->sum('total_license_cost');
        $disbursementCost = CustomerDisbursement::whereIn('sale_id', $completedIds)->sum('amount');
        $totalBuyingPrice = Sale::totalBuyingPriceForSaleIds($completedIds);
        $totalBillsPaid = $user->hasPermission('bills.view')
            ? Bill::query()->paid()
                ->when($allowedBranchIds !== null, fn($q) => $q->where(function ($q) use ($allowedBranchIds) {
                    $q->whereIn('branch_id', $allowedBranchIds)->orWhereNull('branch_id');
                }))->sum('amount')
            : 0;
        $totalPettyCashDisbursed = $user->hasPermission('petty-cash.view')
            ? PettyCashRequest::query()->where('status', PettyCashRequest::STATUS_DISBURSED)
                ->when($allowedBranchIds !== null, fn($q) => $q->whereHas('fund', fn($f) => $f->whereIn('branch_id', $allowedBranchIds)))
                ->sum('amount')
            : 0;
        $stats = [
            'total' => $total,
            'today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'this_month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'total_revenue' => (clone $completedQuery)->sum('total'),
            'total_cost_to_sell' => $totalBuyingPrice + $licenseCost + $disbursementCost + $totalBillsPaid + $totalPettyCashDisbursed,
        ];
        $stats['total_profit'] = $stats['total_revenue'] - $stats['total_cost_to_sell'];

        if ($isFieldAgent) {
            $customers = Customer::whereHas('sales.items', fn($q) => $q->where('field_agent_id', $user->id))->orderBy('name')->get(['id', 'name']);
        } else {
            $customers = $allowedBranchIds !== null
                ? Customer::visibleToBranches($allowedBranchIds)->orderBy('name')->get(['id', 'name'])
                : Customer::orderBy('name')->get(['id', 'name']);
        }

        return view('transactions.index', compact('transactions', 'stats', 'customers', 'typeFilter'));
    }

    protected function includeType(string $type, string $filter): bool
    {
        if ($filter === '' || $filter === 'all') {
            return true;
        }
        return $filter === $type;
    }

    public function export(Request $request)
    {
        $filename = 'transactions-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new TransactionsExport($request), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }
}
