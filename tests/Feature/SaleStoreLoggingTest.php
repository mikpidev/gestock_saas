<?php

require_once __DIR__.'/../../app/Models/store.php';
require_once __DIR__.'/../../app/Models/company.php';

use App\Http\Controllers\DTEController;
use App\Models\Company;
use App\Models\CorrelativoStore;
use App\Models\ProductType;
use App\Models\Sale;
use App\Models\Store;
use App\Models\TipoDte;
use App\Models\User;
use App\Services\ContingenciaService;
use App\Services\DocumentService;
use App\Services\HaciendaAuthService;
use App\Services\ReceptionService;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Http::fake();

    $this->records = [];
    Log::listen(function (MessageLogged $event) {
        $this->records[] = [
            'message' => $event->message,
            'context' => $event->context,
        ];
    });

    $this->company = Company::create([
        'company_name' => 'Gestock Test',
        'address' => 'San Salvador',
        'phone' => '22223333',
        'owner' => 'Owner',
        'email' => 'company-'.uniqid().'@example.com',
        'deployment_type' => 'saas',
        'status' => 'activa',
        'comments' => 'test',
    ]);

    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);

    Role::findOrCreate('admin', 'web');
    $this->user->assignRole('admin');
    $this->actingAs($this->user);

    $tipo = new TipoDte();
    $tipo->codigo = '01';
    $tipo->nombre = 'Factura';
    $tipo->save();
    $this->tipo = $tipo;

    [$this->store, $this->product] = saleLogStore($this->company, $this->tipo);
});

function saleLogStore(Company $company, TipoDte $tipo): array
{
    $store = Store::create([
        'company_id' => $company->id,
        'store_name' => 'Tienda log',
        'establecimiento' => 'M001',
        'punto_venta' => 'P001',
        'address' => 'Calle 1',
        'phone' => '77778888',
        'manager' => 'Manager',
        'email' => 'tienda-'.uniqid().'@example.com',
        'status' => 'activa',
        'environment' => 'Development',
    ]);

    $product = ProductType::create([
        'company_id' => $company->id,
        'store_id' => $store->id,
        'name' => 'Producto '.$store->id,
        'price' => 11.30,
        'stock' => 10,
        'category' => 'General',
    ]);

    CorrelativoStore::create([
        'store_id' => $store->id,
        'tipo_documento_id' => $tipo->id,
        'correlativo' => 0,
    ]);

    return [$store, $product];
}

function saleLogPayload(int $productId, int $tipoId): array
{
    return [
        'sale_date' => '2026-09-26',
        'discount_amount' => 0,
        'products' => [
            ['id' => $productId, 'quantity' => 1, 'price' => 11.30],
        ],
        'tipo_documento_id' => $tipoId,
        'payment_method' => 'Efectivo',
    ];
}

function postLoggedSale($testCase, Store $store, array $payload, ?string $idempotencyKey = null)
{
    $headers = ['Accept' => 'application/json'];

    if ($idempotencyKey !== null) {
        $headers['Idempotency-Key'] = $idempotencyKey;
    }

    return $testCase->postJson(route('stores.sales.store', $store), $payload, $headers);
}

function logsNamed(array $records, string $message): array
{
    return array_values(array_filter(
        $records,
        fn (array $record) => $record['message'] === $message
    ));
}

test('creating a sale logs outcome created and the same key logs replay', function () {
    $payload = saleLogPayload($this->product->id, $this->tipo->id);
    $key = 'sale-key-same';

    $first = postLoggedSale($this, $this->store, $payload, $key);
    $second = postLoggedSale($this, $this->store, $payload, '  '.$key.'  ');

    $first->assertOk();
    $second->assertOk();

    expect($second->json('sale_id'))->toBe($first->json('sale_id'))
        ->and(Sale::query()->count())->toBe(1);

    $saleLogs = logsNamed($this->records, 'gestock.sale');
    expect($saleLogs)->toHaveCount(2);

    $created = $saleLogs[0]['context'];
    $replay = $saleLogs[1]['context'];

    expect($created)->toBe([
        'outcome' => 'created',
        'user_id' => $this->user->id,
        'store_id' => $this->store->id,
        'sale_id' => $first->json('sale_id'),
        'idempotency_key' => $key,
        'correlativo' => 1,
        'status_http' => 200,
    ]);

    expect($replay)->toBe([
        'outcome' => 'replay',
        'user_id' => $this->user->id,
        'store_id' => $this->store->id,
        'sale_id' => $first->json('sale_id'),
        'idempotency_key' => $key,
        'correlativo' => 1,
        'status_http' => 200,
    ]);

    $dteLogs = logsNamed($this->records, 'gestock.dte');
    expect($dteLogs)->toHaveCount(1);

    $dte = $dteLogs[0]['context'];
    expect($dte['sale_id'])->toBe($first->json('sale_id'))
        ->and($dte['store_id'])->toBe($this->store->id)
        ->and($dte['user_id'])->toBe($this->user->id)
        ->and($dte['dte_type'])->toBe('01')
        ->and($dte['dte_status_before'])->toBe('PENDIENTE')
        ->and($dte['dte_status_after'])->toBe('PENDIENTE')
        ->and($dte['mh_code'])->toBeNull()
        ->and($dte['mh_message'])->toBeString()
        ->and($dte['duration_ms'])->toBeInt();

    expect(array_keys($dte))->toBe([
        'sale_id',
        'store_id',
        'user_id',
        'dte_type',
        'dte_status_before',
        'dte_status_after',
        'mh_code',
        'mh_message',
        'duration_ms',
    ]);
});

test('a long idempotency key is logged as a hash', function () {
    $key = str_repeat('k', 70);

    $response = postLoggedSale($this, $this->store, saleLogPayload($this->product->id, $this->tipo->id), $key);

    $response->assertOk();

    $saleLogs = logsNamed($this->records, 'gestock.sale');
    expect($saleLogs)->toHaveCount(1)
        ->and($saleLogs[0]['context']['outcome'])->toBe('created')
        ->and($saleLogs[0]['context']['idempotency_key'])->toBe(hash('sha256', $key))
        ->and($saleLogs[0]['context']['idempotency_key'])->not->toBe($key);
});

test('generarDTE logs gestock.dte with the hacienda status and not the payload', function () {
    $sale = Sale::create([
        'store_id' => $this->store->id,
        'user_id' => $this->user->id,
        'sale_date' => '2026-09-26',
        'total_amount' => 11.30,
        'net_amount' => 11.30,
        'dte_status' => 'PENDIENTE',
        'tipo_documento_id' => $this->tipo->id,
        'payment_method' => 'Efectivo',
        'numero_control' => 'DTE-01-M001P001-000000000000001',
        'codigo_generacion' => 'ABCDEF01-2345-6789-ABCD-EF0123456789',
    ]);

    $tax = new \App\Models\StoreTaxInfo();
    $tax->forceFill(['nit' => '06140000000000']);
    $access = new \App\Models\MHAccess();
    $access->forceFill([
        'api_key' => 'api-key',
        'password_pri' => 'pri',
        'port_firma_digital' => '8113',
    ]);
    $this->store->setRelation('taxInfo', $tax);
    $this->store->setRelation('mh_access', $access);
    $sale->setRelation('store', $this->store);
    $sale->setRelation('tipoDte', $this->tipo);

    $document = \Mockery::mock(DocumentService::class);
    $document->shouldReceive('buildDTEJsonFE')->once()->andReturn(['identificacion' => ['tipoDte' => '01']]);
    $document->shouldReceive('signDocument')->once()->andReturn(['body' => 'signed-body']);

    $auth = \Mockery::mock(HaciendaAuthService::class);
    $auth->shouldReceive('generateNewToken')->once()->andReturn('mh-token');

    $reception = \Mockery::mock(ReceptionService::class);
    $reception->shouldReceive('sendToHacienda')->once()->andReturn([
        'estado' => 'PROCESADO',
        'codigoMsg' => '001',
        'descripcionMsg' => 'Recibido',
        'documento' => '{"identificacion":{"numeroControl":"secret"}}',
    ]);

    $controller = new DTEController(
        $document,
        $auth,
        $reception,
        \Mockery::mock(ContingenciaService::class),
    );

    $this->records = [];
    $response = $controller->generarDTE($sale);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true)['estado'])->toBe('PROCESADO');

    $dteLogs = logsNamed($this->records, 'gestock.dte');
    expect($dteLogs)->toHaveCount(1);

    $context = $dteLogs[0]['context'];
    expect($context['sale_id'])->toBe($sale->id)
        ->and($context['store_id'])->toBe($this->store->id)
        ->and($context['user_id'])->toBe($this->user->id)
        ->and($context['dte_type'])->toBe('01')
        ->and($context['dte_status_before'])->toBe('PENDIENTE')
        ->and($context['dte_status_after'])->toBe('PROCESADO')
        ->and($context['mh_code'])->toBe('001')
        ->and($context['mh_message'])->toBe('Recibido');

    $encoded = json_encode($context);
    expect($encoded)->not->toContain('signed-body')
        ->and($encoded)->not->toContain('mh-token')
        ->and($encoded)->not->toContain('secret')
        ->and($encoded)->not->toContain('identificacion');
});
