<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class AuditRunAnswer extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = ['audit_run_id', 'audit_question_id', 'answer_value', 'photo_path'];

    public function auditRun()
    {
        return $this->belongsTo(AuditRun::class);
    }

    public function question()
    {
        return $this->belongsTo(AuditQuestion::class, 'audit_question_id');
    }
}
