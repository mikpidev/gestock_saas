# ART-0925-02 — Sale harden verification

Base: `main` @ `ed32662c9bdbc341b6dab4a1e8ba83f92329e4e4` (`change env to config service`).

ART-0925-01 is not applied on this base. This patch only changes sale access and `POST /stores/{store}/sales`. Company, store, correlativo, and dashboard policies are untouched.

Apply on a clean `main` from the repo root:

```bash
git apply artifacts/ART-0925-02.patch
```

The patch is `artifacts/ART-0925-02.patch`. `git apply --check --reverse` succeeded against the working tree that produced it.

## Result

`tests/Feature/SaleStoreHardeningTest.php`: **4 passed, 20 assertions**, exit code 0.

```text
php artisan test tests/Feature/SaleStoreHardeningTest.php

PASS  Tests\Feature\SaleStoreHardeningTest
✓ a user cannot create a sale for another store in the same company
✓ a sale rejects a customer that belongs to another store
✓ a sale rejects a product that belongs to another store
✓ a manipulated line price does not change the persisted sale line price

Tests:    4 passed (20 assertions)
Duration: 0.35s
```

PHP 8.4.26, Pest, `phpunit.xml` (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`). `Http::fake()` blocks Hacienda calls. The price case persisted `sale_details.unit_price` and `subtotal` from `product_types.price` (`10.00` × qty `2` → unit `10.00`, subtotal `20.00`, `sales.total_amount` `20.00`) while the request sent `products.0.price = 1.00`.

| Case | Expected | Observed |
| --- | --- | --- |
| Role `user` posts to another store in the same company | 403, no sale | 403, `Sale::count()` 0 |
| `customers_id` from another store | 422 on `customers_id`, no sale | 422, `Sale::count()` 0 |
| `products.*.id` from another store | 422 on `products.0.id`, no sale | 422, `Sale::count()` 0 |
| Client price differs from catalog price | Persisted line price is `product.price` | unit price `10.00`, not `1.00` |

## What the patch changes

`SaleController::validateStoreAccess` for role `user` now requires both `company_id` and `store_id`. Admin and superadmin checks are unchanged. There is no shared Authz helper on `main`, so the check stays in this method (every sale action that already calls it, including create and store).

`SaleController::store` validates:

- `customers_id`: `Rule::exists('customers', 'id')->where('store_id', $store->id)`
- `products.*.id`: `Rule::exists('product_types', 'id')->where('store_id', $store->id)`

Line `unit_price`, `subtotal`, and sale totals use `product_types.price`. The client `products.*.price` is validated as optional numeric input and is not used.

## Notes for the local run

`app/Models/store.php` and `app/Models/company.php` do not match PSR-4 case, so Linux cannot autoload `App\Models\Store` or `App\Models\Company`. The feature test `require_once`s those files before `RefreshDatabase`. On a case-insensitive filesystem the same requires are harmless. Full `RefreshDatabase` migrations completed on SQLite once those classes were loaded.

`generateTestSales` (`POST /stores/{store}/sales/test`) still uses global `exists` and the client price. That route is outside this task.

Out of scope and not modified: company/store/correlativo/dashboard policies (ART-0925-01), `dte_status ===` (ART-0925-03), Idempotency-Key (ART-0925-04), pagination-data (ART-0925-05).
