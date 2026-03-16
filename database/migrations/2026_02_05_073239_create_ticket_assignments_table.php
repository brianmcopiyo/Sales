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
        Schema::create('ticket_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('assigned_to'); // User who was assigned
            $table->uuid('assigned_by'); // User who made the assignment
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable(); // When they stopped working on it
            $table->text('activity_summary')->nullable(); // What they did while assigned
            $table->boolean('is_current')->default(false); // Is this the current assignment
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['ticket_id', 'is_current']);
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_assignments');
    }
};
