<?php

use App\Http\Controllers\OCIController;
use App\Models\Company;
use App\Models\CorrelativoStore;
use App\Models\ProductType;
use App\Models\Sale;
use App\Models\Store;
use App\Models\TipoDte;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\HaciendaAuthService;
use App\Support\MailLog;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->mailFile = storage_path('logs/gestock-mail-test.log');
    $this->saleFile = storage_path('logs/gestock-sale-dte-mail-test.log');
    $this->defaultFile = storage_path('logs/gestock-default-mail-test.log');

    foreach ([$this->mailFile, $this->saleFile, $this->defaultFile] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }

    config([
        'logging.channels.gestock_mail_file.path' => $this->mailFile,
        'logging.channels.sale_dte_file.path' => $this->saleFile,
        'logging.channels.single.path' => $this->defaultFile,
    ]);

    foreach (['gestock_mail', 'gestock_mail_file', 'sale_dte', 'sale_dte_file', 'single', 'stack'] as $channel) {
        Log::forgetChannel($channel);
    }

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

test('mail channel is not on the default stack or the sale dte stack', function () {
    expect(config('logging.channels.gestock_mail.channels'))->toBe(['gestock_mail_file'])
        ->and(config('logging.channels.gestock_mail_file.path'))->toEndWith('gestock-mail-test.log')
        ->and(config('logging.channels.sale_dte.channels'))->toBe(['sale_dte_file'])
        ->and(config('logging.channels.stack.channels'))->not->toContain('gestock_mail')
        ->and(config('logging.channels.stack.channels'))->not->toContain('gestock_mail_file')
        ->and(config('logging.channels.sale_dte.channels'))->not->toContain('gestock_mail')
        ->and(config('logging.default'))->not->toBe('gestock_mail');

    Log::channel('stack')->info('default-stack-line');
    Log::channel('sale_dte')->info('gestock.sale', ['sale_id' => 9, 'outcome' => 'created']);
    MailLog::attempt([
        'sale_id' => 9,
        'dte_status' => 'PROCESADO',
        'numero_control' => 'DTE-01-M001P001-000000000000015',
        'resultado' => 'sent',
        'http_status' => 200,
    ]);

    $mail = file_get_contents($this->mailFile);
    $sale = file_get_contents($this->saleFile);
    $default = file_get_contents($this->defaultFile);

    expect($mail)->toContain('gestock.mail')
        ->and($mail)->toContain('"sale_id":9')
        ->and($mail)->toContain('"dte_status":"PROCESADO"')
        ->and($mail)->toContain('"correlativo":15')
        ->and($mail)->toContain('"resultado":"sent"')
        ->and($mail)->toContain('"http_status":200')
        ->and($mail)->not->toContain('gestock.sale')
        ->and($mail)->not->toContain('default-stack-line');

    expect($sale)->toContain('gestock.sale')
        ->and($sale)->not->toContain('gestock.mail');

    expect($default)->toContain('default-stack-line')
        ->and($default)->not->toContain('gestock.mail')
        ->and($default)->not->toContain('gestock.sale');
});

test('email send writes one metadata line and drops addresses xml and tokens', function () {
    $piiEmail = 'pii-customer@example.test';
    $fromEmail = 'from-secret@example.test';
    $piiName = 'NombreSecretoPii';
    $jsonSecret = 'DTEJSONSECRET';
    $signed = 'SIGNEDBODYSECRET';

    $customerId = insertMailChannelCustomer($this->store->id, $piiEmail, $piiName);
    $sale = insertMailChannelSale($this->store->id, $this->user->id, $this->tipo->id, $customerId);

    $document = \Mockery::mock(DocumentService::class);
    $document->shouldReceive('buildDTEJsonFE')->once()->andReturn([
        'identificacion' => [
            'codigoGeneracion' => $sale->codigo_generacion,
            'fecEmi' => '2026-09-26',
            'numeroControl' => $jsonSecret,
        ],
        'emisor' => ['nombre' => 'Emisor'],
        'receptor' => ['nombre' => $piiName, 'correo' => $piiEmail],
        'resumen' => ['totalPagar' => 11.30],
    ]);
    $this->app->instance(DocumentService::class, $document);

    config([
        'services.oci_smtp.from_email' => $fromEmail,
        'services.oci_smtp.from_name' => 'Gestock',
    ]);

    $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
    $pdf->shouldReceive('loadView')->once()->andReturnSelf();
    $pdf->shouldReceive('output')->once()->andReturn($signed);
    $this->app->instance('dompdf.wrapper', $pdf);
    Mail::fake();

    $response = app(OCIController::class)->emailSend($this->store, $sale);

    expect($response->getStatusCode())->toBe(200);

    $mail = file_get_contents($this->mailFile);
    expect(substr_count($mail, 'gestock.mail'))->toBe(1)
        ->and($mail)->toContain('"sale_id":'.$sale->id)
        ->and($mail)->toContain('"dte_status":"PROCESADO"')
        ->and($mail)->toContain('"correlativo":15')
        ->and($mail)->toContain('"resultado":"sent"')
        ->and($mail)->toContain('"http_status":200');

    assertMailSecretsAbsent($mail, [$piiEmail, $fromEmail, $piiName, $jsonSecret, $signed, 'eyJ', '<?xml', 'identificacion', '"documento":', '"token":']);
    assertMailSecretsAbsent(mailChannelCaptured($this->records), [$piiEmail, $fromEmail, $piiName, $jsonSecret, $signed]);

    $saleLog = is_file($this->saleFile) ? file_get_contents($this->saleFile) : '';
    expect($saleLog)->not->toContain('gestock.mail')
        ->and($saleLog)->not->toContain($piiEmail)
        ->and($saleLog)->not->toContain($fromEmail);

    $default = is_file($this->defaultFile) ? file_get_contents($this->defaultFile) : '';
    expect($default)->not->toContain('gestock.mail')
        ->and($default)->not->toContain($piiEmail)
        ->and($default)->not->toContain($fromEmail);
});

test('skipped mail attempt logs sale metadata without the customer address', function () {
    $piiName = 'NombreSecretoPii';
    $customerId = insertMailChannelCustomer($this->store->id, null, $piiName);
    $sale = insertMailChannelSale($this->store->id, $this->user->id, $this->tipo->id, $customerId);

    $response = app(OCIController::class)->emailSend($this->store, $sale);

    expect($response->getStatusCode())->toBe(422);

    $mail = file_get_contents($this->mailFile);
    expect(substr_count($mail, 'gestock.mail'))->toBe(1)
        ->and($mail)->toContain('"resultado":"skipped"')
        ->and($mail)->toContain('"sale_id":'.$sale->id)
        ->and($mail)->toContain('"dte_status":"PROCESADO"')
        ->and($mail)->toContain('"correlativo":15')
        ->and($mail)->toContain('"http_status":422')
        ->and($mail)->not->toContain($piiName)
        ->and($mail)->not->toContain('@');

    $saleLog = is_file($this->saleFile) ? file_get_contents($this->saleFile) : '';
    expect($saleLog)->not->toContain('gestock.mail');
});

test('oci mail failure keeps from and to out of every channel', function () {
    $piiEmail = 'pii-customer@example.test';
    $fromEmail = 'from-secret@example.test';
    $piiName = 'NombreSecretoPii';
    $jwt = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.PAYLOADSECRET.SIGNATURESECRET';
    $xml = '<?xml version="1.0"?><Respuesta>NombreSecretoPii</Respuesta>';

    $customerId = insertMailChannelCustomer($this->store->id, $piiEmail, $piiName);
    $sale = insertMailChannelSale($this->store->id, $this->user->id, $this->tipo->id, $customerId);

    $document = \Mockery::mock(DocumentService::class);
    $document->shouldReceive('buildDTEJsonFE')->once()->andReturn([
        'identificacion' => [
            'codigoGeneracion' => $sale->codigo_generacion,
            'fecEmi' => '2026-09-26',
        ],
        'emisor' => [],
        'receptor' => ['nombre' => $piiName, 'correo' => $piiEmail],
        'resumen' => [],
    ]);
    $this->app->instance(DocumentService::class, $document);

    config([
        'services.oci_smtp.from_email' => $fromEmail,
        'services.oci_smtp.from_name' => 'Gestock',
    ]);

    $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
    $pdf->shouldReceive('loadView')->once()->andReturnSelf();
    $pdf->shouldReceive('output')->once()->andReturn('%PDF');
    $this->app->instance('dompdf.wrapper', $pdf);
    Mail::shouldReceive('raw')->once()->andThrow(new RuntimeException(
        'SMTP 550 <'.$piiEmail.'> '.$jwt.' '.$xml.' {"documento":"DTEJSONSECRET"}'
    ));

    $response = app(OCIController::class)->emailSend($this->store, $sale);

    expect($response->getStatusCode())->toBe(500);

    $mail = file_get_contents($this->mailFile);
    expect(substr_count($mail, 'gestock.mail'))->toBe(1)
        ->and($mail)->toContain('"resultado":"failed"')
        ->and($mail)->toContain('"sale_id":'.$sale->id)
        ->and($mail)->toContain('"http_status":500')
        ->and($mail)->toContain('"error":"No se pudo enviar el correo por OCI"');

    $saleLog = is_file($this->saleFile) ? file_get_contents($this->saleFile) : '';
    $default = is_file($this->defaultFile) ? file_get_contents($this->defaultFile) : '';
    $captured = mailChannelCaptured($this->records);
    $secrets = [$piiEmail, $fromEmail, $piiName, $jwt, $xml, 'eyJ', '<?xml', 'DTEJSONSECRET', 'identificacion'];

    foreach ([$mail, $saleLog, $default, $captured] as $blob) {
        assertMailSecretsAbsent($blob, $secrets);
    }

    expect($saleLog)->not->toContain('gestock.mail');
    expect($default)->not->toContain('gestock.mail');
});

test('sale controller mail catch writes the mail channel and not sale dte', function () {
    $piiEmail = 'pii-customer@example.test';
    $jwt = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.PAYLOADSECRET.SIGNATURESECRET';
    $xml = '<?xml version="1.0"?><Respuesta>secreto</Respuesta>';

    $this->mock(OCIController::class, function ($mock) use ($piiEmail, $jwt, $xml) {
        $mock->shouldReceive('emailSend')->once()->andThrow(new RuntimeException(
            'smtp '.$piiEmail.' '.$jwt.' '.$xml
        ));
    });
    $this->mock(HaciendaAuthService::class, function ($mock) {
        $mock->shouldReceive('getToken')->andReturn('not-a-real-token');
    });
    Http::fake();

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
    $mail = file_get_contents($this->mailFile);

    expect(substr_count($mail, 'gestock.mail'))->toBe(1)
        ->and($mail)->toContain('"resultado":"failed"')
        ->and($mail)->toContain('"sale_id":'.$saleId)
        ->and($mail)->toContain('"dte_status":"PROCESADO"')
        ->and($mail)->toContain('"correlativo":'.MailLog::correlativoFromControl($sale->numero_control))
        ->and($mail)->toContain('"error":"omitted"');

    assertMailSecretsAbsent($mail, [$piiEmail, $jwt, $xml, 'eyJ', '<?xml']);

    $saleLog = is_file($this->saleFile) ? file_get_contents($this->saleFile) : '';
    expect($saleLog)->not->toContain('gestock.mail')
        ->and($saleLog)->not->toContain('Error Enviando correo')
        ->and($saleLog)->not->toContain($piiEmail)
        ->and($saleLog)->not->toContain($jwt)
        ->and($saleLog)->not->toContain('<?xml');

    assertMailSecretsAbsent(mailChannelCaptured($this->records), [$piiEmail, $jwt, '<?xml', 'eyJ']);
});

function insertMailChannelCustomer(int $storeId, ?string $correo, string $nombre): int
{
    DB::table('cod_actividad')->insertOrIgnore([
        'codigo' => '12345',
        'nombre' => 'Actividad',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('departamentos')->insertOrIgnore([
        'codigo' => '06',
        'nombre' => 'San Salvador',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $id = DB::table('customers')->insertGetId([
        'store_id' => $storeId,
        'numDocumento' => '0614'.str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT),
        'nombre' => $nombre,
        'codActividad' => '12345',
        'direccion_departamento' => '06',
        'direccion_municipio' => '14',
        'correo' => $correo,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

function insertMailChannelSale(int $storeId, int $userId, int $tipoId, int $customerId): Sale
{
    return Sale::create([
        'store_id' => $storeId,
        'user_id' => $userId,
        'customers_id' => $customerId,
        'sale_date' => '2026-09-26',
        'total_amount' => 11.30,
        'net_amount' => 11.30,
        'dte_status' => 'PROCESADO',
        'numero_control' => 'DTE-01-M001P001-000000000000015',
        'codigo_generacion' => 'ABCDEF01-2345-6789-ABCD-EF0123456789',
        'tipo_documento_id' => $tipoId,
        'environment' => 'Development',
        'payment_method' => 'Efectivo',
    ]);
}

function mailChannelCaptured(array $records): string
{
    return implode("\n", $records);
}

function assertMailSecretsAbsent(string $blob, array $secrets): void
{
    foreach ($secrets as $secret) {
        expect($blob)->not->toContain($secret);
    }
}
