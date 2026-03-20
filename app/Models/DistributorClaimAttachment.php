<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DistributorClaimAttachment extends Model
{
    use HasUuid;

    protected $fillable = [
        'claim_id',
        'file_path',
        'original_filename',
        'mime_type',
        'uploaded_by',
    ];

    public function claim()
    {
        return $this->belongsTo(DistributorClaim::class, 'claim_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }
}
