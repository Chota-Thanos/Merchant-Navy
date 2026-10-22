<?php

namespace App\Services\Otp;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Creates, sends and checks login codes.
 * Codes are stored hashed, expire after `otp.ttl_minutes`, and lock after `otp.max_attempts` wrong tries.
 */
class OtpService
{
    public function __construct(private OtpSender $sender) {}

    public function send(string $phone): void
    {
        $latest = OtpCode::where('phone', $phone)->latest('id')->first();
        $wait = config('otp.resend_after_seconds');
        if ($latest && $latest->created_at->gt(now()->subSeconds($wait))) {
            throw ValidationException::withMessages(['phone' => "Please wait {$wait} seconds before requesting a new code."]);
        }

        $code = $this->generateCode();

        // Only the newest code for a number is valid.
        OtpCode::where('phone', $phone)->whereNull('used_at')->delete();
        OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(config('otp.ttl_minutes')),
        ]);

        $this->sender->send($phone, strtr(config('otp.message'), [
            ':code' => $code,
            ':minutes' => config('otp.ttl_minutes'),
        ]));
    }

    /** @throws ValidationException when the code is wrong, expired or locked */
    public function verify(string $phone, string $code): void
    {
        $otp = OtpCode::where('phone', $phone)->whereNull('used_at')->latest('id')->first();

        if (! $otp || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => 'This code has expired. Please request a new one.']);
        }
        if ($otp->attempts >= config('otp.max_attempts')) {
            throw ValidationException::withMessages(['code' => 'Too many wrong attempts. Please request a new code.']);
        }
        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            throw ValidationException::withMessages(['code' => 'Incorrect code. Please try again.']);
        }

        $otp->update(['used_at' => now()]);
    }

    private function generateCode(): string
    {
        $length = config('otp.length');

        if (config('otp.fixed_code') && ! app()->isProduction()) {
            return (string) config('otp.fixed_code');
        }

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }
}
