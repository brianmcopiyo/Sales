<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_attachments', function (Blueprint $table) {
            $table->string('attachment_type', 32)->default('initiation')->after('sale_id');
        });
    }

    public function down(): void
    {
        Schema::table('sale_attachments', function (Blueprint $table) {
            $table->dropColumn('attachment_type');
        });
    }
};
