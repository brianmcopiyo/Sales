<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class DistributorClaim extends Model
{
    use HasUuid;

    const TYPES = [
        'damaged_goods'    => 'Damaged Goods',
        'short_shipment'   => 'Short Shipment',
        'scheme_settlement'=> 'Scheme Settlement',
        'other'            => 'Other',
    ];

    const STATUSES = [
        'pending'      => 'Pending',
        'under_review' => 'Under Review',
        'approved'     => 'Approved',
        'rejected'     => 'Rejected',
        'settled'      => 'Settled',
    ];

    protected $fillable = [
        'distributor_profile_id',
        'claim_number',
        'type',
        'status',
        'description',
        'amount_claimed',
        'amount_approved',
        'reference_sale_id',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_claimed'  => 'decimal:2',
            'amount_approved' => 'decimal:2',
            'reviewed_at'     => 'datetime',
            'settled_at'      => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DistributorClaim $claim) {
            if (empty($claim->claim_number)) {
                $claim->claim_number = 'CLM-' . strtoupper(uniqid());
            }
        });
    }

    public function distributorProfile()
    {
        return $this->belongsTo(DistributorProfile::class);
    }

    public function referenceSale()
    {
        return $this->belongsTo(Sale::class, 'reference_sale_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function attachments()
    {
        return $this->hasMany(DistributorClaimAttachment::class, 'claim_id');
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
