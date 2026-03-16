<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class Vendor extends Model
{
    use HasFactory, HasUuid;

    public const PAYMENT_TERMS_NET_30 = 'net_30';
    public const PAYMENT_TERMS_DUE_ON_RECEIPT = 'due_on_receipt';
    public const PAYMENT_TERMS_CUSTOM = 'custom';

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'default_payment_terms',
        'terms_days',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'terms_days' => 'integer',
        ];
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Compute due date from invoice date using vendor's default terms (if set).
     */
    public function dueDateFromInvoiceDate($invoiceDate): ?\Carbon\Carbon
    {
        $date = \Carbon\Carbon::parse($invoiceDate);
        if ($this->default_payment_terms === self::PAYMENT_TERMS_DUE_ON_RECEIPT) {
            return $date->copy();
        }
        if ($this->default_payment_terms === self::PAYMENT_TERMS_NET_30) {
            return $date->copy()->addDays(30);
        }
        if ($this->default_payment_terms === self::PAYMENT_TERMS_CUSTOM && $this->terms_days !== null) {
            return $date->copy()->addDays((int) $this->terms_days);
        }
        return null;
    }
}
