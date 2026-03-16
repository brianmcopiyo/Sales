<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DevicesImeiOnlyImport implements ToCollection, WithHeadingRow
{
    public array $imeis = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $imei = $row->get('imei');
            if ($imei !== null && trim((string) $imei) !== '') {
                $this->imeis[] = trim((string) $imei);
            }
        }
    }
}
