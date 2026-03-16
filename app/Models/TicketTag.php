<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class TicketTag extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'color',
        'description',
    ];

    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'ticket_tag_ticket');
    }
}
