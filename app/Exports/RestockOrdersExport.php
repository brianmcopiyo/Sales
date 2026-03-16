<?php

namespace App\Exports;

use App\Models\RestockOrder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class RestockOrdersExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected Request $request
    ) {}

    public function query()
    {
        $user = auth()->user();
        $query = RestockOrder::with(['branch', 'product', 'creator', 'rejectedBy'])
            ->when($user->branch_id, function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });

        if ($this->request->filled('status')) {
            $query->where('status', $this->request->get('status'));
        }
        if ($this->request->filled('date_from')) {
            $query->whereDate('ordered_at', '>=', $this->request->get('date_from'));
        }
        if ($this->request->filled('date_to')) {
            $query->whereDate('ordered_at', '<=', $this->request->get('date_to'));
        }

        return $query->latest('ordered_at');
    }

    public function headings(): array
    {
        return [
            'Order #',
            'Reference',
            'Product',
            'Branch',
            'Quantity Ordered',
            'Quantity Received',
            'Total Acquisition Cost',
            'Status',
            'Dealership',
            'Expected At',
            'Ordered At',
            'Received At',
            'Created By',
            'Rejected By',
            'Rejected At',
            'Rejection Reason',
        ];
    }

    public function map($order): array
    {
        $statusLabel = match ($order->status ?? '') {
            'pending' => 'Pending',
            'received_partial' => 'Partial',
            'received_full' => 'Received',
            'cancelled' => 'Rejected',
            default => ucfirst(str_replace('_', ' ', $order->status ?? '')),
        };

        return [
            $order->display_order_number ?? $order->order_number ?? '',
            $order->reference_number ?? '—',
            $order->product?->name ?? '',
            $order->branch?->name ?? '',
            (int) $order->quantity_ordered,
            (int) $order->quantity_received,
            $order->total_acquisition_cost !== null ? number_format((float) $order->total_acquisition_cost, 2) : '—',
            $statusLabel,
            $order->dealership_display_name ?? '—',
            $order->expected_at ? $order->expected_at->format('M d, Y') : '—',
            $order->ordered_at ? $order->ordered_at->format('M d, Y H:i') : '—',
            $order->received_at ? $order->received_at->format('M d, Y H:i') : '—',
            $order->creator?->name ?? '—',
            $order->rejectedBy?->name ?? '—',
            $order->rejected_at ? $order->rejected_at->format('M d, Y H:i') : '—',
            $order->rejection_reason ?? '—',
        ];
    }
}
