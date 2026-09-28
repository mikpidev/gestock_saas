<?php

use App\Http\Controllers\DTEController;
use App\Models\Company;
use App\Models\CorrelativoStore;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\ProductType;
use App\Models\Sale;
use App\Models\Store;
use App\Models\TipoDte;
use App\Models\User;
use App\Services\ContingenciaService;
use App\Services\DocumentService;
use App\Services\DteQuotaService;
use App\Services\HaciendaAuthService;
use App\Services\ReceptionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

require_once __DIR__.'/../../app/Models/store.php';
require_once __DIR__.'/../../app/Models/company.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();

    foreach (['superadmin', 'admin', 'user'] as $role) {
        Role::findOrCreate($role, 'web');
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

afterEach(function () {
    Carbon::setTestNow();
});

function planQuotaCompany(array $overrides = []): Company
{
    return Company::query()->create(array_merge([
        'company_name' => 'Compania '.uniqid(),
        'address' => 'Direccion 1',
        'phone' => '22223333',
        'owner' => 'Dueno',
        'email' => uniqid('co').'@example.test',
        'deployment_type' => 'saas',
        'status' => 'activa',
        'comments' => 'nota',
    ], $overrides));
}

function planQuotaStore(Company $company, array $overrides = []): Store
{
    return Store::query()->create(array_merge([
        'company_id' => $company->id,
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
        'plan' => 'basic',
        'dte_monthly_limit' => 200,
    ], $overrides));
}

function planQuotaUser(string $role, Company $company, ?Store $store = null): User
{
    $user = User::factory()->create([
        'company_id' => $company->id,
        'store_id' => $store?->id,
    ]);
    $user->assignRole($role);

    return $user;
}

function planQuotaPayload(Store $store, array $overrides = []): array
{
    return array_merge([
        'store_name' => 'Tienda editada',
        'establecimiento' => '0001',
        'punto_venta' => '0001',
        'address' => 'Direccion 2',
        'phone' => '22224444',
        'manager' => 'Gerente',
        'email' => $store->email,
        'status' => 'activa',
        'environment' => 'Development',
        'comments' => null,
    ], $overrides);
}

function planQuotaSale(Store $store, User $user, array $overrides = [], ?Carbon $createdAt = null): Sale
{
    $sale = new Sale(array_merge([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'sale_date' => '2026-09-15',
        'total_amount' => 10,
        'net_amount' => 10,
        'dte_status' => 'PROCESADO',
    ], $overrides));
    $sale->created_at = $createdAt ?? Carbon::now();
    $sale->updated_at = $sale->created_at;
    $sale->save();

    return $sale;
}

function planQuotaDteController(): DTEController
{
    return new DTEController(
        \Mockery::mock(DocumentService::class),
        \Mockery::mock(HaciendaAuthService::class),
        \Mockery::mock(ReceptionService::class),
        \Mockery::mock(ContingenciaService::class),
    );
}

test('config lists the four store plans', function () {
    expect(config('plans.free'))->toMatchArray([
        'price_usd' => 25,
        'billing_period' => 'annual_one_shot',
        'includes_iva' => false,
        'dte_monthly_limit' => 50,
        'annual_revenue_limit' => 10000,
    ])->and(config('plans.basic.price_usd'))->toBe(25)
        ->and(config('plans.basic.dte_monthly_limit'))->toBe(200)
        ->and(config('plans.basic.annual_revenue_limit'))->toBeNull()
        ->and(config('plans.premium.price_usd'))->toBe(40)
        ->and(config('plans.premium.dte_monthly_limit'))->toBe(1000)
        ->and(config('plans.empresarial.price_usd'))->toBe(75)
        ->and(config('plans.empresarial.dte_monthly_limit'))->toBeNull()
        ->and(config('plans.empresarial.annual_revenue_limit'))->toBeNull()
        ->and(Schema::hasColumn('companies', 'plan'))->toBeFalse()
        ->and(Schema::hasColumn('stores', 'plan'))->toBeTrue()
        ->and(Schema::hasColumn('stores', 'dte_monthly_limit'))->toBeTrue();

    $company = planQuotaCompany();
    $store = Store::query()->create([
        'company_id' => $company->id,
        'store_name' => 'Sin plan explicito',
        'address' => 'Direccion 2',
        'phone' => '22224444',
        'manager' => 'Gerente',
        'email' => uniqid('def').'@example.test',
        'status' => 'activa',
        'environment' => 'Development',
    ]);

    expect($store->fresh()->plan)->toBe('basic')
        ->and($store->fresh()->dte_monthly_limit)->toBeNull();
});

test('migration remaps legacy company plans onto each store and drops companies.plan', function () {
    $freeCompany = planQuotaCompany();
    $basicCompany = planQuotaCompany();
    $premiumCompany = planQuotaCompany();
    $freeStoreA = planQuotaStore($freeCompany, ['plan' => 'free', 'dte_monthly_limit' => 1]);
    $freeStoreB = planQuotaStore($freeCompany, ['plan' => 'free', 'dte_monthly_limit' => 1]);
    $basicStore = planQuotaStore($basicCompany, ['plan' => 'free', 'dte_monthly_limit' => 1]);
    $premiumStore = planQuotaStore($premiumCompany, ['plan' => 'free', 'dte_monthly_limit' => 1]);

    try {
        Schema::table('companies', function ($table) {
            $table->string('plan', 20)->nullable();
        });
        \Illuminate\Support\Facades\DB::table('companies')->where('id', $freeCompany->id)->update(['plan' => 'free']);
        \Illuminate\Support\Facades\DB::table('companies')->where('id', $basicCompany->id)->update(['plan' => 'basic']);
        \Illuminate\Support\Facades\DB::table('companies')->where('id', $premiumCompany->id)->update(['plan' => 'premium']);

        $migration = require database_path('migrations/2026_09_28_180000_move_plan_to_stores.php');
        $migration->up();

        expect($freeStoreA->fresh()->plan)->toBe('basic')
            ->and($freeStoreA->fresh()->dte_monthly_limit)->toBe(200)
            ->and($freeStoreB->fresh()->plan)->toBe('basic')
            ->and($basicStore->fresh()->plan)->toBe('premium')
            ->and($basicStore->fresh()->dte_monthly_limit)->toBe(1000)
            ->and($premiumStore->fresh()->plan)->toBe('empresarial')
            ->and($premiumStore->fresh()->dte_monthly_limit)->toBeNull()
            ->and(Schema::hasColumn('companies', 'plan'))->toBeFalse();
    } finally {
        if (Schema::hasColumn('companies', 'plan')) {
            Schema::table('companies', function ($table) {
                $table->dropColumn('plan');
            });
        }
    }
});

test('only a superadmin can create a store and the default plan is basic', function () {
    $company = planQuotaCompany();
    $other = planQuotaCompany();
    $admin = planQuotaUser('admin', $company);
    $cashier = planQuotaUser('user', $company);
    $superadmin = planQuotaUser('superadmin', $company);

    $this->actingAs($admin)
        ->post(route('store.store', $company), planQuotaPayload(new Store(['email' => uniqid('a').'@example.test'])))
        ->assertForbidden();
    $this->actingAs($cashier)
        ->post(route('store.store', $company), planQuotaPayload(new Store(['email' => uniqid('b').'@example.test'])))
        ->assertForbidden();
    $this->actingAs($superadmin)
        ->post(route('store.store', $company), planQuotaPayload(new Store(['email' => uniqid('c').'@example.test'])))
        ->assertForbidden();

    $email = uniqid('new').'@example.test';
    $this->actingAs($superadmin)
        ->withSession(['selected_company_id' => $company->id])
        ->post(route('store.store', $other), planQuotaPayload(new Store(['email' => $email])))
        ->assertForbidden();

    $this->actingAs($superadmin)
        ->withSession(['selected_company_id' => $company->id])
        ->post(route('store.store', $company), planQuotaPayload(new Store(['email' => $email]), [
            'store_name' => 'Sucursal nueva',
            'email' => $email,
            'dte_monthly_limit' => 1,
        ]))
        ->assertRedirect();

    $created = Store::query()->where('email', $email)->first();
    expect($created)->not->toBeNull()
        ->and($created->plan)->toBe('basic')
        ->and($created->dte_monthly_limit)->toBe(200);

    $freeEmail = uniqid('free').'@example.test';
    $this->actingAs($superadmin)
        ->withSession(['selected_company_id' => $company->id])
        ->post(route('store.store', $company), planQuotaPayload(new Store(['email' => $freeEmail]), [
            'email' => $freeEmail,
            'plan' => 'free',
        ]))
        ->assertRedirect();

    $freeStore = Store::query()->where('email', $freeEmail)->first();
    expect($freeStore->plan)->toBe('free')
        ->and($freeStore->dte_monthly_limit)->toBe(50);
});

test('an admin can update a store but a forged plan is ignored', function () {
    $company = planQuotaCompany();
    $store = planQuotaStore($company, ['plan' => 'premium', 'dte_monthly_limit' => 1000]);
    $admin = planQuotaUser('admin', $company);
    $superadmin = planQuotaUser('superadmin', $company);

    $this->actingAs($admin)
        ->put(route('stores.update', $store), planQuotaPayload($store, [
            'store_name' => 'Nombre admin',
            'plan' => 'free',
            'dte_monthly_limit' => 1,
        ]))
        ->assertRedirect(route('stores.index'));

    expect($store->fresh()->store_name)->toBe('Nombre admin')
        ->and($store->fresh()->plan)->toBe('premium')
        ->and($store->fresh()->dte_monthly_limit)->toBe(1000);

    $this->actingAs($superadmin)
        ->put(route('stores.update', $store), planQuotaPayload($store, [
            'plan' => 'empresarial',
            'dte_monthly_limit' => 3,
        ]))
        ->assertForbidden();

    $this->actingAs($superadmin)
        ->withSession(['selected_company_id' => $company->id])
        ->put(route('stores.update', $store), planQuotaPayload($store, [
            'store_name' => 'Nombre sa',
            'plan' => 'free',
            'dte_monthly_limit' => 3,
        ]))
        ->assertRedirect(route('stores.index'));

    expect($store->fresh()->store_name)->toBe('Nombre sa')
        ->and($store->fresh()->plan)->toBe('free')
        ->and($store->fresh()->dte_monthly_limit)->toBe(50);
});

test('company forms no longer ask for a plan and create-store UI is superadmin only', function () {
    $company = planQuotaCompany();
    $store = planQuotaStore($company, ['plan' => 'premium']);
    $admin = planQuotaUser('admin', $company);
    $superadmin = planQuotaUser('superadmin', $company);

    $payload = [
        'company_name' => 'Sin plan',
        'address' => 'Direccion 1',
        'phone' => '22223333',
        'owner' => 'Dueno',
        'email' => uniqid('nueva').'@example.test',
        'deployment_type' => 'saas',
        'status' => 'activa',
        'comments' => 'nota',
        'plan' => 'premium',
    ];

    $this->actingAs($superadmin)
        ->post(route('companies.store'), $payload)
        ->assertRedirect(route('companies.index'));

    $created = Company::query()->where('email', $payload['email'])->first();
    expect($created)->not->toBeNull()
        ->and(Schema::hasColumn('companies', 'plan'))->toBeFalse();

    $this->actingAs($admin)
        ->get(route('stores.index'))
        ->assertOk()
        ->assertDontSee('Nueva tienda', false)
        ->assertDontSee('name="plan"', false)
        ->assertSee('Premium', false);

    $this->actingAs($superadmin)
        ->withSession(['selected_company_id' => $company->id])
        ->get(route('stores.index'))
        ->assertOk()
        ->assertSee('Nueva tienda', false)
        ->assertSee('name="plan"', false);

    $this->actingAs($superadmin)
        ->get(route('companies.index'))
        ->assertOk()
        ->assertDontSee('name="plan"', false)
        ->assertDontSee('edit_plan', false);

    $company->setRelation('stores', collect([$store]));
    $this->actingAs($admin);
    expect(view('company.show', ['company' => $company])->render())->not->toContain('Agregar Tienda');

    $this->actingAs($superadmin);
    expect(view('company.show', ['company' => $company])->render())->toContain('Agregar Tienda');
});

test('monthly DTE quota counts processed sales credit notes and debit notes in the Guatemala month', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-01 02:00:00', 'UTC'));

    $company = planQuotaCompany();
    $store = planQuotaStore($company, ['plan' => 'basic', 'dte_monthly_limit' => null]);
    $other = planQuotaStore($company, ['plan' => 'basic', 'dte_monthly_limit' => 200]);
    $user = planQuotaUser('admin', $company, $store);
    $service = app(DteQuotaService::class);

    expect($service->monthlyLimit($store))->toBe(200)
        ->and($service->denial($store))->toBeNull();

    $insideA = Carbon::parse('2026-09-01 06:30:00', 'UTC');
    $insideB = Carbon::parse('2026-10-01 04:00:00', 'UTC');
    $august = Carbon::parse('2026-09-01 05:30:00', 'UTC');
    $october = Carbon::parse('2026-10-01 06:30:00', 'UTC');

    $sale = planQuotaSale($store, $user, ['dte_status' => 'PROCESADO'], $insideA);
    planQuotaSale($store, $user, ['dte_status' => 'PENDIENTE'], $insideA);
    planQuotaSale($store, $user, ['dte_status' => 'RECHAZADO'], $insideB);
    planQuotaSale($store, $user, ['dte_status' => 'PROCESADO'], $august);
    planQuotaSale($store, $user, ['dte_status' => 'PROCESADO'], $october);
    planQuotaSale($other, $user, ['dte_status' => 'PROCESADO'], $insideA);

    $credit = new CreditNote([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'sale_id' => $sale->id,
        'sale_date' => '2026-09-15',
        'total_amount' => 99999,
        'net_amount' => 99999,
        'dte_status' => 'PROCESADO',
    ]);
    $credit->created_at = $insideB;
    $credit->updated_at = $insideB;
    $credit->save();

    $debit = new DebitNote([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'sale_id' => $sale->id,
        'debit_note_date' => '2026-09-15',
        'sale_date' => '2026-09-15',
        'total_amount' => 4,
        'net_amount' => 4,
        'dte_status' => 'PROCESADO',
    ]);
    $debit->created_at = $insideA;
    $debit->updated_at = $insideA;
    $debit->save();

    expect($service->processedDteCount($store))->toBe(3);

    $sale->delete();
    expect($service->processedDteCount($store))->toBe(2);

    $store->dte_monthly_limit = 2;
    $store->save();
    $denial = $service->denial($store->fresh());
    expect($denial['error'])->toBe('dte_monthly_limit')
        ->and($denial['used'])->toBe(2)
        ->and($denial['limit'])->toBe(2);

    $store->dte_monthly_limit = 3;
    $store->save();
    expect($service->denial($store->fresh()))->toBeNull();
});

test('free annual revenue sums processed sales in the Guatemala year and ignores notes', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 18:00:00', 'UTC'));

    $company = planQuotaCompany();
    $store = planQuotaStore($company, ['plan' => 'free', 'dte_monthly_limit' => 50]);
    $user = planQuotaUser('admin', $company, $store);
    $service = app(DteQuotaService::class);

    $priorYear = Carbon::parse('2026-01-01 05:30:00', 'UTC');
    $thisYear = Carbon::parse('2026-01-01 06:30:00', 'UTC');

    planQuotaSale($store, $user, ['total_amount' => 9000, 'dte_status' => 'PROCESADO'], $thisYear);
    planQuotaSale($store, $user, ['total_amount' => 50000, 'dte_status' => 'PROCESADO'], $priorYear);
    planQuotaSale($store, $user, ['total_amount' => 5000, 'dte_status' => 'PENDIENTE'], $thisYear);
    $anchor = planQuotaSale($store, $user, ['total_amount' => 1, 'dte_status' => 'PROCESADO'], $thisYear);

    $credit = new CreditNote([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'sale_id' => $anchor->id,
        'sale_date' => '2026-02-01',
        'total_amount' => 80000,
        'net_amount' => 80000,
        'dte_status' => 'PROCESADO',
    ]);
    $credit->created_at = $thisYear;
    $credit->updated_at = $thisYear;
    $credit->save();

    expect($service->annualProcessedRevenue($store))->toBe(9001.0)
        ->and($service->denial($store))->toBeNull();

    planQuotaSale($store, $user, ['total_amount' => 999, 'dte_status' => 'PROCESADO'], $thisYear);
    $blocked = $service->denial($store->fresh());
    expect($blocked['error'])->toBe('annual_revenue_limit')
        ->and($blocked['used'])->toBe(10000.0)
        ->and($blocked['limit'])->toBe(10000.0);

    $premium = planQuotaStore($company, ['plan' => 'premium', 'dte_monthly_limit' => 1000]);
    planQuotaSale($premium, $user, ['total_amount' => 250000, 'dte_status' => 'PROCESADO'], $thisYear);
    expect($service->denial($premium->fresh()))->toBeNull();

    $empresarial = planQuotaStore($company, ['plan' => 'empresarial', 'dte_monthly_limit' => null]);
    foreach (range(1, 5) as $ignored) {
        planQuotaSale($empresarial, $user, ['dte_status' => 'PROCESADO'], $thisYear);
    }
    expect($service->monthlyLimit($empresarial))->toBeNull()
        ->and($service->denial($empresarial))->toBeNull();
});

test('generar DTE NC and ND return 422 before signing when the store is over quota', function () {
    $company = planQuotaCompany();
    $store = planQuotaStore($company, ['plan' => 'basic', 'dte_monthly_limit' => 1]);
    $user = planQuotaUser('user', $company, $store);
    $this->actingAs($user);

    $tipo = new TipoDte();
    $tipo->codigo = '01';
    $tipo->nombre = 'Factura';
    $tipo->save();

    $processed = planQuotaSale($store, $user, ['dte_status' => 'PROCESADO', 'tipo_documento_id' => $tipo->id]);
    $pending = planQuotaSale($store, $user, [
        'dte_status' => 'PENDIENTE',
        'tipo_documento_id' => $tipo->id,
        'total_amount' => 25,
    ]);

    $credit = new CreditNote([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'sale_id' => $processed->id,
        'sale_date' => '2026-09-15',
        'total_amount' => 5,
        'net_amount' => 5,
        'dte_status' => 'PENDIENTE',
    ]);
    $credit->save();

    $debit = new DebitNote([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'sale_id' => $processed->id,
        'debit_note_date' => '2026-09-15',
        'sale_date' => '2026-09-15',
        'total_amount' => 5,
        'net_amount' => 5,
        'dte_status' => 'PENDIENTE',
    ]);
    $debit->save();

    $controller = planQuotaDteController();

    $saleResponse = $controller->generarDTE($pending);
    $creditResponse = $controller->generarDTECreditNote($credit, $processed);
    $debitResponse = $controller->generarDTEDebitNote($debit, $processed);

    expect($saleResponse->getStatusCode())->toBe(422)
        ->and($saleResponse->getData(true)['error'])->toBe('dte_monthly_limit')
        ->and($creditResponse->getStatusCode())->toBe(422)
        ->and($debitResponse->getStatusCode())->toBe(422)
        ->and($pending->fresh()->dte_status)->toBe('PENDIENTE');

    $open = planQuotaStore($company, ['plan' => 'basic', 'dte_monthly_limit' => 50]);
    $allowed = planQuotaSale($open, $user, [
        'dte_status' => 'PENDIENTE',
        'tipo_documento_id' => $tipo->id,
    ]);

    $document = \Mockery::mock(DocumentService::class);
    $document->shouldReceive('buildDTEJsonFE')->once()->andThrow(new RuntimeException('past-quota'));
    $happy = new DTEController(
        $document,
        \Mockery::mock(HaciendaAuthService::class),
        \Mockery::mock(ReceptionService::class),
        \Mockery::mock(ContingenciaService::class),
    );

    $passed = $happy->generarDTE($allowed);
    expect($passed->getStatusCode())->toBe(500)
        ->and($passed->getData(true)['message'])->toBe('past-quota');
});

test('creating a sale over the monthly quota returns HTTP 422 and does not sign', function () {
    Http::fake();

    $company = planQuotaCompany();
    $store = planQuotaStore($company, [
        'plan' => 'free',
        'dte_monthly_limit' => 1,
        'establecimiento' => '0001',
        'punto_venta' => '0001',
    ]);
    $admin = planQuotaUser('admin', $company, $store);

    $tipo = new TipoDte();
    $tipo->codigo = '01';
    $tipo->nombre = 'Factura cuota';
    $tipo->save();

    CorrelativoStore::query()->create([
        'store_id' => $store->id,
        'tipo_documento_id' => $tipo->id,
        'correlativo' => 0,
    ]);

    $product = ProductType::query()->create([
        'name' => 'Producto '.uniqid(),
        'price' => 10,
        'stock' => 5,
        'category' => 'General',
        'company_id' => $company->id,
        'store_id' => $store->id,
    ]);

    planQuotaSale($store, $admin, ['dte_status' => 'PROCESADO', 'total_amount' => 20]);

    $payload = [
        'sale_date' => '2026-09-28',
        'discount_amount' => 0,
        'products' => [[
            'id' => $product->id,
            'quantity' => 1,
            'price' => 10,
        ]],
        'tipo_documento_id' => $tipo->id,
        'payment_method' => 'Efectivo',
    ];

    $blocked = $this->actingAs($admin)->postJson(route('stores.sales.store', $store), $payload);
    $blocked->assertStatus(422)
        ->assertJsonPath('error', 'dte_monthly_limit');

    expect(Sale::query()->where('store_id', $store->id)->where('dte_status', 'PENDIENTE')->count())->toBe(1);

    $open = planQuotaStore($company, [
        'plan' => 'premium',
        'dte_monthly_limit' => 1000,
        'establecimiento' => '0002',
        'punto_venta' => '0001',
    ]);
    CorrelativoStore::query()->create([
        'store_id' => $open->id,
        'tipo_documento_id' => $tipo->id,
        'correlativo' => 0,
    ]);
    $openProduct = ProductType::query()->create([
        'name' => 'Producto '.uniqid(),
        'price' => 10,
        'stock' => 5,
        'category' => 'General',
        'company_id' => $company->id,
        'store_id' => $open->id,
    ]);

    $allowed = $this->actingAs($admin)->postJson(route('stores.sales.store', $open), [
        'sale_date' => '2026-09-28',
        'discount_amount' => 0,
        'products' => [[
            'id' => $openProduct->id,
            'quantity' => 1,
            'price' => 10,
        ]],
        'tipo_documento_id' => $tipo->id,
        'payment_method' => 'Efectivo',
    ]);

    $allowed->assertOk();
    expect(Sale::query()->where('store_id', $open->id)->count())->toBe(1);
});
