<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;

class DevicesList extends Command
{
    protected $signature = 'devices {--output=table : Output format: table, json, or count}';

    protected $description = 'List devices (all statuses).';

    public function handle(): int
    {
        $devices = Device::query()
            ->with(['product:id,name', 'branch:id,name'])
            ->orderBy('id')
            ->get();

        $format = $this->option('output');

        if ($devices->isEmpty()) {
            $this->info('No devices found.');
            return self::SUCCESS;
        }

        if ($format === 'count') {
            $byStatus = $devices->groupBy('status')->map(fn ($group) => $group->count());
            foreach ($byStatus as $status => $count) {
                $this->line("{$status}: {$count}");
            }
            $this->line('Total: ' . $devices->count());
            return self::SUCCESS;
        }

        if ($format === 'json') {
            $this->line($devices->toJson(JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $rows = $devices->map(fn (Device $d) => [
            $d->id,
            $d->imei,
            $d->status,
            $d->product?->name ?? '-',
            $d->branch?->name ?? '-',
            $d->sale_id ?? '-',
            $d->updated_at?->format('Y-m-d H:i'),
        ])->all();

        $this->table(
            ['ID', 'IMEI', 'Status', 'Product', 'Branch', 'Sale ID', 'Updated At'],
            $rows
        );

        $this->newLine();
        $this->info('Total: ' . $devices->count() . ' device(s).');

        return self::SUCCESS;
    }
}
