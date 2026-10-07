<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function build()
    {
        $loginUrl = match ($this->user->role) {
            'seller' => route('seller.login'),
            'logistics_center' => route('logistics.login'),
            default => route('buyer.login'),
        };

        return $this->subject('Your Vendo Account Has Been Approved!')
            ->view('emails.account-approved')
            ->with(['user' => $this->user, 'loginUrl' => $loginUrl]);
    }
}