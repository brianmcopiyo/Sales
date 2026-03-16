<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('field_agents', function (Blueprint $table) {
            $table->decimal('total_earned', 10, 2)->default(0)->after('is_active');
            $table->decimal('available_balance', 10, 2)->default(0)->after('total_earned');
        });

        // Backfill existing data from sale_items and commission_disbursements
        DB::statement("
            UPDATE field_agents
            SET total_earned = COALESCE((
                SELECT SUM(commission_amount)
                FROM sale_items
                WHERE sale_items.field_agent_id = field_agents.user_id
            ), 0),
            available_balance = COALESCE((
                SELECT SUM(commission_amount)
                FROM sale_items
                WHERE sale_items.field_agent_id = field_agents.user_id
            ), 0) - COALESCE((
                SELECT SUM(amount)
                FROM commission_disbursements
                WHERE commission_disbursements.field_agent_id = field_agents.user_id
                AND commission_disbursements.status = 'completed'
            ), 0)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('field_agents', function (Blueprint $table) {
            $table->dropColumn(['total_earned', 'available_balance']);
        });
    }
};
