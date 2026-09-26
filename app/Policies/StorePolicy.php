<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StorePolicy
{
    /**
     * Read a store (dashboard, dashboard-data). Mirrors SaleController::validateStoreAccess:
     * superadmin on the selected company, admin or user on their own company.
     */
    public function view(User $user, Store $store): Response
    {
        if ($user->hasRole('superadmin')) {
            return $this->selectedCompany($store->company_id);
        }

        if ($user->hasRole('admin') || $user->hasRole('user')) {
            return $this->ownCompany($user, $store->company_id);
        }

        return Response::deny('Acceso no autorizado.');
    }

    public function create(User $user, Company $company): Response
    {
        return $this->administersCompany($user, $company->id);
    }

    public function update(User $user, Store $store): Response
    {
        return $this->administersCompany($user, $store->company_id);
    }

    public function delete(User $user, Store $store): Response
    {
        return $this->administersCompany($user, $store->company_id);
    }

    public function manageCorrelativos(User $user, Store $store): Response
    {
        return $this->administersCompany($user, $store->company_id);
    }

    /**
     * Store administration: superadmin on the selected company, admin on their own company.
     * The user role cannot administer stores.
     */
    private function administersCompany(User $user, mixed $companyId): Response
    {
        if ($user->hasRole('superadmin')) {
            return $this->selectedCompany($companyId);
        }

        if ($user->hasRole('admin')) {
            return $this->ownCompany($user, $companyId);
        }

        return Response::deny('Acceso no autorizado.');
    }

    private function selectedCompany(mixed $companyId): Response
    {
        return $this->sameCompany(session('selected_company_id'), $companyId)
            ? Response::allow()
            : Response::deny('Acceso no autorizado.');
    }

    private function ownCompany(User $user, mixed $companyId): Response
    {
        return $this->sameCompany($user->company_id, $companyId)
            ? Response::allow()
            : Response::deny('Acceso no autorizado.');
    }

    private function sameCompany(mixed $left, mixed $right): bool
    {
        if ($left === null || $left === '' || $right === null || $right === '') {
            return false;
        }

        return (int) $left === (int) $right;
    }
}
