<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('audit_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('audit_template_id')->constrained('audit_templates')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('audit_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('audit_section_id')->constrained('audit_sections')->cascadeOnDelete();
            $table->text('question_text');
            $table->string('question_type'); // yes_no, score, photo
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('score_max')->nullable(); // for score type, e.g. 5
            $table->timestamps();
        });

        Schema::create('audit_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('check_in_id')->constrained('check_ins')->cascadeOnDelete();
            $table->foreignUuid('audit_template_id')->constrained('audit_templates')->cascadeOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedTinyInteger('compliance_score')->nullable(); // 0-100
            $table->timestamps();
        });

        Schema::table('audit_runs', function (Blueprint $table) {
            $table->unique('check_in_id'); // one audit run per check-in (per template could be relaxed later)
        });

        Schema::create('audit_run_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('audit_run_id')->constrained('audit_runs')->cascadeOnDelete();
            $table->foreignUuid('audit_question_id')->constrained('audit_questions')->cascadeOnDelete();
            $table->string('answer_value')->nullable(); // yes/no, or numeric score, or null for photo-only
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        Schema::table('audit_run_answers', function (Blueprint $table) {
            $table->unique(['audit_run_id', 'audit_question_id']);
        });

        Schema::table('audit_runs', function (Blueprint $table) {
            $table->index(['audit_template_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_run_answers');
        Schema::dropIfExists('audit_runs');
        Schema::dropIfExists('audit_questions');
        Schema::dropIfExists('audit_sections');
        Schema::dropIfExists('audit_templates');
    }
};
