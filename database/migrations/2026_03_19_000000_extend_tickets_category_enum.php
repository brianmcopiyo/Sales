<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Extend tickets.category enum to include all categories used by the app.
     */
    public function up(): void
    {
        // MySQL: modify enum to add order, promise, complaint, unsuccessful, credit
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN category ENUM(
                'technical',
                'billing',
                'sales',
                'general',
                'order',
                'promise',
                'complaint',
                'unsuccessful',
                'credit'
            ) DEFAULT 'general'");
        } else {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('category', 32)->default('general')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN category ENUM(
                'technical',
                'billing',
                'sales',
                'general'
            ) DEFAULT 'general'");
        } else {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('category', 32)->default('general')->change();
            });
        }
    }
};
