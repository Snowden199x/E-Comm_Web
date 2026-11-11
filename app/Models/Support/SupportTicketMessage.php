<?php

namespace App\Models\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SupportTicketMessage extends Model
{
    protected $fillable = ['support_ticket_id', 'sender_id', 'body', 'is_staff'];

    protected function casts(): array
    {
        return ['is_staff' => 'boolean'];
    }

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** Vendo staff replies show as "Vendo Support" even when the admin account is later renamed. */
    public function getSenderLabelAttribute(): string
    {
        return $this->is_staff ? 'Vendo Support' : ($this->sender?->name ?? 'Buyer');
    }
}
