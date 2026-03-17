<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class AuditQuestion extends Model
{
    use HasFactory, HasUuid;

    public const TYPE_YES_NO = 'yes_no';
    public const TYPE_SCORE = 'score';
    public const TYPE_PHOTO = 'photo';

    protected $fillable = ['audit_section_id', 'question_text', 'question_type', 'sort_order', 'score_max'];

    public function section()
    {
        return $this->belongsTo(AuditSection::class, 'audit_section_id');
    }

    public function runAnswers()
    {
        return $this->hasMany(AuditRunAnswer::class, 'audit_question_id');
    }

    public function maxScore(): int
    {
        if ($this->question_type === self::TYPE_YES_NO) {
            return 1;
        }
        if ($this->question_type === self::TYPE_SCORE && $this->score_max) {
            return (int) $this->score_max;
        }
        if ($this->question_type === self::TYPE_PHOTO) {
            return 1; // present = 1
        }
        return 0;
    }

    public function scoreFromAnswer(?string $answerValue, ?string $photoPath): ?float
    {
        if ($this->question_type === self::TYPE_YES_NO) {
            return $answerValue === 'yes' ? 1.0 : ($answerValue === 'no' ? 0.0 : null);
        }
        if ($this->question_type === self::TYPE_SCORE && $this->score_max) {
            $n = $answerValue !== null && $answerValue !== '' ? (float) $answerValue : null;
            return $n !== null ? min($this->score_max, max(0, $n)) / (float) $this->score_max : null;
        }
        if ($this->question_type === self::TYPE_PHOTO) {
            return $photoPath ? 1.0 : null;
        }
        return null;
    }
}
