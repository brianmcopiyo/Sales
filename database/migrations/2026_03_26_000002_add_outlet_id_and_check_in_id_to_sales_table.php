<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignUuid('outlet_id')->nullable()->after('customer_id')->constrained('outlets')->nullOnDelete();
            $table->foreignUuid('check_in_id')->nullable()->after('outlet_id')->constrained('check_ins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['outlet_id']);
            $table->dropForeign(['check_in_id']);
        });
    }
};
