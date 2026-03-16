<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class PettyCashRequest extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DISBURSED = 'disbursed';

    public const CATEGORIES = [
        'office_supplies' => 'Office supplies',
        'travel' => 'Travel',
        'postage' => 'Postage',
        'maintenance' => 'Maintenance',
        'other' => 'Other',
    ];

    protected $fillable = [
        'petty_cash_fund_id',
        'requested_by',
        'amount',
        'petty_cash_category_id',
        'category',
        'reason',
        'attachment_path',
        'status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'disbursed_at',
        'disbursed_by',
        'receipt_attachment_path',
        'proof_of_expenditure_path',
        'proof_of_expenditure_uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'proof_of_expenditure_uploaded_at' => 'datetime',
        ];
    }

    protected function setAttachmentPathAttribute($value): void
    {
        $this->attributes['attachment_path'] = ($value === '' || $value === null) ? null : $value;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isDisbursed(): bool
    {
        return $this->status === self::STATUS_DISBURSED;
    }

    public function hasProofOfExpenditure(): bool
    {
        return ! empty($this->proof_of_expenditure_path);
    }

    public function fund()
    {
        return $this->belongsTo(PettyCashFund::class, 'petty_cash_fund_id');
    }

    public function categoryRelation()
    {
        return $this->belongsTo(PettyCashCategory::class, 'petty_cash_category_id');
    }

    public function getCategoryNameAttribute(): ?string
    {
        if ($this->categoryRelation) {
            return $this->categoryRelation->name;
        }
        if ($this->category && isset(self::CATEGORIES[$this->category])) {
            return self::CATEGORIES[$this->category];
        }
        return $this->category;
    }

    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function disbursedByUser()
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }
}
