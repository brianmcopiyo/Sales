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
        Schema::create('ticket_tag_ticket', function (Blueprint $table) {
            $table->uuid('ticket_id');
            $table->uuid('ticket_tag_id');
            $table->timestamps();

            $table->primary(['ticket_id', 'ticket_tag_id']);
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('ticket_tag_id')->references('id')->on('ticket_tags')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_tag_ticket');
    }
};
