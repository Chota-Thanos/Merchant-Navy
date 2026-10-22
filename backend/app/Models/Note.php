<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Text notes (HTML + LaTeX). */
class Note extends Model
{
    protected $fillable = ['body'];
}
