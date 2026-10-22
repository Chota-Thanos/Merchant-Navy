<?php

namespace App\Providers;

use App\Models;
use App\Services\Otp\OtpSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // SMS provider for OTP, chosen by OTP_DRIVER (config/otp.php).
        $this->app->bind(OtpSender::class, fn ($app) => $app->make(config('otp.drivers')[config('otp.driver')]));
    }

    public function boot(): void
    {
        // Logged-in API calls: generous (a student saves an answer on every click) but not unlimited. Per student.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(300)->by($request->user()?->id ?: $request->ip()));

        // OTP requests: limited per phone number + IP, so many students on one Wi-Fi aren't blocked together.
        RateLimiter::for('otp', fn (Request $request) => Limit::perMinute(5)->by($request->input('phone').'|'.$request->ip()));

        // Short names stored in polymorphic `*_type` columns instead of PHP class names.
        Relation::enforceMorphMap([
            'user' => Models\User::class,
            'admin' => Models\Admin::class,
            'subject' => Models\Subject::class,
            'test' => Models\Test::class,
            'pdf' => Models\PdfDocument::class,
            'video' => Models\Video::class,
            'live_class' => Models\LiveClass::class,
            'note' => Models\Note::class,
            'assignment' => Models\Assignment::class,
        ]);
    }
}
