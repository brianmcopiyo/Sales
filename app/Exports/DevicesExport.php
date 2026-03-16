<?php

namespace App\Exports;

use App\Models\Device;
use App\Models\Branch;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class DevicesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(protected Request $request) {}

    public function query()
    {
        $user = auth()->user();
        $allowedBranchIds = $user && $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
        $branchFilter = $this->request->get('branch_id') ?? $this->request->get('branch');

        $query = Device::with(['product', 'customer', 'branch', 'sale.soldBy']);
        if ($allowedBranchIds !== null) {
            if ($branchFilter && in_array($branchFilter, $allowedBranchIds, true)) {
                $query->where('branch_id', $branchFilter);
            } else {
                $query->whereIn('branch_id', $allowedBranchIds);
            }
        }

        if ($this->request->filled('search')) {
            $term = $this->request->get('search');
            $query->where('imei', 'like', "%{$term}%");
        }
        if ($this->request->filled('status')) {
            $query->where('status', $this->request->get('status'));
        }
        if ($this->request->filled('product_id')) {
            $query->where('product_id', $this->request->get('product_id'));
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
            'IMEI',
            'Product',
            'Branch',
            'Customer',
            'Status',
            'Date added',
            'Sold by',
            'Sold date',
        ];
    }

    public function map($device): array
    {
        return [
            $device->imei ?? '',
            $device->product?->name ?? '',
            $device->branch?->name ?? '—',
            $device->customer?->name ?? '—',
            ucfirst($device->status ?? ''),
            $device->created_at?->format('Y-m-d') ?? '—',
            $device->sale?->soldBy?->name ?? '—',
            $device->sale?->created_at?->format('Y-m-d') ?? '—',
        ];
    }
}
