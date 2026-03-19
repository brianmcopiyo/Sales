<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldExpense extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'outlet_id',
        'expense_date',
        'category',
        'amount',
        'currency',
        'status',
        'description',
        'receipt_path',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}
