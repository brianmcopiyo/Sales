<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_templates', function (Blueprint $table) {
            $table->enum('category', ['general', 'shelf', 'compliance', 'hygiene'])
                ->default('general')
                ->after('is_active');
            $table->string('reference_image')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('audit_templates', function (Blueprint $table) {
            $table->dropColumn(['category', 'reference_image']);
        });
    }
};
