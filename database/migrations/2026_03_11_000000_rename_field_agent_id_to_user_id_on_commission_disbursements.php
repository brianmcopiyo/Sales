<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Safely rename field_agent_id to user_id on commission_disbursements.
     * Commissions are tied to users; this removes the field-agent naming.
     */
    public function up(): void
    {
        $table = 'commission_disbursements';

        if (Schema::hasColumn($table, 'user_id') && !Schema::hasColumn($table, 'field_agent_id')) {
            return;
        }

        if (!Schema::hasColumn($table, 'user_id')) {
            Schema::table($table, function (Blueprint $t) {
                $t->uuid('user_id')->nullable()->after('id');
            });
        }

        if (Schema::hasColumn($table, 'field_agent_id')) {
            DB::table($table)->whereNotNull('field_agent_id')->update([
                'user_id' => DB::raw('field_agent_id'),
            ]);
        }

        if (Schema::hasColumn($table, 'field_agent_id')) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['field_agent_id']);
                $t->dropColumn('field_agent_id');
            });
        }

        Schema::table($table, function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        $table = 'commission_disbursements';

        Schema::table($table, function (Blueprint $t) {
            $t->dropForeign(['user_id']);
        });

        Schema::table($table, function (Blueprint $t) {
            $t->uuid('field_agent_id')->nullable()->after('id');
        });

        DB::table($table)->whereNotNull('user_id')->update([
            'field_agent_id' => DB::raw('user_id'),
        ]);

        Schema::table($table, function (Blueprint $t) {
            $t->foreign('field_agent_id')->references('id')->on('users')->onDelete('cascade');
            $t->dropColumn('user_id');
        });
    }
};
