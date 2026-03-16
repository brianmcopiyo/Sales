<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeOtpsEmailNullable extends Command
{
    protected $signature = 'otps:make-email-nullable';

    protected $description = 'Make otps.email column nullable (fixes login for phone-only users). Run once if migration was not applied.';

    public function handle(): int
    {
        if (!Schema::hasTable('otps')) {
            $this->error('Table otps does not exist.');
            return self::FAILURE;
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE otps MODIFY email VARCHAR(255) NULL');
            $this->info('otps.email is now nullable (MySQL).');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE otps ALTER COLUMN email DROP NOT NULL');
            $this->info('otps.email is now nullable (PostgreSQL).');
        } else {
            $this->error('Unsupported driver: ' . $driver);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
