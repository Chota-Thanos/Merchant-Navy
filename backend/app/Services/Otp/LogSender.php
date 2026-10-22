<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Log;

/** Local development: the SMS text (with the code) is written to storage/logs/laravel.log. */
class LogSender implements OtpSender
{
    public function send(string $phone, string $message): void
    {
        Log::info("[OTP] to {$phone}: {$message}");
    }
}
