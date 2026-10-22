<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A STUDENT. Logs in with phone + OTP; each login creates a Sanctum token (one per device).
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'phone',
        'name',
        'nickname',
        'email',
        'target_exam',
        'attempt_year',
        'profile_completed_at',
        'is_active',
    ];

    protected $hidden = ['remember_token'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'attempt_year' => 'integer',
            'profile_completed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot(['assigned_at', 'assigned_by'])
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function needsProfile(): bool
    {
        return $this->profile_completed_at === null;
    }

    /** Where email notifications go. */
    public function routeNotificationForMail(): ?string
    {
        return $this->email;
    }
}
