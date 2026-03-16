<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Track when commission was credited so we credit only once (on sale complete).
     * Backfill: credit commission for existing completed sales that were never credited.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('commission_credited_at')->nullable()->after('status');
        });

        // Backfill: for completed sales without commission_credited_at, credit the seller (user)
        $completed = DB::table('sales')->where('status', 'completed')->whereNull('commission_credited_at')->get(['id', 'sold_by']);
        foreach ($completed as $sale) {
            $total = (float) DB::table('sale_items')->where('sale_id', $sale->id)->sum('commission_amount');
            if ($total > 0 && $sale->sold_by) {
                $user = DB::table('users')->where('id', $sale->sold_by)->first(['total_commission_earned', 'commission_available_balance']);
                if ($user) {
                    DB::table('users')->where('id', $sale->sold_by)->update([
                        'total_commission_earned' => (float) ($user->total_commission_earned ?? 0) + $total,
                        'commission_available_balance' => (float) ($user->commission_available_balance ?? 0) + $total,
                    ]);
                }
                DB::table('sales')->where('id', $sale->id)->update(['commission_credited_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('commission_credited_at');
        });
    }
};
