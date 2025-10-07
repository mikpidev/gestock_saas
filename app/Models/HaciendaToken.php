<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HaciendaToken extends Model
{
    protected $fillable = [
        'token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

}
