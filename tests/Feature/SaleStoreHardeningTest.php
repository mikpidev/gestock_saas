<?php

use App\Models\Company;
use App\Models\CorrelativoStore;
use App\Models\Customer;
use App\Models\ProductType;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

/*
 * store.php / company.php do not match PSR-4 case, so Linux cannot autoload
 * App\Models\Store or App\Models\Company. Load them before RefreshDatabase.
 */
require_once dirname(__DIR__, 2).'/app/Models/store.php';
require_once dirname(__DIR__, 2).'/app/Models/company.php';

function saleHardenRole(string $name): Role
{
    return Role::findOrCreate($name, 'web');
}

function saleHardenCompany(string $email): Company
{
    return Company::create([
        'company_name' => 'Empresa '.$email,
        'address' => 'San Salvador',
        'phone' => '22222222',
        'owner' => 'Dueno',
        'email' => $email,
        'plan' => 'free',
        'deployment_type' => 'saas',
        'status' => 'activa',
        'comments' => 'test',
    ]);
}

function saleHardenStore(Company $company, string $name, string $email): Store
{
    return Store::create([
        'company_id' => $company->id,
        'store_name' => $name,
        'establecimiento' => '0001',
        'punto_venta' => '0001',
        'address' => 'Colonia Centro',
        'phone' => '22223333',
        'manager' => 'Encargado',
        'email' => $email,
        'status' => 'activa',
        'environment' => 'Production',
        'comments' => null,
    ]);
}

function saleHardenUser(Company $company, Store $store, string $role): User
{
    $user = User::factory()->create([
        'company_id' => $company->id,
        'store_id' => $store->id,
    ]);
    $user->assignRole(saleHardenRole($role));

    return $user;
}

function saleHardenCatalogs(): void
{
    DB::table('departamentos')->insertOrIgnore([
        'codigo' => '01',
        'nombre' => 'San Salvador',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('cod_actividad')->insertOrIgnore([
        'codigo' => '00001',
        'nombre' => 'Venta',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function saleHardenCustomer(Store $store, string $documento): Customer
{
    saleHardenCatalogs();

    $id = DB::table('customers')->insertGetId([
        'store_id' => $store->id,
        'numDocumento' => $documento,
        'nombre' => 'Cliente '.$documento,
        'codActividad' => '00001',
        'direccion_departamento' => '01',
        'direccion_municipio' => '01',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return Customer::findOrFail($id);
}

function saleHardenProduct(Store $store, string $name, float $price): ProductType
{
    return ProductType::create([
        'name' => $name,
        'price' => $price,
        'stock' => 20,
        'category' => 'General',
        'company_id' => $store->company_id,
        'store_id' => $store->id,
    ]);
}

function saleHardenTipoDocumento(): int
{
    $existing = DB::table('tipo_documento')->where('codigo', '01')->value('id');
    if ($existing) {
        return (int) $existing;
    }

    return (int) DB::table('tipo_documento')->insertGetId([
        'codigo' => '01',
        'nombre' => 'Factura',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function saleHardenPayload(Store $store, ?int $customerId, int $productId, float $clientPrice): array
{
    $tipoId = saleHardenTipoDocumento();

    CorrelativoStore::firstOrCreate(
        [
            'store_id' => $store->id,
            'tipo_documento_id' => $tipoId,
        ],
        [
            'correlativo' => 0,
        ]
    );

    return [
        'customers_id' => $customerId,
        'sale_date' => now()->toDateString(),
        'discount_amount' => 0,
        'products' => [
            [
                'id' => $productId,
                'quantity' => 2,
                'price' => $clientPrice,
            ],
        ],
        'tipo_documento_id' => $tipoId,
        'payment_method' => 'Efectivo',
    ];
}

beforeEach(function () {
    Http::fake();
});

test('a user cannot create a sale for another store in the same company', function () {
    $company = saleHardenCompany('co-user-store@example.com');
    $storeA = saleHardenStore($company, 'Tienda A', 'tienda-a@example.com');
    $storeB = saleHardenStore($company, 'Tienda B', 'tienda-b@example.com');
    $user = saleHardenUser($company, $storeA, 'user');

    $customer = saleHardenCustomer($storeB, '06140000000001');
    $product = saleHardenProduct($storeB, 'Producto B', 10);

    $response = $this->actingAs($user)->postJson(
        route('stores.sales.store', $storeB),
        saleHardenPayload($storeB, $customer->id, $product->id, 10)
    );

    $response->assertForbidden();
    expect(Sale::count())->toBe(0);
});

test('a sale rejects a customer that belongs to another store', function () {
    $company = saleHardenCompany('co-customer@example.com');
    $storeA = saleHardenStore($company, 'Tienda A', 'tienda-a-cust@example.com');
    $storeB = saleHardenStore($company, 'Tienda B', 'tienda-b-cust@example.com');
    $user = saleHardenUser($company, $storeA, 'user');

    $foreignCustomer = saleHardenCustomer($storeB, '06140000000002');
    $product = saleHardenProduct($storeA, 'Producto A', 10);

    $response = $this->actingAs($user)->postJson(
        route('stores.sales.store', $storeA),
        saleHardenPayload($storeA, $foreignCustomer->id, $product->id, 10)
    );

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['customers_id']);
    expect(Sale::count())->toBe(0);
});

test('a sale rejects a product that belongs to another store', function () {
    $company = saleHardenCompany('co-product@example.com');
    $storeA = saleHardenStore($company, 'Tienda A', 'tienda-a-prod@example.com');
    $storeB = saleHardenStore($company, 'Tienda B', 'tienda-b-prod@example.com');
    $user = saleHardenUser($company, $storeA, 'user');

    $customer = saleHardenCustomer($storeA, '06140000000003');
    $foreignProduct = saleHardenProduct($storeB, 'Producto B', 10);

    $response = $this->actingAs($user)->postJson(
        route('stores.sales.store', $storeA),
        saleHardenPayload($storeA, $customer->id, $foreignProduct->id, 10)
    );

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['products.0.id']);
    expect(Sale::count())->toBe(0);
});

test('a manipulated line price does not change the persisted sale line price', function () {
    $company = saleHardenCompany('co-price@example.com');
    $store = saleHardenStore($company, 'Tienda A', 'tienda-price@example.com');
    $user = saleHardenUser($company, $store, 'user');

    $catalogPrice = 10.00;
    $customer = saleHardenCustomer($store, '06140000000004');
    $product = saleHardenProduct($store, 'Producto Precio', $catalogPrice);

    $response = $this->actingAs($user)->postJson(
        route('stores.sales.store', $store),
        saleHardenPayload($store, $customer->id, $product->id, 1.00)
    );

    $response->assertOk();

    $sale = Sale::first();
    expect($sale)->not->toBeNull();
    expect((int) $sale->store_id)->toBe((int) $store->id);
    expect((int) $sale->customers_id)->toBe((int) $customer->id);

    $detail = SaleDetail::where('sale_id', $sale->id)->first();
    expect($detail)->not->toBeNull();
    expect((int) $detail->product_type_id)->toBe((int) $product->id);
    expect(round((float) $detail->unit_price, 2))->toBe($catalogPrice);
    expect(round((float) $detail->subtotal, 2))->toBe(20.00);
    expect(round((float) $sale->total_amount, 2))->toBe(20.00);
    expect(round((float) $detail->unit_price, 2))->not->toBe(1.00);
});
