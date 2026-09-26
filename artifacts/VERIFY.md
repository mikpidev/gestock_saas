# ART-0925-03 — DTE status comparison

Patch: `artifacts/ART-0925-03.patch`  
Base: `main` (`ed32662`, `change env to config service`)  
No pull request. Apply this patch on `main` after ART-0925-01 and ART-0925-02.

## What was wrong

`SaleController` saved Hacienda's real `dte_status`, then evaluated it with assignment:

```php
if ($sale->dte_status = 'PROCESADO') {
```

In PHP that writes `PROCESADO` onto the model and the condition is always true. The row was already saved, so the database kept `PENDIENTE` (or `RECHAZADO`) while the AJAX JSON returned the in-memory `PROCESADO`, and `OCIController::emailSend` ran for every consult.

The same assignment gated mail in `CreditNoteController::refreshDTE` and `DebitNoteController::refreshDTE`.

Success status in this codebase is the string `PROCESADO`. `sales.dte_status` is a string with default `PENDIENTE` (migration `2025_10_29_024745`). The screens and queries also use `RECHAZADO`. The old `paid` / `unpaid` / `partial` enum is not the live DTE status.

## What the patch changes

- `SaleController::refreshDTE` and `SaleController::store`: `=` became `===` before `emailSend`.
- `SaleController::store` resolves `ConsultaService` with `app(ConsultaService::class)` instead of `new`. Production still gets a new instance (the class is not a singleton). The container resolution is what lets the create-path test substitute Hacienda's `estado`. `refreshDTE` already receives that service from the container.
- Credit-note and debit-note refresh use `===` on the same mail gate. Their `store` methods already save `$response['estado']` and do not send mail.
- `tests/Feature/DteStatusComparisonTest.php` covers sale create and sale refresh for `PROCESADO`, `PENDIENTE`, and `RECHAZADO`.

Mail is called only when the consulted status is `PROCESADO`. `PENDIENTE` and `RECHAZADO` persist as returned and do not send mail. The create response `dte_status` matches the saved row.

## Apply

From a clean `main`:

```bash
git apply artifacts/ART-0925-03.patch
php artisan test --filter=DteStatusComparisonTest
```

`company.php` and `store.php` do not match PSR-4 on a case-sensitive filesystem. The test loads those files only when the classes are not already autoloadable.

## Tests

Command:

```bash
php artisan test --filter=DteStatusComparisonTest
```

Environment: PHP 8.4.26, Pest 4.6.0, `phpunit.xml` sqlite `:memory:`.

Result (exit 0):

```
PASS  Tests\Feature\DteStatusComparisonTest
✓ it keeps the Hacienda status on sale create and emails only when PROCESADO  (PROCESADO)
✓ it keeps the Hacienda status on sale create and emails only when PROCESADO  (PENDIENTE)
✓ it keeps the Hacienda status on sale create and emails only when PROCESADO  (RECHAZADO)
✓ it keeps the Hacienda status on DTE refresh and emails only when PROCESADO  (PROCESADO)
✓ it keeps the Hacienda status on DTE refresh and emails only when PROCESADO  (PENDIENTE)
✓ it keeps the Hacienda status on DTE refresh and emails only when PROCESADO  (RECHAZADO)

Tests:    6 passed (48 assertions)
Duration: 0.45s
```

Restoring `=` on sale create made the suite fail the way Mark saw it: JSON `dte_status` was `PROCESADO` when Hacienda returned `PENDIENTE`, and `PROCESADO` when Hacienda returned `RECHAZADO`. Restoring `=` on refresh made `emailSend` run once for `PENDIENTE` and once for `RECHAZADO` (expected 0). With `===` restored, all 6 tests passed.

## Not in this patch

- Company, store, correlativos, and dashboard policies (ART-0925-01).
- Sale `store_id`, `exists`, and server price (ART-0925-02).
- Idempotency-Key (ART-0925-04) and pagination-data (ART-0925-05).
- `StoreController` around line 130 still has `$dte_status = 'PROCESADO' ?? $request->dte_status`. That is a dashboard filter, not the sale mail gate. A non-null left operand makes `??` ignore the request. Left unchanged.
