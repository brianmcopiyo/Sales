<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class BillAttachment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'bill_id',
        'file_path',
        'original_name',
        'uploaded_by',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
