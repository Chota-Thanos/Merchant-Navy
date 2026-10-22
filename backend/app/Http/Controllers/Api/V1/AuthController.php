<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\Auth\DeviceSessionService;
use App\Services\Otp\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Mobile number + OTP login. */
class AuthController extends Controller
{
    public function __construct(private OtpService $otp, private DeviceSessionService $devices) {}

    /** POST /api/v1/auth/otp/send */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $this->otp->send($request->validated('phone'));

        return response()->json(['data' => null, 'message' => 'OTP sent']);
    }

    /** POST /api/v1/auth/otp/verify — creates the student on first login. */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->validated('phone');
        $this->otp->verify($phone, $request->validated('code'));

        $user = User::firstOrCreate(['phone' => $phone]);
        abort_unless($user->is_active, 403, 'Your account is disabled. Please contact support.');

        $token = $this->devices->login($user, $request->validated('device_name') ?? 'Unknown device');

        return response()->json(['data' => [
            'token' => $token,
            'needsProfile' => $user->needsProfile(),
        ]]);
    }

    /** POST /api/v1/auth/logout — logs out this device only. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['data' => null]);
    }
}
