# How to apply the sale and DTE logs

Patch file `artifacts/ART-0925-07.patch`.
Base commit `main` at `ed32662` (`change env to config service`).
No pull request was opened.

`git apply --check` of this patch against that `main` succeeded.
The same check succeeded after `artifacts/ART-0925-04.patch` was applied on top of `main`.

## Apply

Apply ART-0925-04 first if you want the replay line. `main` does not contain `Idempotency-Key` handling. This patch does not add it. On raw `main`, every successful `POST` still inserts a sale, and the log says `outcome=created` each time.

```bash
git apply --check artifacts/ART-0925-07.patch
git apply artifacts/ART-0925-04.patch
git apply artifacts/ART-0925-07.patch
php artisan test --filter=SaleStoreLoggingTest
```

ART-0925-04 is the branch `cursor/sale-idempotency-key-039c`. Its patch is `artifacts/ART-0925-04.patch` on that branch.

The logging patch edits `routes/web.php`, `app/Http/Controllers/DTEController.php`, `phpunit.xml`, and adds `app/Http/Middleware/LogSaleStoreOutcome.php` plus `tests/Feature/SaleStoreLoggingTest.php`. It does not edit `SaleController`.

`phpunit.xml` sets `APP_KEY` for the test process. HTTP tests throw `MissingAppKeyException` without it when `.env` is absent.

## What you can grep

`POST /stores/{store}/sales` (`stores.sales.store`) writes one `gestock.sale` line after a 200 response.

`outcome` is `created` when this request inserted the sale. `outcome` is `replay` when the response returns a sale this request did not insert. That replay path is ART-0925-04. A second post with the same key still returns one sale id, and the second line does not insert another row.

Fields on `gestock.sale` are `outcome`, `user_id`, `store_id`, `sale_id`, `idempotency_key`, `correlativo`, `status_http`.

`idempotency_key` is the stored key when the column exists, otherwise the trimmed header. Keys of 64 characters or fewer are logged raw. Longer keys are logged as SHA-256. The header length limit from ART-0925-04 stays 255.

`correlativo` is the integer at the end of `numero_control`.

`generarDTE`, `generarDTECreditNote`, and `generarDTEDebitNote` each write one `gestock.dte` line per attempt, including when the attempt throws. Fields are `sale_id`, `store_id`, `user_id`, `dte_type`, `dte_status_before`, `dte_status_after`, `mh_code`, `mh_message`, `duration_ms`.

`mh_code` is `codigoMsg` or `codigo_msg`. `mh_message` is `descripcionMsg`, `mensaje`, or `descripcion_msg`, cut at 160 characters. A message that contains `<` or `{` is stored as null so the DTE JSON or XML is not written on this line. The line does not include the signed body or the Hacienda token.

A replay does not call `generarDTE` again, so the second post does not add a second `gestock.dte` line.

Older `Log::info` calls in `DTEController` and `ReceptionService` still print the DTE JSON and the token. This patch does not remove them. Grep `gestock.dte` for the short line.

## Commands and results

Run from this checkout with PHP 8.4.26. `app/Models/store.php` and `app/Models/company.php` do not match PSR-4 on Linux. The new test loads those two files with `require_once`. That is not a rename.

```bash
php artisan test --filter=SaleStoreLoggingTest
php artisan test --filter=SaleStoreIdempotencyTest
```

`SaleStoreLoggingTest` finished with 3 tests and 42 assertions.
`SaleStoreIdempotencyTest` finished with 8 tests and 48 assertions.
Exit code was 0. Pest printed a warning on each test because `.env` is missing (`file_get_contents(/workspace/.env)`). No assertion failed.

The created-then-replay test posted twice with `Idempotency-Key: sale-key-same` (the second header had surrounding spaces). Both responses were HTTP 200. Both JSON bodies had the same `sale_id`. `sales` row count stayed 1. `gestock.dte` was written once.

Measured `gestock.sale` context for that pair:

```json
{"outcome":"created","user_id":1,"store_id":1,"sale_id":1,"idempotency_key":"sale-key-same","correlativo":1,"status_http":200}
{"outcome":"replay","user_id":1,"store_id":1,"sale_id":1,"idempotency_key":"sale-key-same","correlativo":1,"status_http":200}
```

Measured `gestock.dte` context from that create. The attempt throws before Hacienda because the test store has no tax info. `Http::fake()` was on. No live Ministerio de Hacienda call was made.

```json
{"sale_id":1,"store_id":1,"user_id":1,"dte_type":"01","dte_status_before":"PENDIENTE","dte_status_after":"PENDIENTE","mh_code":null,"mh_message":"Attempt to read property \"nit\" on null","duration_ms":4}
```

A separate test calls `generarDTE` with mocked `DocumentService`, `HaciendaAuthService`, and `ReceptionService`. The fake Hacienda result is `estado=PROCESADO`, `codigoMsg=001`, `descripcionMsg=Recibido`, plus a document JSON string. The `gestock.dte` context was `dte_status_before=PENDIENTE`, `dte_status_after=PROCESADO`, `mh_code=001`, `mh_message=Recibido`. The context did not contain the signed body, the token, or the document JSON.

A 70 character key logged `idempotency_key` as `hash('sha256', $key)`, not the raw key.

In the app log file the same payloads are the message context. Default channel is `stack`, which writes `storage/logs/laravel.log`.

```text
gestock.sale {"outcome":"created",...}
gestock.sale {"outcome":"replay",...}
gestock.dte {"sale_id":1,"dte_status_before":"PENDIENTE","dte_status_after":"PROCESADO",...}
```

## Smoke after you apply both patches

Create one sale twice with the same `Idempotency-Key` header. ART-0925-04's sale form sends that header. Then grep.

```bash
grep 'gestock.sale' storage/logs/laravel.log
```

You want two lines for that `sale_id`. The first has `"outcome":"created"`. The second has `"outcome":"replay"`. The sales table still has one row for that key.

## Not in this patch

Authorization 403 logging is not included. `abort(403)` is copied across store, company, sales, and other controllers. There is no single policy method on `main`. Covering each copy would be the mass logging this task leaves out.

Sale harden rejects are not included. `store_mismatch`, `product_not_in_store`, and `price_tamper` are not in this tree. Those checks live on `cursor/sale-harden-store-access-ab1e`. This patch does not add them.

Dashboard filter logging is not included.
