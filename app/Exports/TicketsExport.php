<?php

namespace App\Exports;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class TicketsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(protected Request $request) {}

    public function query()
    {
        $user = auth()->user();
        if ($user->isCustomer()) {
            $customer = \App\Models\Customer::where('email', $user->email)->orWhere('phone', $user->phone)->first();
            $query = $customer
                ? Ticket::with(['customer', 'assignedTo', 'sale', 'device.product', 'product', 'branch', 'tags'])->where('customer_id', $customer->id)
                : Ticket::query()->whereRaw('1 = 0');
        } else {
            $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
            $query = Ticket::with(['customer', 'assignedTo', 'sale', 'device.product', 'product', 'branch', 'tags']);
            if ($isFieldAgent) {
                $query->where('assigned_to', $user->id);
            } else {
                $query->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id));
            }
        }

        if ($this->request->filled('search')) {
            $search = $this->request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('ticket_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }
        if ($this->request->filled('status')) {
            $query->where('status', $this->request->status);
        }
        if ($this->request->filled('priority')) {
            $query->where('priority', $this->request->priority);
        }
        if ($this->request->filled('category')) {
            $query->where('category', $this->request->category);
        }
        if ($this->request->filled('assigned_to')) {
            if ($this->request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $this->request->assigned_to);
            }
        }
        if ($this->request->filled('branch_id')) {
            $query->where('branch_id', $this->request->branch_id);
        }
        if ($this->request->filled('product_id')) {
            $query->where('product_id', $this->request->product_id);
        }
        if ($this->request->filled('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('id', $this->request->get('tag')));
        }
        if ($this->request->filled('overdue')) {
            $query->where('due_date', '<', now())->whereNotIn('status', ['resolved', 'closed']);
        }
        if ($this->request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $this->request->date_from);
        }
        if ($this->request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $this->request->date_to);
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return ['Ticket #', 'Subject', 'Customer', 'Assigned To', 'Priority', 'Status', 'Created'];
    }

    public function map($ticket): array
    {
        return [
            $ticket->ticket_number ?? '',
            $ticket->subject ?? '',
            $ticket->customer?->name ?? '',
            $ticket->assignedTo?->name ?? '—',
            ucfirst($ticket->priority ?? ''),
            ucfirst($ticket->status ?? ''),
            $ticket->created_at?->format('M d, Y H:i') ?? '',
        ];
    }
}
