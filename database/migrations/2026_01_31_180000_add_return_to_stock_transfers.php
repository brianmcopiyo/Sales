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
            $table->text('return_reason')->nullable()->after('sender_confirmed_at');
            $table->uuid('returned_by')->nullable()->after('return_reason');
            $table->timestamp('returned_at')->nullable()->after('returned_by');

            $table->foreign('returned_by')->references('id')->on('users')->onDelete('set null');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE stock_transfers MODIFY COLUMN status ENUM('pending', 'in_transit', 'pending_sender_confirmation', 'received', 'cancelled', 'rejected', 'returned') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['returned_by']);
            $table->dropColumn(['return_reason', 'returned_by', 'returned_at']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE stock_transfers MODIFY COLUMN status ENUM('pending', 'in_transit', 'pending_sender_confirmation', 'received', 'cancelled', 'rejected') DEFAULT 'pending'");
        }
    }
};
