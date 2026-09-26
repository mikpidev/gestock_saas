<?php

use App\Models\Company;
use App\Models\CorrelativoStore;
use App\Models\Store;
use App\Models\TipoDte;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();

    foreach (['superadmin', 'admin', 'user'] as $role) {
        Role::findOrCreate($role, 'web');
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function companyAttributes(array $overrides = []): array
{
    return array_merge([
        'company_name' => 'Compania '.uniqid(),
        'address' => 'Direccion 1',
        'phone' => '22223333',
        'owner' => 'Dueno',
        'email' => uniqid('co').'@example.test',
        'plan' => 'free',
        'deployment_type' => 'saas',
        'status' => 'activa',
        'comments' => 'nota',
    ], $overrides);
}

function makeCompany(array $overrides = []): Company
{
    return Company::query()->create(companyAttributes($overrides));
}

function storeAttributes(array $overrides = []): array
{
    return array_merge([
        'store_name' => 'Tienda '.uniqid(),
        'establecimiento' => '0001',
        'punto_venta' => '0001',
        'address' => 'Direccion 2',
        'phone' => '22224444',
        'manager' => 'Gerente',
        'email' => uniqid('st').'@example.test',
        'status' => 'activa',
        'environment' => 'Development',
        'comments' => null,
    ], $overrides);
}

function makeStore(Company $company, array $overrides = []): Store
{
    return Store::query()->create(array_merge(
        storeAttributes(),
        ['company_id' => $company->id],
        $overrides,
    ));
}

function makeUser(string $role, ?Company $company = null, ?Store $store = null): User
{
    $user = User::factory()->create([
        'company_id' => $company?->id,
        'store_id' => $store?->id,
    ]);

    if ($role !== '') {
        $user->assignRole($role);
    }

    return $user;
}

function makeTipoDocumento(): TipoDte
{
    static $n = 10;

    $n++;
    $tipo = new TipoDte;
    $tipo->codigo = str_pad((string) ($n % 100), 2, '0', STR_PAD_LEFT);
    $tipo->nombre = 'Factura '.$n;
    $tipo->save();

    return $tipo;
}

function correlativoPayload(CorrelativoStore $correlativo, int $value): array
{
    return [
        'correlativos' => [[
            'id' => $correlativo->id,
            'tipo_documento_id' => $correlativo->tipo_documento_id,
            'correlativo' => $value,
        ]],
    ];
}

test('non superadmins cannot create update or delete companies', function (string $role) {
    $company = makeCompany(['company_name' => 'Original']);
    $actor = makeUser($role, $company);
    $payload = companyAttributes(['company_name' => 'Cambiada', 'email' => uniqid('new').'@example.test']);

    $this->actingAs($actor)
        ->get(route('companies.create'))
        ->assertForbidden();

    $this->actingAs($actor)
        ->post(route('companies.store'), $payload)
        ->assertForbidden();

    expect(Company::query()->where('email', $payload['email'])->exists())->toBeFalse();

    $this->actingAs($actor)
        ->get(route('companies.show', $company))
        ->assertForbidden();

    $this->actingAs($actor)
        ->get(route('companies.edit', $company))
        ->assertForbidden();

    $this->actingAs($actor)
        ->put(route('companies.update', $company), $payload)
        ->assertForbidden();

    $this->actingAs($actor)
        ->delete(route('companies.destroy', $company))
        ->assertForbidden();

    $fresh = $company->fresh();
    expect($fresh)->not->toBeNull()
        ->and($fresh->company_name)->toBe('Original')
        ->and($fresh->deleted_at)->toBeNull();
})->with(['admin', 'user']);

test('admin and user cannot open another tenant company', function (string $role) {
    $own = makeCompany();
    $foreign = makeCompany(['company_name' => 'Ajena']);
    $actor = makeUser($role, $own);

    $this->actingAs($actor)->get(route('companies.show', $foreign))->assertForbidden();
    $this->actingAs($actor)
        ->put(route('companies.update', $foreign), companyAttributes(['company_name' => 'Tomada']))
        ->assertForbidden();
    $this->actingAs($actor)->delete(route('companies.destroy', $foreign))->assertForbidden();

    expect($foreign->fresh()->company_name)->toBe('Ajena')
        ->and($foreign->fresh()->deleted_at)->toBeNull();
})->with(['admin', 'user']);

test('superadmin can select and create a company', function () {
    $company = makeCompany();
    $superadmin = makeUser('superadmin', $company);
    $payload = companyAttributes(['company_name' => 'Nueva SA']);

    $this->actingAs($superadmin)
        ->get(route('companies.show', $company))
        ->assertRedirect(route('stores.index'))
        ->assertSessionHas('selected_company_id', $company->id);

    $this->actingAs($superadmin)
        ->post(route('companies.store'), $payload)
        ->assertRedirect(route('companies.index'));

    expect(Company::query()->where('email', $payload['email'])->exists())->toBeTrue();
});

test('users cannot administer stores and admins cannot administer another tenant', function () {
    $company = makeCompany();
    $other = makeCompany();
    $store = makeStore($company, ['store_name' => 'Propia']);
    $foreign = makeStore($other, ['store_name' => 'Ajena']);
    $admin = makeUser('admin', $company);
    $user = makeUser('user', $company, $store);

    $this->actingAs($user)
        ->post(route('store.store', $company), storeAttributes())
        ->assertForbidden();
    $this->actingAs($user)
        ->put(route('stores.update', $store), storeAttributes(['store_name' => 'Hack', 'email' => $store->email]))
        ->assertForbidden();
    $this->actingAs($user)
        ->delete(route('stores.destroy', $store))
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('store.store', $other), storeAttributes())
        ->assertForbidden();
    $this->actingAs($admin)
        ->get(route('stores.edit', $foreign))
        ->assertForbidden();
    $this->actingAs($admin)
        ->put(route('stores.update', $foreign), storeAttributes(['store_name' => 'Tomada', 'email' => $foreign->email]))
        ->assertForbidden();
    $this->actingAs($admin)
        ->delete(route('stores.destroy', $foreign))
        ->assertForbidden();

    expect($store->fresh()->store_name)->toBe('Propia')
        ->and($store->fresh()->deleted_at)->toBeNull()
        ->and($foreign->fresh()->store_name)->toBe('Ajena')
        ->and($foreign->fresh()->deleted_at)->toBeNull()
        ->and(Store::query()->count())->toBe(2);
});

test('admin can update a store in their own company', function () {
    $company = makeCompany();
    $store = makeStore($company, ['store_name' => 'Propia']);
    $admin = makeUser('admin', $company);

    $this->actingAs($admin)
        ->put(route('stores.update', $store), storeAttributes([
            'store_name' => 'Renombrada',
            'email' => $store->email,
        ]))
        ->assertRedirect(route('stores.index'));

    expect($store->fresh()->store_name)->toBe('Renombrada');
});

test('superadmin store administration follows the selected company', function () {
    $selected = makeCompany();
    $other = makeCompany();
    $store = makeStore($selected, ['store_name' => 'Seleccionada']);
    $foreign = makeStore($other, ['store_name' => 'Otra']);
    $superadmin = makeUser('superadmin', $selected);

    $this->actingAs($superadmin)
        ->put(route('stores.update', $store), storeAttributes([
            'store_name' => 'Sin sesion',
            'email' => $store->email,
        ]))
        ->assertForbidden();

    $this->actingAs($superadmin)
        ->withSession(['selected_company_id' => $selected->id])
        ->put(route('stores.update', $foreign), storeAttributes([
            'store_name' => 'Cruzada',
            'email' => $foreign->email,
        ]))
        ->assertForbidden();

    $this->actingAs($superadmin)
        ->withSession(['selected_company_id' => $selected->id])
        ->post(route('store.store', $other), storeAttributes())
        ->assertForbidden();

    $this->actingAs($superadmin)
        ->withSession(['selected_company_id' => $selected->id])
        ->put(route('stores.update', $store), storeAttributes([
            'store_name' => 'Actualizada',
            'email' => $store->email,
        ]))
        ->assertRedirect(route('stores.index'));

    expect($store->fresh()->store_name)->toBe('Actualizada')
        ->and($foreign->fresh()->store_name)->toBe('Otra')
        ->and(Store::query()->where('company_id', $other->id)->count())->toBe(1);
});

test('correlativos require store admin on the same tenant', function () {
    $company = makeCompany();
    $other = makeCompany();
    $store = makeStore($company);
    $foreign = makeStore($other);
    $tipo = makeTipoDocumento();
    $ownCorrelativo = CorrelativoStore::query()->create([
        'store_id' => $store->id,
        'tipo_documento_id' => $tipo->id,
        'correlativo' => 4,
    ]);
    $foreignTipo = makeTipoDocumento();
    $foreignCorrelativo = CorrelativoStore::query()->create([
        'store_id' => $foreign->id,
        'tipo_documento_id' => $foreignTipo->id,
        'correlativo' => 4,
    ]);
    $admin = makeUser('admin', $company);
    $user = makeUser('user', $company, $store);
    $superadmin = makeUser('superadmin', $company);

    $this->actingAs($user)->get(route('correlativos.edit', $store))->assertForbidden();
    $this->actingAs($user)
        ->put(route('correlativos.update', $store), correlativoPayload($ownCorrelativo, 20))
        ->assertForbidden();

    $this->actingAs($admin)->get(route('correlativos.edit', $foreign))->assertForbidden();
    $this->actingAs($admin)
        ->put(route('correlativos.update', $foreign), correlativoPayload($foreignCorrelativo, 20))
        ->assertForbidden();

    $this->actingAs($superadmin)
        ->withSession(['selected_company_id' => $other->id])
        ->put(route('correlativos.update', $store), correlativoPayload($ownCorrelativo, 20))
        ->assertForbidden();

    expect($ownCorrelativo->fresh()->correlativo)->toBe(4)
        ->and($foreignCorrelativo->fresh()->correlativo)->toBe(4);

    $this->actingAs($admin)
        ->put(route('correlativos.update', $store), correlativoPayload($ownCorrelativo, 9))
        ->assertRedirect(route('correlativos.edit', $store));

    expect($ownCorrelativo->fresh()->correlativo)->toBe(9);
});

test('sales index forbids the wrong role and the wrong tenant', function () {
    $company = makeCompany();
    $other = makeCompany();
    $store = makeStore($company);
    $foreign = makeStore($other);
    $admin = makeUser('admin', $company);
    $user = makeUser('user', $company, $store);
    $stranger = makeUser('', $company, $store);
    $superadmin = makeUser('superadmin', $company);

    $this->actingAs($admin)->get(route('stores.sales.index', $foreign))->assertForbidden();
    $this->actingAs($user)->get(route('stores.sales.index', $foreign))->assertForbidden();
    $this->actingAs($stranger)->get(route('stores.sales.index', $store))->assertForbidden();
    $this->actingAs($superadmin)
        ->withSession(['selected_company_id' => $other->id])
        ->get(route('stores.sales.index', $store))
        ->assertForbidden();

    $this->actingAs($user)->get(route('stores.sales.index', $store))->assertOk();
    $this->actingAs($admin)->get(route('stores.sales.index', $store))->assertOk();
    $this->actingAs($superadmin)
        ->withSession(['selected_company_id' => $company->id])
        ->get(route('stores.sales.index', $store))
        ->assertOk();
});

test('store dashboard and dashboard data forbid the wrong role and the wrong tenant', function () {
    $company = makeCompany();
    $other = makeCompany();
    $store = makeStore($company);
    $foreign = makeStore($other);
    $admin = makeUser('admin', $company);
    $user = makeUser('user', $company, $store);
    $stranger = makeUser('', $company, $store);
    $superadmin = makeUser('superadmin', $company);

    foreach (['stores.dashboard', 'stores.dashboard.data', 'stores.dashboard.download-csv'] as $routeName) {
        $this->actingAs($admin)->get(route($routeName, $foreign))->assertForbidden();
        $this->actingAs($user)->get(route($routeName, $foreign))->assertForbidden();
        $this->actingAs($stranger)->get(route($routeName, $store))->assertForbidden();
        $this->actingAs($superadmin)
            ->withSession(['selected_company_id' => $other->id])
            ->get(route($routeName, $store))
            ->assertForbidden();
        $this->actingAs($superadmin)
            ->get(route($routeName, $store))
            ->assertForbidden();
    }

    $this->actingAs($admin)->get(route('stores.dashboard', $store))->assertOk();
    $this->actingAs($user)->get(route('stores.dashboard', $store))->assertOk();
    $this->actingAs($superadmin)
        ->withSession(['selected_company_id' => $company->id])
        ->get(route('stores.dashboard', $store))
        ->assertOk();
});
