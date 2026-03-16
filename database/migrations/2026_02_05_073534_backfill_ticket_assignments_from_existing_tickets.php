<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Backfill assignment history for existing tickets
        if (Schema::hasTable('ticket_assignments') && Schema::hasTable('tickets')) {
            $tickets = DB::table('tickets')
                ->whereNotNull('assigned_to')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('ticket_assignments')
                        ->whereColumn('ticket_assignments.ticket_id', 'tickets.id');
                })
                ->get();

            foreach ($tickets as $ticket) {
                DB::table('ticket_assignments')->insert([
                    'id' => (string) Str::uuid(),
                    'ticket_id' => $ticket->id,
                    'assigned_to' => $ticket->assigned_to,
                    'assigned_by' => $ticket->assigned_to, // Use assigned_to as assigned_by if we don't know who assigned it
                    'assigned_at' => $ticket->created_at,
                    'unassigned_at' => null,
                    'activity_summary' => null,
                    'is_current' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: Remove backfilled assignments
        // This is commented out for safety - uncomment if you need to rollback
        // DB::table('ticket_assignments')->whereColumn('assigned_by', 'assigned_to')->delete();
    }
};
