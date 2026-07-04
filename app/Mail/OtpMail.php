<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * OtpMail — email berisi kode OTP pemulihan (§1).
 */
class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public int $ttlMinutes = 5)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kode Lentera: '.$this->code);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp');
    }
}
