<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('notes');
            $table->uuid('rejected_by')->nullable()->after('rejection_reason');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');

            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
        });

        // Add 'rejected' to status enum (MySQL)
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE stock_transfers MODIFY COLUMN status ENUM('pending', 'in_transit', 'received', 'cancelled', 'rejected') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['rejection_reason', 'rejected_by', 'rejected_at']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE stock_transfers MODIFY COLUMN status ENUM('pending', 'in_transit', 'received', 'cancelled') DEFAULT 'pending'");
        }
    }
};
