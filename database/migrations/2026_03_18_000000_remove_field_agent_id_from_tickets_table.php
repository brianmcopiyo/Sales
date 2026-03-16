<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove field agent assignment from tickets.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'field_agent_id')) {
                $table->dropForeign(['field_agent_id']);
                $table->dropColumn('field_agent_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->uuid('field_agent_id')->nullable()->after('assigned_to');
            $table->foreign('field_agent_id')->references('user_id')->on('field_agents')->onDelete('set null');
        });
    }
};
