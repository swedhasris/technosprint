<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailThread extends Model
{
    protected $fillable = [
        'ticket_id', 'thread_id', 'subject', 'last_message_at'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
