<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $otpCode) {}

    public function build()
    {
        return $this->subject('Vendo — Email Verification Code')
            ->view('emails.otp');
    }
}