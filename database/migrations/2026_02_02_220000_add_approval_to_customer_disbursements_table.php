<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add approval workflow to customer disbursements (secondary approval for auditing).
     */
    public function up(): void
    {
        Schema::table('customer_disbursements', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('disbursed_by');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->uuid('approved_by')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->uuid('rejected_by')->nullable()->after('rejected_at');
            $table->text('rejection_reason')->nullable()->after('rejected_by');
        });

        // Backfill existing rows as approved (approved_by = disbursed_by, approved_at = created_at)
        DB::table('customer_disbursements')
            ->whereNull('approved_at')
            ->update([
                'status' => 'approved',
                'approved_at' => DB::raw('created_at'),
                'approved_by' => DB::raw('disbursed_by'),
                'updated_at' => now(),
            ]);

        Schema::table('customer_disbursements', function (Blueprint $table) {
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('customer_disbursements', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'status',
                'approved_at',
                'approved_by',
                'rejected_at',
                'rejected_by',
                'rejection_reason',
            ]);
        });
    }
};
