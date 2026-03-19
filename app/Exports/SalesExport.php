<?php

namespace App\Exports;

use App\Models\Sale;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class SalesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected Request $request
    ) {}

    public function query()
    {
        $user = Auth::user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        $allowedBranchIds = $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
        $branchFilter = $this->request->get('branch');
        if ($branchFilter === '') {
            $branchFilter = null;
        }
        if ($allowedBranchIds !== null && $branchFilter !== null && !in_array($branchFilter, $allowedBranchIds, true)) {
            $branchFilter = null;
        }

        $query = Sale::with([
            'customer', 'branch', 'soldBy',
            'items.fieldAgent', 'items.device', 'items.product.brand', 'items.product.regionPrices',
        ]);

        // Restrict to branches this user can view (their branch + descendants)
        $query->when($allowedBranchIds !== null, fn ($q) => $q->whereIn('branch_id', $allowedBranchIds));
        if ($isFieldAgent) {
            $query->whereHas('items', fn ($q) => $q->where('field_agent_id', $user->id));
        }
        if ($branchFilter !== null) {
            $query->where('branch_id', $branchFilter);
        }

        if ($this->request->filled('search')) {
            $term = $this->request->get('search');
            $query->where('sale_number', 'like', "%{$term}%");
        }
        if ($this->request->filled('status')) {
            $query->where('status', $this->request->get('status'));
        }
        if ($this->request->filled('customer_id')) {
            $query->where('customer_id', $this->request->get('customer_id'));
        }
        if ($this->request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $this->request->get('date_from'));
        }
        if ($this->request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $this->request->get('date_to'));
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'Sale #',
            'Customer',
            'Branch',
            'Sold By',
            'Field Agent',
            'Product',
            'Brand',
            'Total (' . config('app.currency_symbol') . ')',
            'Buying price (' . config('app.currency_symbol') . ')',
            'License cost (' . config('app.currency_symbol') . ')',
            'Profit (' . config('app.currency_symbol') . ')',
            'Commission (' . config('app.currency_symbol') . ')',
            'Status',
            'Date',
        ];
    }

    public function map($sale): array
    {
        $products = $sale->items->pluck('product.name')->filter()->unique()->implode(', ');
        $brands = $sale->items->pluck('product.brand.name')->filter()->unique()->implode(', ');
        $fieldAgent = $sale->items->first()?->fieldAgent?->name ?? '-';

        $buyingPrice = (float) $sale->total_buying_price;
        $licenseCost = (float) ($sale->total_license_cost ?? 0);
        $commission = (float) $sale->items->sum('commission_amount');
        $profit = (float) $sale->gross_profit;

        return [
            $sale->sale_number ?? '',
            $sale->customer?->name ?? 'Walk-in',
            $sale->branch?->name ?? '',
            $sale->soldBy?->name ?? '',
            $fieldAgent,
            $products ?: '-',
            $brands ?: '-',
            number_format((float) $sale->total, 2),
            number_format($buyingPrice, 2),
            number_format($licenseCost, 2),
            number_format($profit, 2),
            number_format($commission, 2),
            ucfirst($sale->status ?? ''),
            $sale->created_at?->format('M d, Y') ?? '',
        ];
    }
}
