# ART-LOG-02 sale and DTE debug file

Patch file `artifacts/ART-LOG-02.patch`.
Base is `main` at `ed32662` after `artifacts/ART-0925-07.patch`.
No pull request was opened. Application files are not committed on this branch. Miguel applies the patch locally.

`git apply --check` succeeded on `main` + ART-0925-07.
The same check succeeded on `main` + ART-0925-04 + ART-0925-07. The SaleController hunks stay on the log lines, so either order of 04 relative to this patch is fine as long as 07 is already applied.

## Apply

```bash
git apply --check artifacts/ART-0925-07.patch
git apply artifacts/ART-0925-07.patch
git apply --check artifacts/ART-LOG-02.patch
git apply artifacts/ART-LOG-02.patch
```

Apply ART-0925-04 first if you want the replay line. It is `artifacts/ART-0925-04.patch` on branch `cursor/sale-idempotency-key-039c`. This patch does not add idempotency.

```bash
git apply artifacts/ART-0925-04.patch
git apply artifacts/ART-0925-07.patch
git apply artifacts/ART-LOG-02.patch
php artisan test --filter=SaleStoreLoggingTest
php artisan test --filter=SaleDteDebugChannelTest
```

## Channel

`config/logging.php` adds a stack that is not part of the default `LOG_STACK`.

| Name | Driver | Writes to |
| --- | --- | --- |
| `sale_dte` | stack | `sale_dte_file` only |
| `sale_dte_file` | single | `storage/logs/gestock-sale-dte.log` |

Level is `LOG_SALE_DTE_LEVEL`, default `debug`.

`gestock.sale` (middleware) and `gestock.dte` (`DTEController::logDteAttempt`) both call `App\Support\SaleDteLog`, which writes that stack. One file has create, replay, and the MH attempt. Unrelated app lines stay on `storage/logs/laravel.log`.

`token_preview` is the first 12 hex characters of `hash('sha256', $token)`. It is not the token and it does not start with `eyJ`.

`SaleDteLog::mhSummary` keeps `estado`, `mh_code`, and `mh_message` (160 characters). A value that contains `<`, `{`, or `eyJ` is dropped. HTTP helpers add `http_status` and do not write `response->body()`.

## What was removed from the hot path

These lines no longer write Hacienda tokens, `api_key`, `passwordPri`, the DTE JSON, the signed body, or the raw MH body. They go to `sale_dte` instead of the default channel.

- `SaleController` `store`, `refreshDTE`, and the void `destroy` error. `Creating sale with data` no longer dumps the request. `Venta creada` logs `sale_id`.
- `DTEController` `generarDTE`, credit note, debit note, consulta errors, and contingencia. Before-sign logs are `sale_id` and `tipo_dte`. Signed body is `has_body`. Token is `token_preview`. Hacienda result is the summary.
- `ReceptionService` send sale, NC, and ND. The pre-request log no longer uses `substr($token, 0, 20)`.
- `HaciendaAuthService` token request. User, password, response body, and the token value are gone.
- `DocumentService::signDocument`. The firmador payload log is deleted. Failures log HTTP status only.
- `ConsultaService` sale, NC, and ND consulta. Response body and emisor NIT are not logged.
- `ContingenciaService` MH body.
- `VoidService` and `VoidDTEController`, `VoidNCController`, `VoidNDController`. The `dte` JSON and `response` arrays are gone.

Error lines on this path keep `sale_id` and `SaleDteLog::safeMessage()`. A message that looks like JSON, XML, or a JWT becomes `omitted payload`. Stack traces are not written on these lines. MH send, sign, and auth code paths are unchanged.

Mail channel is not added.

## Commands and results

Run from a checkout with PHP 8.4.26 after both patches. `phpunit.xml` from ART-0925-07 sets `APP_KEY`.

```bash
php artisan test --filter=SaleDteDebugChannelTest
php artisan test --filter=SaleStoreLoggingTest
```

With ART-0925-04 applied as well:

```bash
php artisan test --filter=SaleStoreLoggingTest
php artisan test --filter=SaleStoreIdempotencyTest
php artisan test --filter=SaleDteDebugChannelTest
```

Measured on PHP 8.4.26.

`SaleDteDebugChannelTest` finished with 1 test and 48 assertions. Exit code 0.
That test posts one sale, then calls `generarDTE` with `Http::fake()` for the firmador, the Hacienda token URL, and `recepciondte`. The fake token is a JWT. The fake firmador body is `SIGNEDBODYSECRET`. The fake MH `documento` is XML that contains `NombreSecretoPii`. The DTE JSON built for signing contains `identificacion`, `DTEJSONSECRET`, and `pii-customer@example.test`. No live Ministerio de Hacienda call was made.

The test file `storage/logs/gestock-sale-dte-test.log` contained `gestock.sale`, `gestock.dte`, `"sale_id"`, `"mh_code":"001"`, and `"token_preview"` equal to `substr(hash('sha256', $jwt), 0, 12)`. It did not contain the JWT, `eyJ`, `api_key`, `passwordPri`, the API key, the private password, the signed body, the sello, the XML, `identificacion`, `"token":`, or `"documento":`. The in-memory log listener for every channel was clean for the same strings.

`SaleStoreLoggingTest` without ART-0925-04: the long-key test and the `generarDTE` test passed. The created-then-replay test failed with sale count 2, which is the same result ART-0925-07 documents on raw `main`.

With ART-0925-04, ART-0925-07, and this patch together: 12 tests, 138 assertions, exit code 0. That is `SaleStoreLoggingTest` (3), `SaleStoreIdempotencyTest` (8), and `SaleDteDebugChannelTest` (1). Pest warned on each test because `.env` is missing (`file_get_contents` of `/workspace/.env`). No assertion failed.

## Smoke for Mark

After a sale in the app:

```bash
grep 'gestock.sale' storage/logs/gestock-sale-dte.log
grep 'gestock.dte' storage/logs/gestock-sale-dte.log
grep '"sale_id":SALE_ID' storage/logs/gestock-sale-dte.log
grep -E 'eyJ|api_key|passwordPri|<?xml|<Respuesta|identificacion|"token":' storage/logs/gestock-sale-dte.log
```

Replace `SALE_ID` with the sale. The first three commands should show the create line and the DTE attempt in this file, not only in `laravel.log`. The last command should print nothing for that sale. `token_preview` may appear. It is a hash prefix, not the Hacienda token.
