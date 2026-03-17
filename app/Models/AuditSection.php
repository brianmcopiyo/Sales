<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class AuditSection extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = ['audit_template_id', 'name', 'sort_order'];

    public function template()
    {
        return $this->belongsTo(AuditTemplate::class, 'audit_template_id');
    }

    public function questions()
    {
        return $this->hasMany(AuditQuestion::class, 'audit_section_id')->orderBy('sort_order');
    }
}
