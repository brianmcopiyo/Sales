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
            $table->uuid('sender_confirmed_by')->nullable()->after('received_notes');
            $table->timestamp('sender_confirmed_at')->nullable()->after('sender_confirmed_by');

            $table->foreign('sender_confirmed_by')->references('id')->on('users')->onDelete('set null');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE stock_transfers MODIFY COLUMN status ENUM('pending', 'in_transit', 'pending_sender_confirmation', 'received', 'cancelled', 'rejected') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['sender_confirmed_by']);
            $table->dropColumn(['sender_confirmed_by', 'sender_confirmed_at']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE stock_transfers MODIFY COLUMN status ENUM('pending', 'in_transit', 'received', 'cancelled', 'rejected') DEFAULT 'pending'");
        }
    }
};
