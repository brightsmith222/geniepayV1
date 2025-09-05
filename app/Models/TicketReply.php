<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketReply extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
    ];

    /**
     * Get the ticket that owns the reply.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user that made the reply.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the reply is from an admin.
     */
    public function isFromAdmin(): bool
    {
        return is_null($this->user_id);
    }

    /**
     * Get the display name for the reply author.
     */
    public function getAuthorNameAttribute(): string
    {
        if ($this->isFromAdmin()) {
            return 'Support Team';
        }
        
        return $this->user ? $this->user->full_name : 'Unknown User';
    }

     /**
     * Get the attachments for the reply.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

}
