# ART-0925-06 — dashboard `dte_status` filter

## What changed

`StoreController::getChartData` (route `stores.dashboard.data`) filtered with:

```php
$dte_status = 'PROCESADO' ?? $request->dte_status;
```

`'PROCESADO'` is never null, so the request value was ignored and charts always used `PROCESADO`.

The filter is now:

```php
$dte_status = $request->dte_status ?? 'PROCESADO';
```

Only `getChartData` was changed. `DownloadPDF` already used the same expression. Sale DTE generation, MH submission, and mail gating were not touched.

## Default when `dte_status` is omitted

**`PROCESADO`.**

That is the default already stated on this method (“ventas aceptadas por Hacienda”) and already implemented by `DownloadPDF` in the same controller. Laravel’s `ConvertEmptyStringsToNull` middleware also turns an empty `dte_status` into null, so a blank value uses the same default. Any non-empty request value is used as-is.

## Apply

From the repo root, on `main`:

```bash
git apply artifacts/ART-0925-06.patch
```

Patch base: `main` (`ed32662`). The patch contains `app/Http/Controllers/StoreController.php` and `tests/Feature/StoreDashboardDteStatusTest.php` only.

Checked on the fix branch with `git apply --reverse --check artifacts/ART-0925-06.patch` (exit 0).

## Commands and results

Environment for this run: PHP 8.4.26 (lockfile requires `>= 8.4`), `composer install`, a local gitignored `.env` containing only `APP_KEY` (phpunit.xml already sets sqlite `:memory:`).

```bash
php artisan test tests/Feature/StoreDashboardDteStatusTest.php
```

```
PASS  Tests\Feature\StoreDashboardDteStatusTest
✓ dashboard chart data filters sales by the requested dte_status          0.21s
✓ dashboard chart data defaults dte_status to PROCESADO when the request omits it  0.01s

Tests:    2 passed (6 assertions)
Duration: 0.26s
```

Counter-check with the old expression restored: the requested-status test failed. `dte_status=RECHAZADO` still returned the PROCESADO sale total (`40.0` instead of `15.5`). Exit code 1.

## Env notes (not fixed here)

- `app/Models/store.php` declares `Store` and `app/Models/company.php` declares `Company`. On Linux, PSR-4 will not autoload those paths. The new test `require_once`s both files so RefreshDatabase can run `2026_07_11_172001_insert_correlativos_existing_stores.php` (`Store::chunk`) and so `CheckCompanyStatus` can read `user->company`. The filenames were not renamed.
- `getChartData` peak hours call MySQL `HOUR(CONVERT_TZ(...))`. The test registers sqlite stand-ins. The production query was not changed.
- With no `.env` file, phpdotenv warns `file_get_contents(.env): Failed to open stream` once per test. Assertions still run. A local `.env` with `APP_KEY` removes the warning.
