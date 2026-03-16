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
        Schema::create('commission_disbursements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('field_agent_id'); // user_id (field agents are users)
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->uuid('processed_by')->nullable(); // user_id who approved/rejected
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('field_agent_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_disbursements');
    }
};
