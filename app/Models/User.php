<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;


/**
 * @method bool hasRole(string|array $roles)
 * @method \Spatie\Permission\Models\Role[] getRoleNames()
 */



class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'store_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    //relacion con la compañia
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    //relacion con la tienda
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function canViewLandingLeads(): bool
    {
        $ownerEmail = config('services.gestock_leads.notify_email');

        return is_string($ownerEmail)
            && $ownerEmail !== ''
            && strcasecmp((string) $this->email, $ownerEmail) === 0;
    }

}
