<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $table->string('client_ref', 64)->nullable()->after('outlet_id');
            $table->unique(['user_id', 'client_ref'], 'check_ins_user_client_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $table->dropUnique('check_ins_user_client_ref_unique');
            $table->dropColumn('client_ref');
        });
    }
};
