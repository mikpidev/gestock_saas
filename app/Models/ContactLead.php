<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactLead extends Model
{
    protected $fillable = [
        'name',
        'business_name',
        'email',
        'phone',
        'message',
        'ip_address',
    ];
}
