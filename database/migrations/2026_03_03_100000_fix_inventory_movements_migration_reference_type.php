<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * fix_stock_levels_for_existing_devices inserted reference_type = 'Migration'
     * (meaning "created by a migration"). Laravel's morphTo() treats reference_type
     * as a class name, so it tries to load class "Migration" and fails.
     * Set these to null so the relation has no reference.
     */
    public function up(): void
    {
        DB::table('inventory_movements')
            ->where('reference_type', 'Migration')
            ->update([
                'reference_type' => null,
                'reference_id' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably restore 'Migration' reference_type; leave as no-op
    }
};
