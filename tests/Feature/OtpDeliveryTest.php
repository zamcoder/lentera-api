<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Services\Otp\WhatsAppChannel;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Pengiriman OTP (§1): email untuk pemulihan, WhatsApp untuk login HP. Tanpa SMS.
 */
class OtpDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_recover_sends_email(): void
    {
        Mail::fake();

        app(OtpService::class)->issue('user@contoh.id', 'recover');

        Mail::assertSent(OtpMail::class, fn (OtpMail $m) => $m->hasTo('user@contoh.id'));
    }

    public function test_login_otp_uses_whatsapp_not_email(): void
    {
        Mail::fake();
        $wa = $this->mock(WhatsAppChannel::class);
        $wa->shouldReceive('send')->once()
            ->with('+6281234567890', \Mockery::type('string'));

        app(OtpService::class)->issue('+6281234567890', 'login_otp');

        Mail::assertNothingSent();
    }
}
