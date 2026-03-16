<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\StockReconciliationService;
use Illuminate\Console\Command;

class SyncStockFromDevicesCommand extends Command
{
    protected $signature = 'stock:sync-from-devices
                            {--branch= : Branch ID, code, or name (optional, otherwise all branches)}
                            {--product= : Product ID or SKU (optional, otherwise all products)}';

    protected $description = 'Set current branch stock to the count of available (non-sold) devices per branch/product.';

    public function handle(StockReconciliationService $service): int
    {
        $branch = $this->option('branch');
        $product = $this->option('product');

        $this->info('Syncing branch stock from available device counts...');

        $result = $service->syncStockFromDeviceCount($branch, $product, null);

        $this->info(sprintf(
            'Done. Updated: %d branch stock row(s), created: %d new row(s).',
            $result['updated'],
            $result['created']
        ));

        return self::SUCCESS;
    }
}
