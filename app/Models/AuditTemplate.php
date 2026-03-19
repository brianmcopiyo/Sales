<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class AuditTemplate extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = ['name', 'description', 'is_active', 'category', 'reference_image'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'category' => 'string',
        ];
    }

    public static function categories(): array
    {
        return [
            'general'    => 'General',
            'shelf'      => 'Shelf / Planogram',
            'compliance' => 'Compliance',
            'hygiene'    => 'Hygiene',
        ];
    }

    public function sections()
    {
        return $this->hasMany(AuditSection::class, 'audit_template_id')->orderBy('sort_order');
    }

    public function runs()
    {
        return $this->hasMany(AuditRun::class, 'audit_template_id');
    }
}
