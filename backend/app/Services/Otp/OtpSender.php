<?php

namespace App\Services\Otp;

/**
 * Sends an OTP text message. One implementation per SMS provider.
 * The active one is chosen by OTP_DRIVER (see config/otp.php).
 */
interface OtpSender
{
    /** @param string $phone 10-digit Indian mobile number, e.g. 9876543210 */
    public function send(string $phone, string $message): void;
}
