<?php

namespace App\Exports\Portal;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PortalSalesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected string $customerId,
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null,
        protected ?string $status = null,
        protected ?string $search = null,
    ) {}

    public function query()
    {
        $query = Sale::with(['items.product', 'outlet'])
            ->where('customer_id', $this->customerId)
            ->secondarySales()
            ->orderByDesc('created_at');

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
        if ($this->status) {
            $query->where('status', $this->status);
        }
        if ($this->search) {
            $query->where('sale_number', 'like', "%{$this->search}%");
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Order #',
            'Products',
            'Outlet',
            'Subtotal',
            'Discount',
            'Total',
            'Status',
            'Date',
        ];
    }

    public function map($sale): array
    {
        $products = $sale->items->map(fn ($i) => $i->product?->name ?? 'N/A')->join(', ');

        return [
            $sale->sale_number ?? $sale->id,
            $products,
            $sale->outlet?->name ?? '—',
            number_format($sale->subtotal, 2),
            number_format($sale->discount, 2),
            number_format($sale->total, 2),
            ucfirst($sale->status),
            $sale->created_at->format('Y-m-d'),
        ];
    }
}
