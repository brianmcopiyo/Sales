<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class CustomerDisbursementsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(protected Builder $query) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Customer',
            'Branch',
            'Customer Email',
            'Device (IMEI)',
            'Device (Product)',
            'Amount (TSh)',
            'Disbursement Phone',
            'Status',
            'Sale #',
            'Disbursed By',
            'Date',
        ];
    }

    public function map($d): array
    {
        return [
            $d->customer?->name ?? '',
            $d->branch_for_display?->name ?? '—',
            $d->customer?->email ?? '—',
            $d->device?->imei ?? '—',
            $d->device?->product?->name ?? '—',
            number_format((float) $d->amount, 2),
            $d->disbursement_phone ?? '—',
            ucfirst($d->status ?? ''),
            $d->sale?->sale_number ?? '—',
            $d->disbursedBy?->name ?? '—',
            $d->created_at?->format('Y-m-d H:i') ?? '',
        ];
    }
}
