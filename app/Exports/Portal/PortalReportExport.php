<?php

namespace App\Exports\Portal;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PortalReportExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected array $data,
        protected string $reportType
    ) {}

    public function collection()
    {
        return collect($this->data['rows'] ?? []);
    }

    public function headings(): array
    {
        return $this->data['headers'] ?? [];
    }

    public function map($row): array
    {
        return $row;
    }
}
