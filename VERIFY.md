# VERIFY — store plans and DTE / revenue quotas

Branch: `cursor/store-plans-dte-quotas-6e03`.

## What changed

Plan moved from the company to the store. Enum on `stores.plan`: `starter | basic | premium | empresarial`. There is no store plan named `free`.

Legacy company plans are remapped when the migration runs:

| companies.plan (legacy) | stores.plan |
| --- | --- |
| free | basic |
| basic | premium |
| premium | empresarial |

Self-service `starter` (label **Starter**) is the new lowest tier. It is not the old company free tier. Creating a store defaults to `basic`. `starter` is stored only when a superadmin sends it. Validation accepts `starter,basic,premium,empresarial` only.

`config/plans.php` (USD list price, IVA not included — +IVA aparte — no billing-rate migration, no +$25 addon). Starter is **$25 annual one-shot** (`billing_period: annual_one_shot`, `billing_label=config_fee` — fee de configuración, not MRR; limited support), same IVA treatment as the paid plans. Quotas are 50 DTE / month and $10000 processed sales / year.

| plan | label | price_usd | billing_period | includes_iva | dte_monthly_limit | annual_revenue_limit |
| --- | --- | --- | --- | --- | --- | --- |
| starter | Starter | 25 | annual_one_shot | false | 50 | 10000 |
| basic | 25 | | false | 200 | null |
| premium | 40 | | false | 1000 | null |
| empresarial | 75 | | false | null (unlimited) | null |

Quotas are per store. `stores.dte_monthly_limit` is a nullable override seeded from config on create, on plan change, and on backfill. A non-null column wins over config. A null column falls back to config. A null config value means no DTE cap (empresarial). The HTTP forms do not accept a posted `dte_monthly_limit`; a forged value is ignored.

## Files

Created:

- `config/plans.php`
- `database/migrations/2026_09_28_180000_move_plan_to_stores.php`
- `app/Services/DteQuotaService.php`
- `tests/Feature/StorePlanAndDteQuotaTest.php`
- `resources/views/store/_dte_usage.blade.php`
- `VERIFY.md`

Updated:

- `app/Models/store.php` — fillable `plan`, `dte_monthly_limit`
- `app/Models/company.php` — `plan` removed from fillable
- `app/Policies/StorePolicy.php` — `create` is superadmin + selected company; `updatePlan` is superadmin + selected company; `update` still lets an admin change other fields
- `app/Http/Controllers/StoreController.php` — default plan `basic`; plan validated only for superadmin; admin POST cannot change plan or the limit; `dashboard` passes `dteUsage`; `dteUsage` returns the JSON summary
- `routes/web.php` — `GET /stores/{store}/dte-usage` named `stores.dte-usage`
- `resources/views/store/dashboard.blade.php`, `resources/views/store/_dte_usage.blade.php` — month usage banner (red on critical, yellow on warn)
- `app/Http/Controllers/CompanyController.php` — plan validation removed
- `app/Http/Controllers/DTEController.php` — quota check at the start of `generarDTE`, `generarDTECreditNote`, and `generarDTEDebitNote`, before signing
- `app/Http/Controllers/SaleController.php`, `CreditNoteController.php`, `DebitNoteController.php` — a 422 from those methods is returned (store) or flashed (refresh)
- `resources/views/store/_form.blade.php`, `resources/views/store/index.blade.php` — plan select only named for superadmin; “Nueva tienda” only for superadmin
- `resources/views/company/_form.blade.php`, `edit.blade.php`, `index.blade.php`, `show.blade.php` — company plan removed; “Agregar Tienda” only for superadmin
- `composer.json` — file autoload for `app/Models/company.php` and `app/Models/store.php` (class names do not match the lowercase filenames, so Linux `artisan migrate` could not load `Store` for the existing correlativos migration)
- Feature fixtures that wrote `companies.plan` — key removed so inserts match the dropped column

Not edited: `resources/views/landing/*`.

## Migration

```bash
composer dump-autoload
php artisan migrate
```

`2026_09_28_180000_move_plan_to_stores`:

1. Add `stores.plan` enum `starter|basic|premium|empresarial` default `basic`.
2. Add nullable `stores.dte_monthly_limit`.
3. For each company, copy `companies.plan` through the remap above onto every store of that company and set `dte_monthly_limit` from `config/plans.php`.
4. Drop `companies.plan`.

`down()` is lossy. It puts `companies.plan` back from the first store of each company (`starter` and `basic` both become company `free`, `premium` becomes `basic`, `empresarial` becomes `premium`) and drops the store columns. Two stores on one company can disagree; only the lowest store id is kept.

Run this before the app serves traffic. Existing rows get the remap. New stores get `basic` unless a superadmin picks another plan.

## Quota rules

Timezone: `America/Guatemala`. Month and year bounds are converted to UTC before the query (`APP_TIMEZONE` is UTC). The column used is `created_at`, not `sale_date` / `credit_note_date` / `debit_note_date`.

DTE count (current calendar month):

- `sales` + `credit_notes` + `debit_notes`
- `dte_status === 'PROCESADO'`
- same `store_id`
- soft-deleted rows excluded (`sales` and `credit_notes` use SoftDeletes; `debit_notes` has `deleted_at` and the quota query adds `whereNull('deleted_at')` because the model does not use the trait)

`credit_notes.dte_status` and `debit_notes.dte_status` are strings defaulting to `PENDIENTE`, same idea as `sales.dte_status`. Both are included.

Soft block when `used >= limit`. Empresarial, or a resolved null limit, does not cap DTE.

Starter only: annual revenue is `sum(sales.total_amount)` for `PROCESADO` sales in the Guatemala calendar year. Block when the sum is `>= 10000`. Credit notes and debit notes are not subtracted. `includes_iva` is stored as `false` and is not applied to the sum.

The check runs before signing. Over quota returns HTTP 422:

```json
{ "message": "...", "error": "dte_monthly_limit|annual_revenue_limit", "used": 0, "limit": 0 }
```

`SaleController::store` returns that 422. The sale row is already committed (soft block) and stays `PENDIENTE`. Credit-note and debit-note `store` do the same. `refreshDTE` redirects back with an error flash instead of a 422, because those actions are form posts. The dashboard usage endpoint does not change that soft 422.

## Dashboard usage

`GET /stores/{store}/dte-usage` (`stores.dte-usage`) returns JSON after `authorize('view', $store)` (same rule as the store dashboard). The store dashboard renders the same payload.

| field | meaning |
| --- | --- |
| used | PROCESADO sales + credit notes + debit notes in the current Guatemala month |
| limit | `stores.dte_monthly_limit` when set, otherwise `config/plans.{plan}.dte_monthly_limit` |
| remaining | `null` when unlimited, otherwise `max(0, limit - used)` |
| plan | `stores.plan` |
| pct | `null` when unlimited, otherwise `round(used / limit * 100)` (a limit of 0 is 100) |
| warning_level | `ok`, `warn`, or `critical` |
| message | upgrade hint plus contactar soporte when `warn` or `critical`; otherwise `null` |

Thresholds:

- Unlimited limit (`null`, empresarial) → `ok`, `pct` null, `remaining` null, `message` null.
- `pct >= 80`, or plan `starter` with `used >= 40` → `critical`. Starter at 40/50 is red. The upgrade hint is Starter → Basic (200 DTE/mes) and contactar soporte.
- `pct >= 60` → `warn`.
- Otherwise `ok`.

The dashboard shows a red badge and “Contactar soporte” (`/#contacto`) when `warning_level` is `critical`, and a yellow badge with the same CTA when it is `warn`. Emission stays a soft 422; this route only reports usage.

## Authorization

Spatie roles `superadmin | admin | user`.

- Create store: superadmin, and only for `session('selected_company_id')`.
- Change `plan`: superadmin via `StorePolicy::updatePlan`, same selected-company check. Saving a plan reseeds `dte_monthly_limit` from config.
- Admin may update name, status, environment, and the other store fields. A forged `plan` or `dte_monthly_limit` is dropped and the rest of the update is saved.
- “Nueva tienda” and the plan `<select name="plan">` render only for superadmin. Admins see the plan disabled, with no `name`, so the browser does not submit it.

## How to test

```bash
php artisan test tests/Feature/StorePlanAndDteQuotaTest.php
php artisan test tests/Feature/TenantAuthorizationTest.php
php artisan test tests/Feature/SaleStoreHardeningTest.php
```

Covered:

- Config values and columns (`companies.plan` gone, `stores.plan` present, no `plans.free`). Starter label is `Starter`, `price_usd` 25, `billing_period` `annual_one_shot`, `billing_label` `config_fee`, `includes_iva` false, DTE limit 50, annual revenue limit 10000.
- DB default `basic` when a store is inserted without a plan.
- Migration remap free→basic (limit 200), basic→premium (1000), premium→empresarial (null), including two stores on one company, then drop `companies.plan`.
- Admin and user cannot create a store. Superadmin cannot create outside the selected company. Omitted plan becomes `basic` / 200. Explicit `starter` becomes `starter` / 50. Posted `plan=free` fails validation. A posted limit is ignored.
- Admin update renames the store and keeps `premium` / 1000 when the POST forges `plan=starter` and `dte_monthly_limit=1`. Superadmin plan change to `starter` reseeds the limit to 50 and ignores a posted limit.
- Company create ignores a posted plan. Store index hides “Nueva tienda” and `name="plan"` for admin, shows both for superadmin. Company index has no plan field. `company.show` hides “Agregar Tienda” for admin.
- Guatemala month bounds: a sale at `2026-10-01 02:00 UTC` is still September. Counts include one processed sale, one credit note, and one debit note; pending, rejected, other-store, previous-month, and next-month rows do not count. Soft-deleted sales drop out. `used == limit` blocks; `used == limit - 1` does not.
- Starter revenue uses processed sales in the Guatemala year (10000 blocks, 9001 does not). A credit note total is not added. Premium with a large total is not revenue-gated. Empresarial with a null limit is not DTE-gated.
- `generarDTE`, `generarDTECreditNote`, and `generarDTEDebitNote` return 422 and do not call the document signer when the store is over the monthly cap. Under the cap, signing is reached.
- `POST stores/{store}/sales` returns 422 with `error=dte_monthly_limit` when the store is over quota, leaves the new sale `PENDIENTE`, and returns 200 for a store under quota.
- `GET stores/{store}/dte-usage` is 403 for a user with no role, another company’s admin, and a superadmin without `selected_company_id`. Admin, user, and a superadmin on the selected company receive the JSON summary.
- Usage levels: 0/50 starter is `ok`; 30/50 is `warn` (60%); 40/50 starter is `critical` with a Basic 200 DTE/mes upgrade message. The dashboard HTML includes the red badge and “Contactar soporte”. A starter store with a 100 override is `critical` at 40 used even though pct is 40. Empresarial with a null limit stays `ok` with null pct, remaining, and message.

## Test run (this environment)

PHP 8.4. SQLite (no MySQL server here). `phpunit.xml` still targets `mysql` / `gestock_testing`; these runs set `DB_CONNECTION=sqlite`.

```text
SaleStoreHardeningTest, StorePlanAndDteQuotaTest, TenantAuthorizationTest
Tests: 24 passed (210 assertions) before the usage endpoint.
```

`StorePlanAndDteQuotaTest` after the starter rename: 11 passed (180 assertions). `TenantAuthorizationTest` dashboard case still returned 200 for the right tenant on an earlier sqlite file.

`StoreDashboardDteStatusTest` no longer writes `companies.plan`. On a fresh SQLite database its fixture user has no Spatie role, so `stores.dashboard.data` returns 403 from `StorePolicy::view`. That 403 is outside this quota change.

## Known gaps

- `generarDTEContingencia` is not quota-checked.
- A document that is already `PROCESADO` counts toward the cap, so a retry of that same document can 422.
- Annual revenue is gross processed sales. Notes do not reduce it. IVA is not added.
- `refreshDTE` for sales, credit notes, and debit notes tells the user with a flash error and a redirect, not HTTP 422.
- `companies.show` redirects to the store index, so the live create-store control is “Nueva tienda” on `stores/index`. The company show button is gated anyway.
- The plan `<select>` in the edit modal is filled by the existing row script. With JavaScript off, a superadmin save can submit the default `basic`.
- `down()` cannot tell a new starter store from a legacy free store that became basic. Both write company `free`.
- List prices in `config/plans.php` are catalog data only. Starter is $25 annual one-shot +IVA (`billing_period: annual_one_shot`, `billing_label=config_fee`); that price is not invoiced from this patch, and the old $30 rate is not migrated.
- Landing pages were left unchanged.
