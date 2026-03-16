<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure branch stock quantities are never negative (0 or positive only).
     */
    public function up(): void
    {
        DB::table('branch_stocks')
            ->where('quantity', '<', 0)
            ->update(['quantity' => 0]);
    }

    /**
     * Reverse: we cannot restore previous negative values.
     */
    public function down(): void
    {
        // No-op: do not re-introduce negative quantities
    }
};
