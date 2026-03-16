<?php

namespace App\Exports;

use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class StockTransfersExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected Request $request
    ) {}

    public function query()
    {
        $user = auth()->user();
        $query = StockTransfer::with(['fromBranch', 'toBranch', 'product', 'items.product', 'creator', 'receiver', 'rejectedByUser'])
            ->when($user->branch_id && ! $user->isAdmin(), function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('from_branch_id', $user->branch_id)
                        ->orWhere('to_branch_id', $user->branch_id);
                });
            });

        if ($this->request->filled('status')) {
            $query->where('status', $this->request->get('status'));
        }
        if ($this->request->filled('from_branch_id')) {
            $query->where('from_branch_id', $this->request->get('from_branch_id'));
        }
        if ($this->request->filled('to_branch_id')) {
            $query->where('to_branch_id', $this->request->get('to_branch_id'));
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
            'Transfer #',
            'Product',
            'From',
            'To',
            'Quantity',
            'Quantity Received',
            'Status',
            'Sent by',
            'Received by',
            'Received at',
            'Rejected by',
            'Rejected at',
            'Created',
        ];
    }

    public function map($transfer): array
    {
        $qtyReceived = $transfer->status === 'received' || $transfer->quantity_received !== null
            ? (string) $transfer->effective_quantity_received
            : '';

        return [
            $transfer->transfer_number ?? '',
            $transfer->product?->name ?? $transfer->items->first()?->product?->name ?? '',
            $transfer->fromBranch?->name ?? '',
            $transfer->toBranch?->name ?? '',
            (int) $transfer->total_quantity,
            $qtyReceived,
            ucfirst(str_replace('_', ' ', $transfer->status ?? '')),
            $transfer->creator?->name ?? '—',
            $transfer->receiver?->name ?? '—',
            $transfer->received_at ? $transfer->received_at->format('Y-m-d H:i') : '—',
            $transfer->rejectedByUser?->name ?? '—',
            $transfer->rejected_at ? $transfer->rejected_at->format('Y-m-d H:i') : '—',
            $transfer->created_at ? $transfer->created_at->format('Y-m-d H:i') : '',
        ];
    }
}
