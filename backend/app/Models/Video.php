<?php

namespace App\Models;

use App\Enums\VideoProvider;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = ['provider', 'provider_ref', 'duration_seconds'];

    protected function casts(): array
    {
        return ['provider' => VideoProvider::class];
    }
}
