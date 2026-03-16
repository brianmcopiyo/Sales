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
        // This migration is handled by the SLA fields migration
        // The sequence_number column is added there
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Handled by SLA fields migration
    }
};
