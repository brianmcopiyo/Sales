<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Commission balance on User so commissions are tied to users, not only field agents.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('total_commission_earned', 12, 2)->default(0)->after('suspended_at');
            $table->decimal('commission_available_balance', 12, 2)->default(0)->after('total_commission_earned');
        });

        // Backfill from field_agents so existing balances move to users
        if (Schema::hasTable('field_agents') && Schema::hasColumn('field_agents', 'total_earned')) {
            DB::statement("
                UPDATE users u
                INNER JOIN field_agents fa ON fa.user_id = u.id
                SET
                    u.total_commission_earned = COALESCE(fa.total_earned, 0),
                    u.commission_available_balance = COALESCE(fa.available_balance, 0)
            ");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['total_commission_earned', 'commission_available_balance']);
        });
    }
};
