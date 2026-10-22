<?php

namespace App\Services\Auth;

use App\Models\User;

/**
 * One Sanctum token = one logged-in device.
 * Logging in on a new device beyond the limit (config lms.max_devices) removes the oldest device.
 */
class DeviceSessionService
{
    public function login(User $user, string $deviceName): string
    {
        $token = $user->createToken(mb_substr($deviceName, 0, 120))->plainTextToken;

        $user->tokens()
            ->orderByDesc('id')
            ->get()
            ->slice(config('lms.max_devices'))
            ->each->delete();

        return $token;
    }
}
