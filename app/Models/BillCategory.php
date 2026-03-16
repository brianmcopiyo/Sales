<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class BillCategory extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'bill_categories';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'category_id');
    }

    public function recurringBills()
    {
        return $this->hasMany(RecurringBill::class, 'category_id');
    }
}
