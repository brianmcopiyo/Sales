<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\PettyCashRequest;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class PettyCashRequestsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(protected Request $request) {}

    public function query()
    {
        $allowedBranchIds = auth()->user() && auth()->user()->branch_id
            ? Branch::selfAndDescendantIds(auth()->user()->branch_id)
            : null;

        $query = PettyCashRequest::with(['fund.branch', 'categoryRelation', 'requestedByUser', 'approvedByUser', 'disbursedByUser'])
            ->when($allowedBranchIds !== null, fn($q) => $q->whereHas('fund', fn($f) => $f->whereIn('branch_id', $allowedBranchIds)))
            ->latest();

        if ($this->request->filled('status')) {
            $query->where('status', $this->request->status);
        }
        if ($this->request->filled('branch_id')) {
            $query->whereHas('fund', fn($f) => $f->where('branch_id', $this->request->branch_id));
        }
        if ($this->request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $this->request->date_from);
        }
        if ($this->request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $this->request->date_to);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Branch',
            'Amount',
            'Category',
            'Reason',
            'Status',
            'Requested By',
            'Request Date',
            'Approved By',
            'Disbursed By',
            'Disbursed At',
        ];
    }

    public function map($row): array
    {
        return [
            $row->fund?->branch?->name ?? '—',
            number_format((float) $row->amount, 2),
            $row->category_name ?? '—',
            $row->reason ?? '—',
            ucfirst($row->status ?? ''),
            $row->requestedByUser?->name ?? '—',
            $row->created_at?->format('Y-m-d H:i') ?? '—',
            $row->approvedByUser?->name ?? '—',
            $row->disbursedByUser?->name ?? '—',
            $row->disbursed_at?->format('Y-m-d H:i') ?? '—',
        ];
    }
}
