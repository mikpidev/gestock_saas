<?php

require_once __DIR__.'/../../app/Models/store.php';
require_once __DIR__.'/../../app/Models/company.php';

use App\Http\Controllers\DTEController;
use App\Models\Company;
use App\Models\CorrelativoStore;
use App\Models\MHAccess;
use App\Models\ProductType;
use App\Models\Sale;
use App\Models\Store;
use App\Models\StoreTaxInfo;
use App\Models\TipoDte;
use App\Models\User;
use App\Services\ContingenciaService;
use App\Services\DocumentService;
use App\Services\HaciendaAuthService;
use App\Services\ReceptionService;
use App\Support\SaleDteLog;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->logFile = storage_path('logs/gestock-sale-dte-test.log');
    if (is_file($this->logFile)) {
        unlink($this->logFile);
    }

    config([
        'logging.channels.sale_dte_file.path' => $this->logFile,
        'services.hacienda.token_url_test' => 'https://mh.test',
        'services.hacienda.test_url' => 'https://mh.test/fesv/',
        'services.firma.url' => 'firmador.test',
    ]);
    Log::forgetChannel(SaleDteLog::CHANNEL);
    Log::forgetChannel('sale_dte_file');

    $this->records = [];
    Log::listen(function (MessageLogged $event) {
        $this->records[] = $event->message.' '.json_encode($event->context);
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

    $this->store = Store::create([
        'company_id' => $this->company->id,
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

    $this->product = ProductType::create([
        'company_id' => $this->company->id,
        'store_id' => $this->store->id,
        'name' => 'Producto',
        'price' => 11.30,
        'stock' => 10,
        'category' => 'General',
    ]);

    CorrelativoStore::create([
        'store_id' => $this->store->id,
        'tipo_documento_id' => $this->tipo->id,
        'correlativo' => 0,
    ]);
});

test('sale and dte debug file keeps sale_id and drops tokens payloads and pii', function () {
    $jwt = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.PAYLOADSECRET.SIGNATURESECRET';
    $apiKey = 'APIKEYSECRETO-XYZ';
    $password = 'PASSWORDPRISECRETO';
    $signedBody = 'SIGNEDBODYSECRET';
    $piiName = 'NombreSecretoPii';
    $piiEmail = 'pii-customer@example.test';
    $xml = '<?xml version="1.0"?><Respuesta><receptor>'.$piiName.'</receptor></Respuesta>';

    Http::fake([
        'https://mh.test/seguridad/auth' => Http::response([
            'body' => ['token' => $jwt],
        ], 200),
        'https://mh.test/fesv/recepciondte' => Http::response([
            'version' => 1,
            'ambiente' => '00',
            'versionApp' => 1,
            'estado' => 'PROCESADO',
            'codigoGeneracion' => 'ABCDEF01-2345-6789-ABCD-EF0123456789',
            'selloRecibido' => 'SELLOSECRETO987',
            'fhProcesamiento' => '26/09/2026 12:00:00',
            'clasificaMsg' => '10',
            'codigoMsg' => '001',
            'descripcionMsg' => 'Recibido',
            'observaciones' => [],
            'documento' => $xml,
        ], 200),
        'http://firmador.test:8113/firmardocumento/' => Http::response([
            'status' => 'OK',
            'body' => $signedBody,
        ], 200),
        '*' => Http::response(['estado' => 'PENDIENTE', 'codigoMsg' => '000', 'descripcionMsg' => 'Pendiente'], 200),
    ]);

    $created = $this->postJson(route('stores.sales.store', $this->store), [
        'sale_date' => '2026-09-26',
        'discount_amount' => 0,
        'products' => [
            ['id' => $this->product->id, 'quantity' => 1, 'price' => 11.30],
        ],
        'tipo_documento_id' => $this->tipo->id,
        'payment_method' => 'Efectivo',
    ], ['Accept' => 'application/json']);

    $created->assertOk();
    $saleId = $created->json('sale_id');
    expect($saleId)->toBeInt();

    $sale = Sale::query()->findOrFail($saleId);
    $tax = new StoreTaxInfo();
    $tax->nit = '06140000000000';
    $access = new MHAccess();
    $access->api_key = $apiKey;
    $access->password_pri = $password;
    $access->port_firma_digital = '8113';
    $this->store->setRelation('taxInfo', $tax);
    $this->store->setRelation('mh_access', $access);
    $sale->setRelation('store', $this->store);
    $sale->setRelation('tipoDte', $this->tipo);

    $document = \Mockery::mock(DocumentService::class)->makePartial();
    $document->shouldReceive('buildDTEJsonFE')->once()->andReturn([
        'identificacion' => ['tipoDte' => '01', 'numeroControl' => 'DTEJSONSECRET'],
        'receptor' => ['nombre' => $piiName, 'correo' => $piiEmail],
    ]);

    $controller = new DTEController(
        $document,
        new HaciendaAuthService(),
        new ReceptionService(),
        \Mockery::mock(ContingenciaService::class),
    );

    $response = $controller->generarDTE($sale);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true)['estado'])->toBe('PROCESADO');

    expect(is_file($this->logFile))->toBeTrue();
    $debug = file_get_contents($this->logFile);

    expect($debug)->toContain('gestock.sale')
        ->and($debug)->toContain('gestock.dte')
        ->and($debug)->toContain('"sale_id":'.$saleId)
        ->and($debug)->toContain('"mh_code":"001"')
        ->and($debug)->toContain('"token_preview":"'.SaleDteLog::tokenPreview($jwt).'"');

    $secrets = [
        $jwt,
        $apiKey,
        $password,
        $signedBody,
        $piiName,
        $piiEmail,
        'DTEJSONSECRET',
        'SELLOSECRETO987',
        $xml,
        'eyJ',
        'api_key',
        'passwordPri',
        '<?xml',
        '<Respuesta',
        'identificacion',
        '"token":',
        '"documento":',
    ];

    foreach ($secrets as $secret) {
        expect($debug)->not->toContain($secret);
    }

    $captured = implode("\n", $this->records);
    foreach ($secrets as $secret) {
        expect($captured)->not->toContain($secret);
    }

    expect($captured)->toContain('gestock.sale')
        ->and($captured)->toContain('gestock.dte')
        ->and($captured)->toContain('"sale_id":'.$saleId);
});
