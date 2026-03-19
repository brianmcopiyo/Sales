<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class CustomersExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(protected Request $request) {}

    public function query()
    {
        $user = $this->request->user();
        $allowedBranchIds = $user && $user->branch_id ? Branch::selfAndDescendantIds($user->branch_id) : null;

        $query = Customer::query()
            ->when($allowedBranchIds !== null, fn($q) => $q->visibleToBranches($allowedBranchIds));

        if ($this->request->filled('branch')) {
            $branchId = $this->request->get('branch');
            if ($allowedBranchIds === null || in_array($branchId, $allowedBranchIds, true)) {
                $query->visibleToBranches([$branchId]);
            }
        }
        if ($this->request->filled('search')) {
            $term = $this->request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }
        if ($this->request->filled('status')) {
            if ($this->request->get('status') === 'active') {
                $query->where('is_active', true);
            }
            if ($this->request->get('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone',
            'ID Number',
            'Address',
            'Status',
            'Created',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->name ?? '',
            $customer->email ?? '',
            $customer->phone ?? '',
            $customer->id_number ?? '',
            $customer->address ?? '',
            $customer->is_active ? 'Active' : 'Inactive',
            $customer->created_at?->format('Y-m-d H:i') ?? '',
        ];
    }
}
