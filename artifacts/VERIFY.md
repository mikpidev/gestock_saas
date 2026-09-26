# ART-LOG-02 sale and DTE debug file

Patch file `artifacts/ART-LOG-02.patch`.
Base is `main` at `ed32662` after `artifacts/ART-0925-07.patch`.
No pull request was opened. Application files are not committed on this branch. Miguel applies the patch locally.

`git apply --check` for ART-LOG-02 succeeded on `main` + ART-0925-07.
The same check succeeded on `main` + ART-0925-04 + ART-0925-07, and on `main` + ART-0925-07 when `Log::debug('URL firmador', ...)` sits after the payload log. The SaleController hunks stay on the log lines, so either order of 04 relative to this patch is fine as long as 07 is already applied.

## Apply

Order is ART-0925-07, then ART-LOG-02, then ART-LOG-02b.

```bash
git apply --check artifacts/ART-0925-07.patch
git apply artifacts/ART-0925-07.patch
git apply --check artifacts/ART-LOG-02.patch
git apply artifacts/ART-LOG-02.patch
git apply --check artifacts/ART-LOG-02b.patch
git apply artifacts/ART-LOG-02b.patch
```

ART-LOG-02b deletes one drifted call that is not on clean `main`:

```php
Log::debug('URL firmador', [
    'url' => "http://{$host}:{$port['port']}/firmardocumento/",
]);
```

That call sits in `signDocument` after the Puerto Certificado log and before `$host = config('services.firma.url')`. On a clean tree the check fails because the line is absent. Skip 02b in that case. On Mark's tree the check succeeds. Apply it. Do not add `use Illuminate\Support\Facades\Log` back.

Apply ART-0925-04 first if you want the replay line. It is `artifacts/ART-0925-04.patch` on branch `cursor/sale-idempotency-key-039c`. This patch does not add idempotency.

```bash
git apply artifacts/ART-0925-04.patch
git apply artifacts/ART-0925-07.patch
git apply artifacts/ART-LOG-02.patch
git apply artifacts/ART-LOG-02b.patch
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
- `DocumentService::signDocument`. The firmador payload log is deleted. Failures log HTTP status only. ART-LOG-02b also deletes `Log::debug('URL firmador', ...)`. The firmador URL is not logged.
- `ConsultaService` sale, NC, and ND consulta. Response body and emisor NIT are not logged.
- `ContingenciaService` MH body.
- `VoidService` and `VoidDTEController`, `VoidNCController`, `VoidNDController`. The `dte` JSON and `response` arrays are gone.

Error lines on this path keep `sale_id` and `SaleDteLog::safeMessage()`. A message that looks like JSON, XML, or a JWT becomes `omitted payload`. Stack traces are not written on these lines. MH send, sign, and auth code paths are unchanged.

Mail channel is not added.

## Commands and results

Run from a checkout with PHP 8.4.26 after ART-0925-07 and ART-LOG-02. Apply ART-LOG-02b as well when the URL line is present. `phpunit.xml` from ART-0925-07 sets `APP_KEY`.

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

`SaleDteDebugChannelTest` finished with 1 test and 50 assertions. Exit code 0.
That test posts one sale, then calls `generarDTE` with `Http::fake()` for the firmador, the Hacienda token URL, and `recepciondte`. The fake token is a JWT. The fake firmador body is `SIGNEDBODYSECRET`. The fake MH `documento` is XML that contains `NombreSecretoPii`. The DTE JSON built for signing contains `identificacion`, `DTEJSONSECRET`, and `pii-customer@example.test`. No live Ministerio de Hacienda call was made.

The test file `storage/logs/gestock-sale-dte-test.log` contained `gestock.sale`, `gestock.dte`, `"sale_id"`, `"mh_code":"001"`, and `"token_preview"` equal to `substr(hash('sha256', $jwt), 0, 12)`. It did not contain the JWT, `eyJ`, `api_key`, `passwordPri`, the API key, the private password, the signed body, the sello, the XML, `identificacion`, `"token":`, or `"documento":`. The in-memory log listener for every channel was clean for the same strings.

`SaleStoreLoggingTest` without ART-0925-04: the long-key test and the `generarDTE` test passed. The created-then-replay test failed with sale count 2, which is the same result ART-0925-07 documents on raw `main`.

With ART-0925-04, ART-0925-07, and ART-LOG-02 together: 12 tests, 140 assertions, exit code 0. That is `SaleStoreLoggingTest` (3, 42 assertions), `SaleStoreIdempotencyTest` (8, 48 assertions), and `SaleDteDebugChannelTest` (1, 50 assertions). Pest warned on each test because `.env` is missing (`file_get_contents` of `/workspace/.env`). No assertion failed. The two extra assertions in the debug test read `DocumentService.php` and require zero bare `Log::` calls.

## ART-LOG-02b firmado hotfix

Mark's smoke failed on firmado after ART-LOG-02. `DocumentService` hunk #2 was rejected, then applied by hand. Hunk #1 had already removed `use Illuminate\Support\Facades\Log`. A leftover `Log::debug('URL firmador', ...)` still ran. In `namespace App\Services` that name is `App\Services\Log`. `generarDTE` returned 500 with `Class "App\Services\Log" not found`.

Clean `main` has `Log::debug('Puerto Certificado', $port)` and `Log::debug('Payload para firmar documento', $payload)` only. ART-LOG-02 rewrites both to `SaleDteLog::debug('Puerto Certificado', ['port' => $port['port']])` and deletes the payload log. The DocumentService hunk now stops on the blank line after that payload call, so the extra URL line no longer makes hunk #2 reject. `git apply --check` of ART-LOG-02 passed on `main` + ART-0925-07, on `main` + ART-0925-04 + ART-0925-07, and on `main` + ART-0925-07 with the URL line inserted after the payload log.

02b is separate. The URL line is not on clean `main`, so folding the deletion into ART-LOG-02 would make that patch fail there.

`SaleController` hunk #7 (`Venta creada`) was the other reject Mark applied by hand. Those calls are `\Log::`, which is the global facade, so that reject does not cause the firmado class error.

After 07, 02, and 02b, `signDocument` keeps `SaleDteLog` only. The port is logged. The payload and the firmador URL are not.

Deleted by ART-LOG-02b:

```php
        Log::debug('URL firmador', [
            'url' => "http://{$host}:{$port['port']}/firmardocumento/",
        ]);
```

The blank line under that call is deleted with it. `use Illuminate\Support\Facades\Log` stays removed.

With that call still present, `SaleDteDebugChannelTest` got HTTP 500 and `{"error":"Error generando DTE","message":"Class \"App\\Services\\Log\" not found"}`. After 02b the same test passed: 1 test, 50 assertions, exit code 0.

## Smoke for Mark

Re-smoke firmado after 02b. The sale should sign. `mh_message` should not contain `Class "App\Services\Log" not found`.

```bash
grep -nE '(^|[^[:alnum:]_])Log::' app/Services/DocumentService.php
grep -n 'SaleDteLog::' app/Services/DocumentService.php
```

The first command should print nothing. `SaleDteLog::` is the only logger in that class.

After a sale in the app:

```bash
grep 'gestock.sale' storage/logs/gestock-sale-dte.log
grep 'gestock.dte' storage/logs/gestock-sale-dte.log
grep '"sale_id":SALE_ID' storage/logs/gestock-sale-dte.log
grep -E 'eyJ|api_key|passwordPri|<?xml|<Respuesta|identificacion|"token":' storage/logs/gestock-sale-dte.log
```

Replace `SALE_ID` with the sale. The first three commands should show the create line and the DTE attempt in this file, not only in `laravel.log`. The last command should print nothing for that sale. `token_preview` may appear. It is a hash prefix, not the Hacienda token.
