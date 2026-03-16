<?php

namespace App\Exports;

use App\Models\Bill;
use App\Models\Branch;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class BillsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(protected Request $request) {}

    public function query()
    {
        $allowedBranchIds = auth()->user() && auth()->user()->branch_id
            ? Branch::selfAndDescendantIds(auth()->user()->branch_id)
            : null;

        $query = Bill::with(['vendor', 'branch', 'category'])
            ->when($allowedBranchIds !== null, fn ($q) => $q->where(function ($q) use ($allowedBranchIds) {
                $q->whereIn('branch_id', $allowedBranchIds)->orWhereNull('branch_id');
            }))
            ->latest();

        if ($this->request->filled('branch_id')) {
            $query->where('branch_id', $this->request->branch_id);
        }
        if ($this->request->filled('vendor_id')) {
            $query->where('vendor_id', $this->request->vendor_id);
        }
        if ($this->request->filled('category_id')) {
            $query->where('category_id', $this->request->category_id);
        }
        if ($this->request->filled('status')) {
            $query->where('status', $this->request->status);
        }
        if ($this->request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $this->request->date_from);
        }
        if ($this->request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $this->request->date_to);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Vendor',
            'Invoice #',
            'Invoice Date',
            'Due Date',
            'Amount',
            'Currency',
            'Category',
            'Branch',
            'Status',
            'Paid At',
            'Payment Ref',
        ];
    }

    public function map($bill): array
    {
        return [
            $bill->vendor?->name ?? '',
            $bill->invoice_number ?? '',
            $bill->invoice_date?->format('Y-m-d') ?? '',
            $bill->due_date?->format('Y-m-d') ?? '',
            number_format((float) $bill->amount, 2),
            $bill->currency ?? 'TSh',
            $bill->category?->name ?? '',
            $bill->branch?->name ?? '',
            $bill->status ?? '',
            $bill->paid_at?->format('Y-m-d') ?? '',
            $bill->payment_reference ?? '',
        ];
    }
}
