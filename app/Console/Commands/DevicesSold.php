<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;

class DevicesSold extends Command
{
    protected $signature = 'devices:sold
                            {--nosales : Only list sold devices that have no sale attached}
                            {--output=table : Output format: table, json, or count}';

    protected $description = 'List devices with status "sold". Use --nosales to only show those with no sale attached.';

    public function handle(): int
    {
        $query = Device::query()
            ->where('status', 'sold')
            ->with(['product:id,name', 'branch:id,name']);

        if ($this->option('nosales')) {
            $query->whereNull('sale_id');
        }

        $devices = $query->orderBy('id')->get();

        $format = $this->option('output');
        $label = $this->option('nosales')
            ? 'sold with no sale attached'
            : 'sold';

        if ($devices->isEmpty()) {
            $this->info("No devices found ({$label}).");
            return self::SUCCESS;
        }

        if ($format === 'count') {
            $this->info("Devices {$label}: " . $devices->count());
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
            $d->sale_id ?? '-',
            $d->sold_by_user_id ?? '-',
            $d->updated_at?->format('Y-m-d H:i'),
        ])->all();

        $this->table(
            ['ID', 'IMEI', 'Product', 'Branch', 'Sale ID', 'Sold By (user_id)', 'Updated At'],
            $rows
        );

        $this->newLine();
        $this->info('Total: ' . $devices->count() . ' device(s).');

        return self::SUCCESS;
    }
}
