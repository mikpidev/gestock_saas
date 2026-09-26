# ART-LOG-03 DTE mail channel

Patch file `artifacts/ART-LOG-03.patch`.
Base is `main` at `ed32662` after `artifacts/ART-0925-07.patch` and `artifacts/ART-LOG-02.patch`.
No pull request was opened. Application files are not committed on this branch. Miguel applies the patch locally.

`git apply --check` of this patch succeeded on `main` + ART-0925-07 + ART-LOG-02.
The same check succeeded on `main` + ART-0925-04 + ART-0925-07 + ART-LOG-02. Apply 04 first when you want the replay line. This patch does not add idempotency.

ART-LOG-02 already deletes the firmador payload `Log::debug` in `DocumentService`. No extra 02b hotfix was folded in.

## Apply

```bash
git apply artifacts/ART-0925-07.patch
git apply artifacts/ART-LOG-02.patch
git apply --check artifacts/ART-LOG-03.patch
git apply artifacts/ART-LOG-03.patch
php artisan test --filter=MailChannelTest
php artisan test --filter=SaleDteDebugChannelTest
```

With the replay line:

```bash
git apply artifacts/ART-0925-04.patch
git apply artifacts/ART-0925-07.patch
git apply artifacts/ART-LOG-02.patch
git apply artifacts/ART-LOG-03.patch
php artisan test --filter=MailChannelTest
php artisan test --filter=SaleDteDebugChannelTest
php artisan test --filter=SaleStoreLoggingTest
php artisan test --filter=SaleStoreIdempotencyTest
```

## Channel

`config/logging.php` adds a stack that is not part of the default `LOG_STACK` and not part of `sale_dte`.

| Name | Driver | Writes to |
| --- | --- | --- |
| `gestock_mail` | stack | `gestock_mail_file` only |
| `gestock_mail_file` | single | `storage/logs/gestock-mail.log` |

Level is `LOG_GESTOCK_MAIL_LEVEL`, default `debug`.

`sale_dte` still writes only `storage/logs/gestock-sale-dte.log`. `gestock.sale` and `gestock.dte` are unchanged.

## One mail line

`App\Support\MailLog::attempt` writes one `gestock.mail` line. Fields are `sale_id`, `dte_status`, `correlativo`, `resultado` (`sent`, `failed`, or `skipped`), optional `http_status`, and optional `error`.

`correlativo` is the integer at the end of `numero_control`. `http_status` is the HTTP status `emailSend` returns (200 sent, 422 no address, 400 unsupported tipo, 403 wrong store, 500 failed). `error` is at most 80 characters. A message that contains `<`, `{`, `eyJ`, or `@` is stored as `omitted`.

The line does not include customer email, from address, customer name, DTE JSON, XML, or tokens.

`OCIController::emailSend`, `emailSendNc`, and `emailSendNd` each write that one line in a `finally` block, including when the firmador is down and the caller still reaches `emailSend`. `OCIService::emailSubmissionToOCI` no longer logs `from` or `to`. It still throws `No se pudo enviar el correo por OCI`.

`SaleController` `store` and `refreshDTE` catch mail errors with `MailLog`, not `SaleDteLog`. That catch runs when `emailSend` throws. The normal send path is caught inside `emailSend`, so that failure is the single `gestock.mail` line and does not also land in `gestock-sale-dte.log`.

Credit note and debit note controllers still have their old default-channel catch. ART-LOG-02 did not move those, and `emailSend` does not rethrow.

## Commands and results

Run from a checkout with PHP 8.4.26 after ART-0925-07, ART-LOG-02, and this patch. `phpunit.xml` from ART-0925-07 sets `APP_KEY`.

```bash
php artisan test --filter=MailChannelTest
php artisan test --filter=SaleDteDebugChannelTest
```

Measured on PHP 8.4.26. No live Ministerio de Hacienda call and no live SMTP call were made.

`MailChannelTest` finished with 5 tests and 132 assertions. Exit code 0.
`SaleDteDebugChannelTest` finished with 1 test and 48 assertions. Exit code 0.
Pest warned on each test because `.env` is missing (`file_get_contents` of `/workspace/.env`). No assertion failed.

The mail tests covered:

- `gestock_mail` is not in the default stack and not in `sale_dte`. A `gestock.mail` line is in the mail file only. A `gestock.sale` line is in the sale-dte file only. A default stack line stays in the default file.
- `emailSend` with `Mail::fake()`, a fake PDF, and a DTE JSON that contains `pii-customer@example.test`, `from-secret@example.test`, `NombreSecretoPii`, and `DTEJSONSECRET` wrote one line: `resultado=sent`, `http_status=200`, `sale_id`, `dte_status=PROCESADO`, `correlativo=15`. Those secrets were absent from the mail file, the sale-dte file, the default file, and the in-memory listener.
- A customer with no address wrote one `resultado=skipped` line with `http_status=422` and did not write the customer name.
- A throwing mailer whose message contained the customer address, a JWT (`eyJ`), and XML wrote one `resultado=failed` line with `http_status=500` and `error` equal to `No se pudo enviar el correo por OCI`. The address, the from address, the JWT, and the XML were absent from every captured channel. The sale-dte file did not contain `gestock.mail`.
- `POST /stores/{store}/sales` with `emailSend` throwing a message that contains the address, a JWT, and XML wrote one `gestock.mail` line with `resultado=failed`, `sale_id`, `dte_status=PROCESADO`, `correlativo`, and `error=omitted`. `gestock-sale-dte.log` did not contain `gestock.mail`, `Error Enviando correo`, the address, or the JWT.

`SaleStoreLoggingTest` without ART-0925-04: the long-key test and the `generarDTE` test passed. The created-then-replay test failed with sale count 2, which is the same result ART-LOG-02 documents on raw `main`.

## Smoke for Mark

After a sale in the app (create, replay, MH, then the mail attempt):

```bash
grep 'gestock.mail' storage/logs/gestock-mail.log
grep '"sale_id":SALE_ID' storage/logs/gestock-mail.log
grep -E '"resultado":"(sent|failed|skipped)"' storage/logs/gestock-mail.log
grep 'gestock.mail' storage/logs/gestock-sale-dte.log
grep -E 'eyJ|<\?xml|@|identificacion|"token":|"from"|"to"' storage/logs/gestock-mail.log
grep 'gestock.sale' storage/logs/gestock-sale-dte.log
grep 'gestock.dte' storage/logs/gestock-sale-dte.log
```

Replace `SALE_ID` with the sale. The mail file should have one `gestock.mail` line for that attempt, with `sale_id`, `dte_status`, `correlativo`, and `resultado`. The `gestock.mail` grep of `gestock-sale-dte.log` should print nothing. The secret grep of `gestock-mail.log` should print nothing. `gestock.sale` and `gestock.dte` stay in `gestock-sale-dte.log`.

If the firmador on port 8115 is down, the mail line is still written when the request reaches `emailSend`.
