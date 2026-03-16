<?php

namespace App\Exports;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\CustomerDisbursement;
use App\Models\Bill;
use App\Models\PettyCashRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(protected Request $request) {}

    protected function includeType(string $type, string $filter): bool
    {
        if ($filter === '' || $filter === 'all') {
            return true;
        }
        return $filter === $type;
    }

    public function collection()
    {
        $user = $this->request->user();
        if (!$user) {
            return collect();
        }
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        $allowedBranchIds = !$isFieldAgent && $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
        $typeFilter = $this->request->get('type', '');
        $dateFrom = $this->request->filled('date_from') ? Carbon::parse($this->request->get('date_from'))->startOfDay() : null;
        $dateTo = $this->request->filled('date_to') ? Carbon::parse($this->request->get('date_to'))->endOfDay() : null;
        $search = $this->request->get('search', '');
        $customerId = $this->request->get('customer_id', '');
        $statusFilter = $this->request->get('status', '');

        $rows = collect();

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
            $sales = $salesQuery->latest()->limit(1000)->get();
            foreach ($sales as $sale) {
                $rows->push((object)[
                    'type' => 'Sale',
                    'date' => $sale->created_at,
                    'reference' => $sale->sale_number,
                    'description' => trim(($sale->customer?->name ?? 'N/A') . ' • ' . ($sale->branch?->name ?? '—')),
                    'amount' => (float) $sale->total,
                ]);
            }
        }

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
            $licenseSales = $licenseQuery->latest()->limit(1000)->get();
            foreach ($licenseSales as $sale) {
                $rows->push((object)[
                    'type' => 'License',
                    'date' => $sale->created_at,
                    'reference' => 'License – ' . $sale->sale_number,
                    'description' => $sale->customer?->name ?? 'N/A',
                    'amount' => (float) $sale->total_license_cost,
                ]);
            }
        }

        if ($this->includeType('disbursement', $typeFilter) && $user->hasPermission('customer-disbursements.view')) {
            $disbQuery = CustomerDisbursement::with(['sale.customer', 'customer'])
                ->where('status', CustomerDisbursement::STATUS_APPROVED)->whereHas('sale');
            if ($allowedBranchIds !== null) {
                $disbQuery->whereHas('sale', fn($q) => $q->whereIn('branch_id', $allowedBranchIds));
            }
            if ($dateFrom) {
                $disbQuery->whereRaw('COALESCE(approved_at, created_at) >= ?', [$dateFrom]);
            }
            if ($dateTo) {
                $disbQuery->whereRaw('COALESCE(approved_at, created_at) <= ?', [$dateTo]);
            }
            $disbursements = $disbQuery->orderByRaw('COALESCE(approved_at, created_at) DESC')->limit(1000)->get();
            foreach ($disbursements as $d) {
                $sale = $d->sale;
                $rows->push((object)[
                    'type' => 'Disbursement',
                    'date' => $d->approved_at ?? $d->created_at,
                    'reference' => 'Disbursement – ' . ($sale?->sale_number ?? 'Sale'),
                    'description' => $d->customer?->name ?? $sale?->customer?->name ?? '—',
                    'amount' => (float) $d->amount,
                ]);
            }
        }

        if ($this->includeType('bill', $typeFilter) && $user->hasPermission('bills.view')) {
            $billsQuery = Bill::with(['vendor'])->paid();
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
            $bills = $billsQuery->latest('paid_at')->limit(1000)->get();
            foreach ($bills as $bill) {
                $rows->push((object)[
                    'type' => 'Bill',
                    'date' => $bill->paid_at,
                    'reference' => $bill->invoice_number ?? 'Bill #' . $bill->id,
                    'description' => $bill->vendor?->name ?? '—',
                    'amount' => (float) $bill->amount,
                ]);
            }
        }

        if ($this->includeType('petty_cash', $typeFilter) && $user->hasPermission('petty-cash.view')) {
            $pcQuery = PettyCashRequest::with(['fund'])->where('status', PettyCashRequest::STATUS_DISBURSED);
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
            $pettyCash = $pcQuery->latest('disbursed_at')->limit(1000)->get();
            foreach ($pettyCash as $pc) {
                $rows->push((object)[
                    'type' => 'Petty cash',
                    'date' => $pc->disbursed_at,
                    'reference' => $pc->reason ? Str::limit($pc->reason, 60) : 'Petty cash #' . $pc->id,
                    'description' => $pc->category_name ?? '—',
                    'amount' => (float) $pc->amount,
                ]);
            }
        }

        return $rows->sortByDesc(fn($r) => $r->date?->timestamp ?? 0)->values();
    }

    public function headings(): array
    {
        return ['Type', 'Date', 'Reference', 'Description', 'Amount (TSh)'];
    }

    public function map($row): array
    {
        return [
            $row->type ?? '—',
            $row->date?->format('Y-m-d H:i') ?? '—',
            $row->reference ?? '—',
            $row->description ?? '—',
            number_format((float) ($row->amount ?? 0), 2),
        ];
    }
}
