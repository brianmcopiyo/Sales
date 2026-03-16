<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('first_response_at')->nullable()->after('resolved_at');
            $table->timestamp('last_response_at')->nullable()->after('first_response_at');
            $table->timestamp('due_date')->nullable()->after('last_response_at');
            $table->integer('sequence_number')->nullable()->after('ticket_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['first_response_at', 'last_response_at', 'due_date', 'sequence_number']);
        });
    }
};
