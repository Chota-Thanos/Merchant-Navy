<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Otp\OtpSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** Captures the SMS text instead of sending it. */
    private object $sms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sms = new class implements OtpSender
        {
            public array $sent = [];

            public function send(string $phone, string $message): void
            {
                $this->sent[$phone] = $message;
            }
        };
        $this->app->instance(OtpSender::class, $this->sms);
    }

    private function codeSentTo(string $phone): string
    {
        preg_match('/\b(\d{6})\b/', $this->sms->sent[$phone], $m);

        return $m[1];
    }

    private function login(string $phone, string $device = 'Test device'): array
    {
        $this->postJson('/api/v1/auth/otp/send', ['phone' => $phone])->assertOk();
        $this->travel(31)->seconds(); // allow the next resend

        return $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'code' => $this->codeSentTo($phone),
            'device_name' => $device,
        ])->assertOk()->json('data');
    }

    public function test_new_student_logs_in_and_must_complete_profile(): void
    {
        $data = $this->login('9876543210');

        $this->assertTrue($data['needsProfile']);
        $this->assertNotEmpty($data['token']);
        $this->assertDatabaseHas('users', ['phone' => '9876543210']);
    }

    public function test_wrong_code_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/otp/send', ['phone' => '9876543210'])->assertOk();

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '9876543210', 'code' => '000000'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/otp/send', ['phone' => '12345'])->assertJsonValidationErrors('phone');
    }

    public function test_profile_can_be_completed(): void
    {
        $token = $this->login('9876543210')['token'];

        $this->withToken($token)->putJson('/api/v1/me', [
            'name' => 'Aarav Sharma',
            'nickname' => 'Aarav',
            'email' => 'aarav@example.com',
            'targetExam' => 'IMU-CET',
            'attemptYear' => now()->year + 1,
        ])->assertOk()->assertJsonPath('data.nickname', 'Aarav');

        $this->assertFalse(User::firstWhere('phone', '9876543210')->needsProfile());
    }

    public function test_third_device_logs_out_the_oldest(): void
    {
        User::factory()->create(['phone' => '9876543210']);

        $first = $this->login('9876543210', 'Laptop')['token'];
        $this->login('9876543210', 'Phone');
        $third = $this->login('9876543210', 'Tablet')['token'];

        $this->assertSame(2, User::firstWhere('phone', '9876543210')->tokens()->count());
        $this->withToken($third)->getJson('/api/v1/me/devices')->assertOk()->assertJsonCount(2, 'data');

        $this->app['auth']->forgetGuards();
        $this->withToken($first)->getJson('/api/v1/me')->assertUnauthorized();
    }
}
