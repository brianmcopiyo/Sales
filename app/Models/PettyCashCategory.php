<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettyCashCategory extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function requests()
    {
        return $this->hasMany(PettyCashRequest::class, 'petty_cash_category_id');
    }

    public static function slugFromName(string $name): string
    {
        return \Illuminate\Support\Str::slug($name);
    }
}
