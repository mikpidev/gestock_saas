# ART-0925-04 — Sale create idempotency

Patch: `artifacts/ART-0925-04.patch`  
Base: `main` @ `ed32662` (`change env to config service`)  
`git apply --check` of this patch against that `main` succeeded.

No pull request was opened. Apply locally, on `main` or after 01–03.

```bash
git apply artifacts/ART-0925-04.patch
php artisan migrate
php artisan test --filter=SaleStoreIdempotencyTest
```

The patch does not edit the `dte_status = 'PROCESADO'` lines (03), the product `exists` / price validation loop (02), or authorization (01). It adds `idempotency_key` on the sale insert and wraps correlativo + sale + details in a transaction inside `SaleController::store`.

## Contract

`POST /stores/{store}/sales` (`stores.sales.store`).

| Item | Behavior |
| --- | --- |
| Header | `Idempotency-Key` (name is case-insensitive). Body field is not read. |
| Optional | Missing, empty, or whitespace-only header keeps the old behavior: every POST creates a sale. |
| Length | Trimmed value, 1–255 characters. Longer → HTTP 422, no sale, correlativo unchanged. |
| Scope | Unique index `sales_store_idempotency_unique` on `(store_id, idempotency_key)`. The same key on another store is a different sale. Multiple rows with `NULL` key are allowed. |
| Replay | Same store + same key returns the original sale. HTTP 200. Same `sale_id` and the same ticket URLs. No second correlativo, invoice number, or detail rows. DTE is not sent again. |
| Body on replay | Ignored. A different price or cart still returns the first sale. |
| Race | The unique index is the lock. If two requests pass the lookup, the loser hits `UniqueConstraintViolationException`, rolls back its correlativo increment, and returns the winner. |
| Soft delete | Lookup uses `withTrashed()`, so a voided sale still occupies its key. |

### Response (unchanged shape)

Default (what `sales/create` `fetch` gets):

```json
{
  "success": true,
  "ticket_url": ".../print",
  "pre_order_url": ".../preorder",
  "sale_id": 123
}
```

If the request is ajax (`X-Requested-With: XMLHttpRequest`):

```json
{
  "success": true,
  "message": "Venta creada y DTE enviado correctamente",
  "ticket_url": ".../print",
  "dte_status": "PENDIENTE"
}
```

The POS screen (`resources/views/sales/create.blade.php`) sends `Idempotency-Key`. It keeps that key until the response has `success`, and a second click while the request is in flight is ignored. A lost response can be retried with the same key.

## What the transaction covers

`DB::beginTransaction()` wraps:

1. `InvoiceNumber::getNextNumber()` → `CorrelativoStore::next()` (its own `DB::transaction` becomes a savepoint)
2. `Sale::create` (stores `idempotency_key`)
3. sale details

Commit happens before DTE / Hacienda calls, so those stay outside the lock. If detail insert fails, the sale row, the invoice number, and the correlativo increment roll back together.

## Tests

`php artisan test --filter=SaleStoreIdempotencyTest`  
PHP 8.4.26, Pest, sqlite `:memory:` (`phpunit.xml`).

```
PASS  Tests\Feature\SaleStoreIdempotencyTest
✓ a second post with the same idempotency key after commit returns the original sale
✓ two different idempotency keys create two sales and two correlativos
✓ the same idempotency key is scoped per store
✓ posts without an idempotency key stay non-idempotent
✓ a failed detail insert rolls back the sale and the correlativo
✓ the unique store and key index rejects a duplicate sale row
✓ an idempotency key longer than 255 characters is rejected
✓ replaying a key ignores a different payload and keeps the original sale

Tests:    8 passed (48 assertions)
Duration: 0.55s
```

Covered:

- Second POST after the first commit, same key (also with surrounding spaces) → 1 sale, same `sale_id` / ticket URLs, correlativo `1`, 1 detail, 1 invoice number.
- Two keys → 2 sales, correlativo `2`.
- Same key, two stores → 2 sales, each correlativo `1`.
- No header → still 2 sales (legacy).
- Exception while creating details → 0 sales, correlativo stays `0` (proves the increment is inside the transaction).
- Direct second insert with the same `(store_id, key)` throws `UniqueConstraintViolationException`.
- Key of 256 characters → 422, no sale.
- Replay with a different price returns the original total.

A second PHP process against sqlite `:memory:` cannot see the test transaction, so a true parallel race was not executed. The after-commit replay plus the unique index is the practical stand-in; the controller catch rolls the loser back and returns the stored sale.

### Linux note (not part of this patch)

On Linux, `app/Models/store.php` and `app/Models/company.php` do not match PSR-4, so `RefreshDatabase` dies in `2026_07_11_172001_insert_correlativos_existing_stores.php` with `Class "App\Models\Store" not found`. Case-insensitive filesystems (typical macOS) hide this. The run above used temporary symlinks `Store.php` → `store.php` and `Company.php` → `company.php`, then deleted them. They are not in the patch.
