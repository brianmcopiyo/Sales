<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class AuditRun extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = ['check_in_id', 'audit_template_id', 'completed_at', 'compliance_score'];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'compliance_score' => 'integer',
        ];
    }

    public function checkIn()
    {
        return $this->belongsTo(CheckIn::class);
    }

    public function template()
    {
        return $this->belongsTo(AuditTemplate::class, 'audit_template_id');
    }

    public function answers()
    {
        return $this->hasMany(AuditRunAnswer::class, 'audit_run_id');
    }

    public function computeComplianceScore(): ?int
    {
        $template = $this->template;
        if (!$template) {
            return null;
        }
        $totalWeight = 0;
        $earned = 0.0;
        foreach ($template->sections as $section) {
            foreach ($section->questions as $question) {
                $max = $question->maxScore();
                if ($max <= 0) {
                    continue;
                }
                $answer = $this->answers->firstWhere('audit_question_id', $question->id);
                $score = $question->scoreFromAnswer(
                    $answer?->answer_value,
                    $answer?->photo_path
                );
                if ($score !== null) {
                    $totalWeight += $max;
                    $earned += $score * $max;
                }
            }
        }
        if ($totalWeight <= 0) {
            return null;
        }
        return (int) round(($earned / $totalWeight) * 100);
    }
}
