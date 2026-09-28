<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CompanyPolicy
{
    public function view(User $user, Company $company): Response
    {
        return $this->superadminOnly($user);
    }

    public function create(User $user): Response
    {
        return $this->superadminOnly($user);
    }

    public function update(User $user, Company $company): Response
    {
        return $this->superadminOnly($user);
    }

    public function delete(User $user, Company $company): Response
    {
        return $this->superadminOnly($user);
    }

    private function superadminOnly(User $user): Response
    {
        return $user->hasRole('superadmin')
            ? Response::allow()
            : Response::deny('Acceso no autorizado.');
    }
}
