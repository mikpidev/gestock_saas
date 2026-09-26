# ART-0925-05 — sales `pagination-data`

## Decision

`GET stores/{store}/pagination-data` (`stores.sales.data`) pointed at `SaleController::getPaginationData`. That method is not defined, so the request raised `BadMethodCallException` (HTTP 500).

The sales screen already paginates on the server: `SaleController::index` uses `paginate(15)`, and `resources/views/sales/index.blade.php` renders the pager from that paginator. The only caller of `pagination-data` was `getPaginationData()` in `resources/js/app.js`. Its success handler only wrote the payload to the console. It did not update the table or the pager.

The smaller fix that matches that usage is to remove the dead route and the client call. `GET /stores/{id}/pagination-data` is now an unmatched route (404). Store access rules on `SaleController::index` are unchanged.

## Patch

`artifacts/ART-0925-05.patch` (against `main`)

- `routes/web.php` — drop `stores/{store}/pagination-data`
- `resources/js/app.js` — drop `getPaginationData` and its `window` export
- `resources/views/sales/index.blade.php` — drop the `DOMContentLoaded` call
- `tests/Feature/SalesPaginationDataTest.php` — route absent, guest 404, authenticated 404

Apply on `main` (or on a tree that still has this route and caller):

```bash
git apply --check artifacts/ART-0925-05.patch
git apply artifacts/ART-0925-05.patch
```

Checked here with `git apply --check --reverse` against the working tree: ok.

## Commands

```bash
php artisan route:list --path=pagination
php artisan route:list --name=stores.sales
php artisan test --filter=SalesPaginationData
```

PHP 8.4.26, Pest, sqlite in-memory (`phpunit.xml`).

## Results

`php artisan route:list --path=pagination`

```
ERROR  Your application doesn't have any routes matching the given criteria.
```

`php artisan route:list --name=stores.sales` still lists `stores.sales.index` (`SaleController@index`) and the other sales routes. `stores.sales.data` is absent.

`php artisan test --filter=SalesPaginationData`

```
PASS  Tests\Feature\SalesPaginationDataTest
✓ the broken sales pagination-data route is not registered
✓ guests receive 404 for the removed sales pagination-data endpoint
✓ authenticated users receive 404 for the removed sales pagination-data endpoint

Tests:    3 passed (3 assertions)
Duration: 0.30s
```

## Linux note for the feature suite

`RefreshDatabase` loads every migration. On a case-sensitive filesystem, `App\Models\Store` and `App\Models\Company` are not autoloadable because the files are `app/Models/store.php` and `app/Models/company.php`. Migration `2026_07_11_172001_insert_correlativos_existing_stores.php` then fails with `Class "App\Models\Store" not found` before any assertion.

The three tests above were run after temporary symlinks `app/Models/Store.php` → `store.php` and `app/Models/Company.php` → `company.php`. Those symlinks are not in the patch. A case-insensitive checkout (typical macOS) resolves the existing filenames without them.
