<?php

namespace App\Exports;

use App\Models\StockTake;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class StockTakesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(protected Request $request) {}

    public function query()
    {
        $user = auth()->user();
        $query = StockTake::with(['branch', 'creator', 'items'])
            ->when($user->branch_id && ! $user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->latest();

        if ($this->request->filled('region_id')) {
            $query->whereHas('branch', fn($q) => $q->where('region_id', $this->request->region_id));
        }
        if ($this->request->filled('branch_id') && $user->isAdmin()) {
            $query->where('branch_id', $this->request->branch_id);
        }
        if ($this->request->filled('status')) {
            $query->where('status', $this->request->status);
        }
        if ($this->request->filled('date_from')) {
            $query->whereDate('stock_take_date', '>=', $this->request->date_from);
        }
        if ($this->request->filled('date_to')) {
            $query->whereDate('stock_take_date', '<=', $this->request->date_to);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Stock Take #', 'Branch', 'Date', 'Items Count', 'Counted', 'Over', 'Short', 'Status', 'Created By'];
    }

    public function map($stockTake): array
    {
        $items = $stockTake->items;
        $counted = $items->whereNotNull('physical_quantity')->count();
        $variances = $items->filter(fn($i) => $i->variance !== 0);
        $over = $variances->filter(fn($i) => $i->variance > 0)->count();
        $short = $variances->filter(fn($i) => $i->variance < 0)->count();

        return [
            $stockTake->stock_take_number ?? '',
            $stockTake->branch?->name ?? '',
            $stockTake->stock_take_date?->format('M d, Y') ?? '',
            $items->count(),
            $counted,
            $over,
            $short,
            ucfirst(str_replace('_', ' ', $stockTake->status ?? '')),
            $stockTake->creator?->name ?? '',
        ];
    }
}
