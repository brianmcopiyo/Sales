<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;

class DevicesAvailable extends Command
{
    protected $signature = 'devices:available
                            {--output=table : Output format: table, json, or count}';

    protected $description = 'List devices with status "available".';

    public function handle(): int
    {
        $devices = Device::query()
            ->where('status', 'available')
            ->with(['product:id,name', 'branch:id,name'])
            ->orderBy('id')
            ->get();

        $format = $this->option('output');

        if ($devices->isEmpty()) {
            $this->info('No available devices found.');
            return self::SUCCESS;
        }

        if ($format === 'count') {
            $this->info('Available devices: ' . $devices->count());
            return self::SUCCESS;
        }

        if ($format === 'json') {
            $this->line($devices->toJson(JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $rows = $devices->map(fn (Device $d) => [
            $d->id,
            $d->imei,
            $d->product?->name ?? '-',
            $d->branch?->name ?? '-',
            $d->updated_at?->format('Y-m-d H:i'),
        ])->all();

        $this->table(
            ['ID', 'IMEI', 'Product', 'Branch', 'Updated At'],
            $rows
        );

        $this->newLine();
        $this->info('Total: ' . $devices->count() . ' device(s).');

        return self::SUCCESS;
    }
}
