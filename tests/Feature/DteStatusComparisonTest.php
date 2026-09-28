<?php

use App\Http\Controllers\DTEController;
use App\Http\Controllers\OCIController;
use App\Models\Company;
use App\Models\CorrelativoStore;
use App\Models\ProductType;
use App\Models\Sale;
use App\Models\Store;
use App\Models\TipoDte;
use App\Models\User;
use App\Services\ConsultaService;
use App\Services\HaciendaAuthService;
use Spatie\Permission\Models\Role;

// company.php and store.php do not match PSR-4 on case-sensitive filesystems.
if (!class_exists(\App\Models\Company::class)) {
    require_once __DIR__.'/../../app/Models/company.php';
}
if (!class_exists(\App\Models\Store::class)) {
    require_once __DIR__.'/../../app/Models/store.php';
}

/**
 * Hacienda statuses persisted on sales.dte_status (string, default PENDIENTE).
 * PROCESADO is the only status that may trigger the DTE email.
 */
function dteStatusFixture(): array
{
    Role::findOrCreate('user', 'web');

    $company = Company::query()->create([
        'company_name' => 'Empresa DTE',
        'address' => 'San Salvador',
        'phone' => '22223333',
        'owner' => 'Owner',
        'email' => 'company-dte@example.com',
        'plan' => 'basic',
        'deployment_type' => 'saas',
        'status' => 'activa',
        'comments' => '',
    ]);

    $store = Store::query()->create([
        'company_id' => $company->id,
        'store_name' => 'Sucursal DTE',
        'establecimiento' => '0001',
        'punto_venta' => '0001',
        'address' => 'San Salvador',
        'phone' => '22224444',
        'manager' => 'Manager',
        'email' => 'store-dte@example.com',
        'status' => 'activa',
        'environment' => 'Development',
        'comments' => '',
    ]);

    $user = User::factory()->create([
        'company_id' => $company->id,
        'store_id' => $store->id,
    ]);
    $user->assignRole('user');

    $tipo = new TipoDte();
    $tipo->codigo = '01';
    $tipo->nombre = 'Factura';
    $tipo->save();

    CorrelativoStore::query()->create([
        'store_id' => $store->id,
        'tipo_documento_id' => $tipo->id,
        'correlativo' => 0,
    ]);

    $product = ProductType::query()->create([
        'name' => 'Producto DTE',
        'price' => 11.30,
        'stock' => 10,
        'category' => 'General',
        'company_id' => $company->id,
        'store_id' => $store->id,
    ]);

    return compact('company', 'store', 'user', 'tipo', 'product');
}

it('keeps the Hacienda status on sale create and emails only when PROCESADO', function (string $estado, bool $sendsEmail) {
    $fx = dteStatusFixture();

    $this->mock(DTEController::class, function ($mock) {
        $mock->shouldReceive('generarDTE')->once();
    });
    $this->mock(HaciendaAuthService::class, function ($mock) {
        $mock->shouldReceive('getToken')->once()->andReturn('test-token');
    });
    $this->mock(ConsultaService::class, function ($mock) use ($estado) {
        $mock->shouldReceive('consultarSale')->once()->andReturn([
            'estado' => $estado,
        ]);
    });
    $this->mock(OCIController::class, function ($mock) use ($sendsEmail) {
        $mock->shouldReceive('emailSend')->times($sendsEmail ? 1 : 0);
    });

    $response = $this->actingAs($fx['user'])->postJson(
        route('stores.sales.store', $fx['store']),
        [
            'sale_date' => now()->toDateString(),
            'discount_amount' => 0,
            'products' => [[
                'id' => $fx['product']->id,
                'quantity' => 1,
                'price' => 11.30,
            ]],
            'tipo_documento_id' => $fx['tipo']->id,
            'payment_method' => 'Efectivo',
        ],
        ['X-Requested-With' => 'XMLHttpRequest']
    );

    $response->assertOk();
    $response->assertJsonPath('dte_status', $estado);

    $sale = Sale::query()->where('store_id', $fx['store']->id)->first();

    expect($sale)->not->toBeNull()
        ->and($sale->dte_status)->toBe($estado);
})->with([
    'PROCESADO' => ['PROCESADO', true],
    'PENDIENTE' => ['PENDIENTE', false],
    'RECHAZADO' => ['RECHAZADO', false],
]);

it('keeps the Hacienda status on DTE refresh and emails only when PROCESADO', function (string $estado, bool $sendsEmail) {
    $fx = dteStatusFixture();

    $sale = Sale::query()->create([
        'store_id' => $fx['store']->id,
        'user_id' => $fx['user']->id,
        'tipo_documento_id' => $fx['tipo']->id,
        'sale_date' => now()->toDateString(),
        'total_amount' => 11.30,
        'net_amount' => 11.30,
        'dte_status' => 'PENDIENTE',
        'codigo_generacion' => 'TEST-CODIGO-GENERACION',
        'numero_control' => 'DTE-01-00010001-000000000000001',
    ]);

    $this->mock(DTEController::class, function ($mock) {
        $mock->shouldReceive('generarDTE')->once();
    });
    $this->mock(HaciendaAuthService::class, function ($mock) {
        $mock->shouldReceive('getToken')->once()->andReturn('test-token');
    });
    $this->mock(ConsultaService::class, function ($mock) use ($estado) {
        $mock->shouldReceive('consultarSale')->once()->andReturn([
            'estado' => $estado,
        ]);
    });
    $this->mock(OCIController::class, function ($mock) use ($sendsEmail) {
        $mock->shouldReceive('emailSend')->times($sendsEmail ? 1 : 0);
    });

    $response = $this->actingAs($fx['user'])
        ->from(route('stores.sales.index', $fx['store']))
        ->post(route('stores.sales.refreshDTE', [$fx['store'], $sale]));

    $response->assertRedirect(route('stores.sales.index', $fx['store']));
    $response->assertSessionHas('success');

    expect($sale->fresh()->dte_status)->toBe($estado);
})->with([
    'PROCESADO' => ['PROCESADO', true],
    'PENDIENTE' => ['PENDIENTE', false],
    'RECHAZADO' => ['RECHAZADO', false],
]);
