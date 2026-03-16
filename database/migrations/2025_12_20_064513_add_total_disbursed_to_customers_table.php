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
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('total_disbursed', 10, 2)->default(0)->after('is_active');
        });

        // Backfill existing data from customer_disbursements (if table exists)
        if (Schema::hasTable('customer_disbursements')) {
            DB::statement("
                UPDATE customers
                SET total_disbursed = COALESCE((
                    SELECT SUM(amount)
                    FROM customer_disbursements
                    WHERE customer_disbursements.customer_id = customers.id
                ), 0)
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('total_disbursed');
        });
    }
};
