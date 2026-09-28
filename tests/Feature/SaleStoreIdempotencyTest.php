<?php

use App\Models\Company;
use App\Models\CorrelativoStore;
use App\Models\InvoiceNumber;
use App\Models\ProductType;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Store;
use App\Models\TipoDte;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Http::fake();

    $this->company = Company::create([
        'company_name' => 'Gestock Test',
        'address' => 'San Salvador',
        'phone' => '22223333',
        'owner' => 'Owner',
        'email' => 'company-'.uniqid().'@example.com',
        'plan' => 'basic',
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

    [$this->store, $this->product] = saleIdempotencyStore($this->company, $this->tipo, 'Tienda 1', 'tienda-'.uniqid().'@example.com');
});

function saleIdempotencyStore(Company $company, TipoDte $tipo, string $name, string $email): array
{
    $store = Store::create([
        'company_id' => $company->id,
        'store_name' => $name,
        'establecimiento' => 'M001',
        'punto_venta' => 'P001',
        'address' => 'Calle 1',
        'phone' => '77778888',
        'manager' => 'Manager',
        'email' => $email,
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

function saleIdempotencyPayload(int $productId, int $tipoId, float $price = 11.30): array
{
    return [
        'sale_date' => '2026-09-26',
        'discount_amount' => 0,
        'products' => [
            ['id' => $productId, 'quantity' => 1, 'price' => $price],
        ],
        'tipo_documento_id' => $tipoId,
        'payment_method' => 'Efectivo',
    ];
}

function postSale($testCase, Store $store, array $payload, ?string $idempotencyKey = null)
{
    $headers = ['Accept' => 'application/json'];

    if ($idempotencyKey !== null) {
        $headers['Idempotency-Key'] = $idempotencyKey;
    }

    return $testCase->postJson(route('stores.sales.store', $store), $payload, $headers);
}

function correlativoFor(Store $store, TipoDte $tipo): int
{
    return (int) CorrelativoStore::query()
        ->where('store_id', $store->id)
        ->where('tipo_documento_id', $tipo->id)
        ->value('correlativo');
}

test('a second post with the same idempotency key after commit returns the original sale', function () {
    $payload = saleIdempotencyPayload($this->product->id, $this->tipo->id);
    $key = 'sale-key-same';

    $first = postSale($this, $this->store, $payload, $key);
    $second = postSale($this, $this->store, $payload, '  '.$key.'  ');

    $first->assertOk();
    $second->assertOk();

    expect($second->json('sale_id'))->toBe($first->json('sale_id'))
        ->and($second->json('ticket_url'))->toBe($first->json('ticket_url'))
        ->and($second->json('pre_order_url'))->toBe($first->json('pre_order_url'))
        ->and($second->json('success'))->toBeTrue();

    expect(Sale::query()->where('store_id', $this->store->id)->count())->toBe(1)
        ->and(SaleDetail::query()->count())->toBe(1)
        ->and(InvoiceNumber::query()->where('store_id', $this->store->id)->count())->toBe(1)
        ->and(correlativoFor($this->store, $this->tipo))->toBe(1);

    $sale = Sale::query()->first();
    expect($sale->idempotency_key)->toBe($key)
        ->and($sale->numero_control)->toEndWith('000000000000001');
});

test('two different idempotency keys create two sales and two correlativos', function () {
    $payload = saleIdempotencyPayload($this->product->id, $this->tipo->id);

    $first = postSale($this, $this->store, $payload, 'key-a');
    $second = postSale($this, $this->store, $payload, 'key-b');

    $first->assertOk();
    $second->assertOk();

    expect($first->json('sale_id'))->not->toBe($second->json('sale_id'))
        ->and(Sale::query()->where('store_id', $this->store->id)->count())->toBe(2)
        ->and(SaleDetail::query()->count())->toBe(2)
        ->and(InvoiceNumber::query()->where('store_id', $this->store->id)->count())->toBe(2)
        ->and(correlativoFor($this->store, $this->tipo))->toBe(2);
});

test('the same idempotency key is scoped per store', function () {
    [$otherStore, $otherProduct] = saleIdempotencyStore(
        $this->company,
        $this->tipo,
        'Tienda 2',
        'tienda2-'.uniqid().'@example.com'
    );

    $key = 'shared-store-key';

    $first = postSale($this, $this->store, saleIdempotencyPayload($this->product->id, $this->tipo->id), $key);
    $second = postSale($this, $otherStore, saleIdempotencyPayload($otherProduct->id, $this->tipo->id), $key);

    $first->assertOk();
    $second->assertOk();

    expect($first->json('sale_id'))->not->toBe($second->json('sale_id'))
        ->and(Sale::query()->count())->toBe(2)
        ->and(correlativoFor($this->store, $this->tipo))->toBe(1)
        ->and(correlativoFor($otherStore, $this->tipo))->toBe(1);
});

test('posts without an idempotency key stay non-idempotent', function () {
    $payload = saleIdempotencyPayload($this->product->id, $this->tipo->id);

    $first = postSale($this, $this->store, $payload);
    $second = postSale($this, $this->store, $payload);

    $first->assertOk();
    $second->assertOk();

    expect($first->json('sale_id'))->not->toBe($second->json('sale_id'))
        ->and(Sale::query()->where('store_id', $this->store->id)->count())->toBe(2)
        ->and(Sale::query()->whereNotNull('idempotency_key')->count())->toBe(0)
        ->and(correlativoFor($this->store, $this->tipo))->toBe(2);
});

test('a failed detail insert rolls back the sale and the correlativo', function () {
    SaleDetail::creating(function () {
        throw new RuntimeException('detail insert failed');
    });

    try {
        $response = postSale(
            $this,
            $this->store,
            saleIdempotencyPayload($this->product->id, $this->tipo->id),
            'key-rollback'
        );

        $response->assertStatus(500);
        expect(Sale::withTrashed()->count())->toBe(0)
            ->and(InvoiceNumber::query()->count())->toBe(0)
            ->and(correlativoFor($this->store, $this->tipo))->toBe(0);
    } finally {
        SaleDetail::flushEventListeners();
    }
});

test('the unique store and key index rejects a duplicate sale row', function () {
    $payload = saleIdempotencyPayload($this->product->id, $this->tipo->id);
    postSale($this, $this->store, $payload, 'unique-key')->assertOk();

    $sale = Sale::query()->first();

    expect(fn () => Sale::create([
        'store_id' => $sale->store_id,
        'user_id' => $sale->user_id,
        'customers_id' => null,
        'sale_date' => '2026-09-26',
        'total_amount' => 1,
        'net_amount' => 1,
        'tipo_documento_id' => $this->tipo->id,
        'payment_method' => 'Efectivo',
        'idempotency_key' => 'unique-key',
    ]))->toThrow(UniqueConstraintViolationException::class);

    expect(Sale::query()->count())->toBe(1)
        ->and(correlativoFor($this->store, $this->tipo))->toBe(1);
});

test('an idempotency key longer than 255 characters is rejected', function () {
    $response = postSale(
        $this,
        $this->store,
        saleIdempotencyPayload($this->product->id, $this->tipo->id),
        str_repeat('k', 256)
    );

    $response->assertStatus(422);
    expect(Sale::query()->count())->toBe(0)
        ->and(correlativoFor($this->store, $this->tipo))->toBe(0);
});

test('replaying a key ignores a different payload and keeps the original sale', function () {
    $key = 'payload-ignored';
    $first = postSale(
        $this,
        $this->store,
        saleIdempotencyPayload($this->product->id, $this->tipo->id, 11.30),
        $key
    );
    $second = postSale(
        $this,
        $this->store,
        saleIdempotencyPayload($this->product->id, $this->tipo->id, 99.99),
        $key
    );

    $first->assertOk();
    $second->assertOk();

    expect($second->json('sale_id'))->toBe($first->json('sale_id'))
        ->and(Sale::query()->count())->toBe(1)
        ->and((float) Sale::query()->value('total_amount'))->toBe(11.30)
        ->and(correlativoFor($this->store, $this->tipo))->toBe(1);
});
