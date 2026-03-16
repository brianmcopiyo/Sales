<?php

namespace App\Exports;

use App\Models\Device;
use App\Models\Branch;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class OverstayedDevicesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(protected Request $request) {}

    public function query()
    {
        $user = auth()->user();
        $isFieldAgent = $user && $user->fieldAgentProfile && $user->branch_id;
        $allowedBranchIds = $user && $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;
        $branchFilter = $this->request->get('branch_id') ?? $this->request->get('branch');
        $days = max(1, (int) $this->request->get('days', 5));
        $cutoff = now()->subDays($days);

        $query = Device::query()
            ->with(['product', 'branch'])
            ->whereIn('status', ['available', 'assigned'])
            ->where('created_at', '<=', $cutoff);

        if ($isFieldAgent) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($branchFilter && ($allowedBranchIds === null || in_array($branchFilter, $allowedBranchIds, true))) {
            $query->where('branch_id', $branchFilter);
        } elseif ($user && $user->branch_id) {
            $query->whereIn('branch_id', $allowedBranchIds ?? []);
        }

        if ($this->request->filled('search')) {
            $term = $this->request->get('search');
            $query->where('imei', 'like', "%{$term}%");
        }
        if ($this->request->filled('product_id')) {
            $query->where('product_id', $this->request->get('product_id'));
        }

        return $query->orderBy('created_at');
    }

    public function headings(): array
    {
        return [
            'IMEI',
            'Product',
            'Branch',
            'Status',
            'Days stock',
            'Date added',
        ];
    }

    public function map($device): array
    {
        $daysInStock = (int) $device->created_at->diffInDays(now());

        return [
            $device->imei ?? '',
            $device->product?->name ?? '',
            $device->branch?->name ?? '—',
            ucfirst($device->status ?? ''),
            $daysInStock,
            $device->created_at?->format('Y-m-d H:i') ?? '—',
        ];
    }
}
