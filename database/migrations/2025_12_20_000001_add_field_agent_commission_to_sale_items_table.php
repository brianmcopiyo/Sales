<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->uuid('field_agent_id')->nullable()->after('device_id');
            $table->decimal('commission_per_device', 10, 2)->default(0)->after('subtotal');
            $table->decimal('commission_amount', 10, 2)->default(0)->after('commission_per_device');

            // Field agent id stores the user id (since field agents are users)
            $table->foreign('field_agent_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['field_agent_id']);
            $table->dropColumn(['field_agent_id', 'commission_per_device', 'commission_amount']);
        });
    }
};


