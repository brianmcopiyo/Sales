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
        Schema::create('ticket_escalations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('requested_by'); // User requesting escalation
            $table->uuid('requested_to'); // User being escalated to
            $table->text('reason'); // Why escalation is needed
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->uuid('responded_by')->nullable(); // User who responded (accepted/rejected)
            $table->text('response_notes')->nullable(); // Notes from the response
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('requested_to')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('responded_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['ticket_id', 'status']);
            $table->index('requested_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_escalations');
    }
};
