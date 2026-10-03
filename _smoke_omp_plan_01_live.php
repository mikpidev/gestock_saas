<?php
/**
 * OMP-PLAN-01 live HTTP smoke for PR#5 store plans + DTE quotas.
 * Seeds isolated smoke stores, then hits http://127.0.0.1:8001 with session cookies.
 */
declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\CorrelativoStore;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\ProductType;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Services\DteQuotaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

const BASE = 'http://127.0.0.1:8001';
const COOKIE_JAR = __DIR__.'/_smoke_cookies.txt';
const REPORT_PC = 'C:\\Users\\mpineda\\Downloads\\OMP-PLAN-01-saas-pr5-live-http.json';
const REPORT_BOX_COPY = __DIR__.'/_OMP-PLAN-01-saas-pr5-live-http.json';

function sha(): string {
    return trim((string) shell_exec('git -C '.escapeshellarg(__DIR__).' rev-parse HEAD'));
}

function cookieValue(string $name): ?string {
    if (!is_file(COOKIE_JAR)) {
        return null;
    }
    foreach (file(COOKIE_JAR) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = preg_split('/\s+/', $line);
        if (count($parts) >= 7 && $parts[5] === $name) {
            return urldecode($parts[6]);
        }
    }
    return null;
}
function http(string $method, string $path, array $opts = []): array {
    $url = BASE.$path;
    $ch = curl_init($url);
    $headers = $opts['headers'] ?? [];
    $headers[] = 'Accept: '.($opts['accept'] ?? 'application/json');
    if (strtoupper($method) !== 'GET') {
        $xsrf = cookieValue('XSRF-TOKEN');
        if ($xsrf) {
            $headers[] = 'X-XSRF-TOKEN: '.$xsrf;
        }
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => $opts['follow'] ?? false,
        CURLOPT_COOKIEJAR => COOKIE_JAR,
        CURLOPT_COOKIEFILE => COOKIE_JAR,
        CURLOPT_TIMEOUT => 60,
    ]);
    if (!empty($opts['form'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($opts['form']));
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }
    if (array_key_exists('json', $opts)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($opts['json']));
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'X-Requested-With: XMLHttpRequest';
    }
    if (!empty($opts['raw'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['raw']);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $header = substr((string)$raw, 0, $headerSize);
    $body = substr((string)$raw, $headerSize);
    return [
        'status' => $status,
        'headers' => $header,
        'body' => $body,
        'json' => json_decode($body, true),
        'error' => $err,
    ];
}

function extractCsrf(string $html): ?string {
    if (preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    if (preg_match("/csrf-token\"\s+content=\"([^\"]+)\"/", $html, $m)) {
        return $m[1];
    }
    // XSRF cookie
    if (is_file(COOKIE_JAR)) {
        $lines = file(COOKIE_JAR);
        foreach ($lines as $line) {
            if (str_contains($line, 'XSRF-TOKEN')) {
                $parts = preg_split('/\s+/', trim($line));
                $val = urldecode($parts[count($parts)-1] ?? '');
                return $val !== '' ? $val : null;
            }
        }
    }
    return null;
}

function login(string $email, string $password): array {
    @unlink(COOKIE_JAR);
    $page = http('GET', '/login', ['accept' => 'text/html', 'follow' => true]);
    $token = extractCsrf($page['body'] ?? '');
    if (!$token) {
        return ['ok' => false, 'detail' => 'no csrf', 'page_status' => $page['status']];
    }
    $resp = http('POST', '/login', [
        'accept' => 'text/html',
        'follow' => false,
        'form' => [
            '_token' => $token,
            'email' => $email,
            'password' => $password,
        ],
    ]);
    $ok = in_array($resp['status'], [302, 303], true) || ($resp['status'] === 200 && !str_contains($resp['body'], 'credentials'));
    return ['ok' => $ok, 'status' => $resp['status'], 'location' => (preg_match('/^Location:\s*(.+)$/mi', $resp['headers'], $m) ? trim($m[1]) : null)];
}

function ensureRole(string $name): void {
    Role::findOrCreate($name, 'web');
}

function makeUser(string $email, string $role, int $companyId, ?int $storeId): User {
    ensureRole($role);
    $user = User::query()->where('email', $email)->first();
    if (!$user) {
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('Password2025*'),
            'company_id' => $companyId,
            'store_id' => $storeId,
        ]);
    } else {
        $user->forceFill([
            'password' => Hash::make('Password2025*'),
            'company_id' => $companyId,
            'store_id' => $storeId,
        ])->save();
    }
    $user->syncRoles([$role]);
    return $user->fresh();
}

function mkSale(Store $store, User $user, array $attrs = [], ?Carbon $createdAt = null): Sale {
    $sale = new Sale(array_merge([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'sale_date' => Carbon::now('America/Guatemala')->toDateString(),
        'total_amount' => 10,
        'net_amount' => 10,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'dte_status' => 'PROCESADO',
        'tipo_documento_id' => 1,
        'payment_method' => 'Efectivo',
    ], $attrs));
    $sale->created_at = $createdAt ?? Carbon::now('America/Guatemala');
    $sale->updated_at = $sale->created_at;
    $sale->save();
    return $sale;
}

echo "Seeding smoke fixtures...\n";

$company = Company::query()->firstOrCreate(
    ['email' => 'smoke-omp-plan-01@example.test'],
    [
        'company_name' => 'SMOKE-OMP-PLAN-01',
        'address' => 'Smoke Ave 1',
        'phone' => '22220000',
        'owner' => 'Smoke Owner',
        'deployment_type' => 'saas',
        'status' => 'activa',
        'comments' => 'smoke isolated',
    ]
);

$foreignCompany = Company::query()->firstOrCreate(
    ['email' => 'smoke-omp-plan-01-foreign@example.test'],
    [
        'company_name' => 'SMOKE-OMP-PLAN-01-FOREIGN',
        'address' => 'Foreign Ave',
        'phone' => '22221111',
        'owner' => 'Foreign',
        'deployment_type' => 'saas',
        'status' => 'activa',
        'comments' => 'smoke foreign',
    ]
);

// Clean prior smoke docs for repeatability (sales/notes on smoke stores only)
$existingIds = Store::query()->where('email', 'like', 'smoke-omp-plan-01%@example.test')->pluck('id');
if ($existingIds->isNotEmpty()) {
    CreditNote::withTrashed()->whereIn('store_id', $existingIds)->forceDelete();
    DebitNote::query()->whereIn('store_id', $existingIds)->delete();
    Sale::withTrashed()->whereIn('store_id', $existingIds)->forceDelete();
    ProductType::withTrashed()->whereIn('store_id', $existingIds)->forceDelete();
    CorrelativoStore::query()->whereIn('store_id', $existingIds)->delete();
    Store::withTrashed()->whereIn('id', $existingIds)->forceDelete();
}

$monthlyStore = Store::query()->create([
    'company_id' => $company->id,
    'store_name' => 'SMOKE Monthly Quota',
    'establecimiento' => 'S001',
    'punto_venta' => 'P001',
    'address' => 'Smoke 1',
    'phone' => '22220001',
    'manager' => 'Mgr',
    'email' => 'smoke-omp-plan-01-monthly@example.test',
    'status' => 'activa',
    'environment' => 'Development',
    'plan' => 'basic',
    'dte_monthly_limit' => 2,
]);

$annualStore = Store::query()->create([
    'company_id' => $company->id,
    'store_name' => 'SMOKE Annual Revenue',
    'establecimiento' => 'S002',
    'punto_venta' => 'P001',
    'address' => 'Smoke 2',
    'phone' => '22220002',
    'manager' => 'Mgr',
    'email' => 'smoke-omp-plan-01-annual@example.test',
    'status' => 'activa',
    'environment' => 'Development',
    'plan' => 'starter',
    'dte_monthly_limit' => 50,
]);

$saleBlockStore = Store::query()->create([
    'company_id' => $company->id,
    'store_name' => 'SMOKE Sale Block',
    'establecimiento' => 'S003',
    'punto_venta' => 'P001',
    'address' => 'Smoke 3',
    'phone' => '22220003',
    'manager' => 'Mgr',
    'email' => 'smoke-omp-plan-01-saleblock@example.test',
    'status' => 'activa',
    'environment' => 'Development',
    'plan' => 'starter',
    'dte_monthly_limit' => 1,
]);

$criticalStore = Store::query()->create([
    'company_id' => $company->id,
    'store_name' => 'SMOKE Critical 40/50',
    'establecimiento' => 'S004',
    'punto_venta' => 'P001',
    'address' => 'Smoke 4',
    'phone' => '22220004',
    'manager' => 'Mgr',
    'email' => 'smoke-omp-plan-01-critical@example.test',
    'status' => 'activa',
    'environment' => 'Development',
    'plan' => 'starter',
    'dte_monthly_limit' => 50,
]);

$authzStore = Store::query()->create([
    'company_id' => $company->id,
    'store_name' => 'SMOKE Authz',
    'establecimiento' => 'S005',
    'punto_venta' => 'P001',
    'address' => 'Smoke 5',
    'phone' => '22220005',
    'manager' => 'Mgr',
    'email' => 'smoke-omp-plan-01-authz@example.test',
    'status' => 'activa',
    'environment' => 'Development',
    'plan' => 'starter',
    'dte_monthly_limit' => 50,
]);

$foreignStore = Store::query()->create([
    'company_id' => $foreignCompany->id,
    'store_name' => 'SMOKE Foreign',
    'establecimiento' => 'F001',
    'punto_venta' => 'P001',
    'address' => 'Foreign',
    'phone' => '22229999',
    'manager' => 'Mgr',
    'email' => 'smoke-omp-plan-01-foreign-store@example.test',
    'status' => 'activa',
    'environment' => 'Development',
    'plan' => 'basic',
    'dte_monthly_limit' => 200,
]);

$admin = makeUser('smoke-omp-admin@example.test', 'admin', (int)$company->id, (int)$monthlyStore->id);
$cashier = makeUser('smoke-omp-user@example.test', 'user', (int)$company->id, (int)$monthlyStore->id);
$stranger = makeUser('smoke-omp-stranger@example.test', 'admin', (int)$foreignCompany->id, (int)$foreignStore->id);
$norole = User::query()->where('email', 'smoke-omp-norole@example.test')->first();
if (!$norole) {
    $norole = User::factory()->create([
        'email' => 'smoke-omp-norole@example.test',
        'password' => Hash::make('Password2025*'),
        'company_id' => $company->id,
        'store_id' => $authzStore->id,
    ]);
} else {
    $norole->forceFill(['company_id' => $company->id, 'store_id' => $authzStore->id, 'password' => Hash::make('Password2025*')])->save();
}
$norole->syncRoles([]); // no role

$sa = User::query()->where('email', 'yo@gestok.com')->first();
if (!$sa) {
    throw new RuntimeException('yo@gestok.com missing');
}

$nowGt = Carbon::now('America/Guatemala');
$quota = app(DteQuotaService::class);

// --- Check 1 fixtures: sale + NC + ND PROCESADO this month ---
$baseSale = mkSale($monthlyStore, $admin, ['total_amount' => 10, 'dte_status' => 'PROCESADO']);
$cn = new CreditNote([
    'store_id' => $monthlyStore->id,
    'user_id' => $admin->id,
    'sale_id' => $baseSale->id,
    'sale_date' => $nowGt->toDateString(),
    'credit_note_date' => $nowGt->toDateString(),
    'total_amount' => 5,
    'net_amount' => 5,
]);
$cn->dte_status = 'PROCESADO';
$cn->created_at = $nowGt;
$cn->updated_at = $nowGt;
$cn->save();

$dn = new DebitNote([
    'store_id' => $monthlyStore->id,
    'user_id' => $admin->id,
    'sale_id' => $baseSale->id,
    'sale_date' => $nowGt->toDateString(),
    'debit_note_date' => $nowGt->toDateString(),
    'total_amount' => 5,
    'net_amount' => 5,
]);
$dn->dte_status = 'PROCESADO';
$dn->created_at = $nowGt;
$dn->updated_at = $nowGt;
$dn->save();

// notes ignored for annual: create large NC that should NOT count toward revenue
// last-month sale should not count for monthly
mkSale($monthlyStore, $admin, ['dte_status' => 'PROCESADO', 'total_amount' => 99], $nowGt->copy()->subMonth()->startOfMonth()->addDays(2));
// pending ignored
mkSale($monthlyStore, $admin, ['dte_status' => 'PENDIENTE', 'total_amount' => 7]);

$countUsed = $quota->processedDteCount($monthlyStore->fresh());
$denialAt2 = $quota->denial($monthlyStore->fresh()); // limit=2, used should be 3 (sale+nc+nd)
$monthlyStore->dte_monthly_limit = 3;
$monthlyStore->save();
$denialAt3 = $quota->denial($monthlyStore->fresh());
$monthlyStore->dte_monthly_limit = 2; // restore for HTTP soft-block
$monthlyStore->save();

// Pending docs for generarDTE HTTP (check 3)
$pendingSale = mkSale($monthlyStore, $admin, ['dte_status' => 'PENDIENTE', 'total_amount' => 12]);
$pendingCn = new CreditNote([
    'store_id' => $monthlyStore->id,
    'user_id' => $admin->id,
    'sale_id' => $baseSale->id,
    'sale_date' => $nowGt->toDateString(),
    'credit_note_date' => $nowGt->toDateString(),
    'total_amount' => 3,
    'net_amount' => 3,
]);
$pendingCn->dte_status = 'PENDIENTE';
$pendingCn->save();
$pendingDn = new DebitNote([
    'store_id' => $monthlyStore->id,
    'user_id' => $admin->id,
    'sale_id' => $baseSale->id,
    'sale_date' => $nowGt->toDateString(),
    'debit_note_date' => $nowGt->toDateString(),
    'total_amount' => 3,
    'net_amount' => 3,
]);
$pendingDn->dte_status = 'PENDIENTE';
$pendingDn->save();

// --- Check 2 annual revenue fixtures ---
mkSale($annualStore, $admin, ['dte_status' => 'PROCESADO', 'total_amount' => 6000]);
mkSale($annualStore, $admin, ['dte_status' => 'PROCESADO', 'total_amount' => 4500]);
// NC should be ignored for annual revenue
$annSale = Sale::query()->where('store_id', $annualStore->id)->orderByDesc('id')->first();
$annCn = new CreditNote([
    'store_id' => $annualStore->id,
    'user_id' => $admin->id,
    'sale_id' => $annSale->id,
    'sale_date' => $nowGt->toDateString(),
    'credit_note_date' => $nowGt->toDateString(),
    'total_amount' => 99999,
    'net_amount' => 99999,
]);
$annCn->dte_status = 'PROCESADO';
$annCn->save();
$annualDenial = $quota->denial($annualStore->fresh());
$annualRevenue = $quota->annualProcessedRevenue($annualStore->fresh());

// --- Check 4 sale block fixtures ---
mkSale($saleBlockStore, $admin, ['dte_status' => 'PROCESADO', 'total_amount' => 20]);
$product = ProductType::query()->create([
    'name' => 'Smoke Product '.uniqid(),
    'price' => 10,
    'stock' => 100,
    'category' => 'General',
    'company_id' => $company->id,
    'store_id' => $saleBlockStore->id,
]);
CorrelativoStore::query()->create([
    'store_id' => $saleBlockStore->id,
    'tipo_documento_id' => 1,
    'correlativo' => 0,
]);

// --- Check 6 critical 40/50 ---
for ($i = 0; $i < 40; $i++) {
    mkSale($criticalStore, $admin, ['dte_status' => 'PROCESADO', 'total_amount' => 1]);
}
$criticalSummary = $quota->usageSummary($criticalStore->fresh());

$seedMeta = [
    'company_id' => $company->id,
    'monthly_store_id' => $monthlyStore->id,
    'annual_store_id' => $annualStore->id,
    'sale_block_store_id' => $saleBlockStore->id,
    'critical_store_id' => $criticalStore->id,
    'authz_store_id' => $authzStore->id,
    'foreign_store_id' => $foreignStore->id,
    'pending_sale_id' => $pendingSale->id,
    'pending_cn_id' => $pendingCn->id,
    'pending_dn_id' => $pendingDn->id,
    'product_id' => $product->id,
    'service_count_used' => $countUsed,
    'service_denial_at_limit2' => $denialAt2,
    'service_denial_at_limit3' => $denialAt3,
    'annual_revenue' => $annualRevenue,
    'annual_denial' => $annualDenial,
    'critical_summary' => $criticalSummary,
];
file_put_contents(__DIR__.'/_smoke_seed_meta.json', json_encode($seedMeta, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "Seed done: ".json_encode($seedMeta)."\n";

$checks = [];
$optional = [];

// ========== LIVE HTTP ==========
echo "Running live HTTP checks...\n";

// Helper: login as admin for most checks
$loginAdmin = login('smoke-omp-admin@example.test', 'Password2025*');
if (!$loginAdmin['ok']) {
    // fallback yo@gestok.com
    $loginAdmin = login('yo@gestok.com', 'Password2025*');
}

// ---- Check 1: monthly quota via dte-usage + POST sales soft block ----
$usage1 = http('GET', '/stores/'.$monthlyStore->id.'/dte-usage');
$postOver = null;
// ensure session company for SA if needed ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â admin shouldn't need it
$loginAdmin2 = login('smoke-omp-admin@example.test', 'Password2025*');
$pageCsrf = http('GET', '/stores/'.$saleBlockStore->id.'/sales/create', ['accept' => 'text/html', 'follow' => true]);
// For check 1 soft block use monthly store which is already over (used>=2)
// First ensure correlativo+product on monthly for POST
$monthlyProduct = ProductType::query()->firstOrCreate(
    ['store_id' => $monthlyStore->id, 'name' => 'Smoke Monthly Product'],
    ['price' => 10, 'stock' => 100, 'category' => 'General', 'company_id' => $company->id]
);
CorrelativoStore::query()->firstOrCreate(
    ['store_id' => $monthlyStore->id, 'tipo_documento_id' => 1],
    ['correlativo' => 0]
);
$loginAdmin = login('smoke-omp-admin@example.test', 'Password2025*');
$block1 = http('POST', '/stores/'.$monthlyStore->id.'/sales', [
    'json' => [
        'sale_date' => $nowGt->toDateString(),
        'discount_amount' => 0,
        'products' => [['id' => $monthlyProduct->id, 'quantity' => 1, 'price' => 10]],
        'tipo_documento_id' => 1,
        'payment_method' => 'Efectivo',
    ],
]);
$checks[] = [
    'id' => 1,
    'name' => 'monthly DTE quota GT (sale+NC+ND PROCESADO; soft block dte_monthly_limit)',
    'result' => (
        ($usage1['status'] === 200 && (int)($usage1['json']['used'] ?? 0) >= 3 && ($block1['status'] === 422 && ($block1['json']['error'] ?? '') === 'dte_monthly_limit'))
        || ($countUsed >= 3 && ($denialAt2['error'] ?? '') === 'dte_monthly_limit' && $block1['status'] === 422 && ($block1['json']['error'] ?? '') === 'dte_monthly_limit')
    ) ? 'PASS' : 'FAIL',
    'detail' => sprintf('service_used=%s denial=%s usage_http=%s usage_body=%s post_status=%s post_body=%s',
        $countUsed,
        json_encode($denialAt2),
        $usage1['status'],
        substr($usage1['body'], 0, 300),
        $block1['status'],
        substr($block1['body'], 0, 400)
    ),
    'http_status' => $block1['status'],
    'evidence' => [
        'dte_usage' => $usage1['json'],
        'post_sales' => $block1['json'] ?? $block1['body'],
        'service_used' => $countUsed,
        'note' => 'Conteo service + HTTP 422 dte_monthly_limit al POST sales sobre cuota',
    ],
];

// ---- Check 2: annual revenue ----
$loginAdmin = login('smoke-omp-admin@example.test', 'Password2025*');
// Force annual check via POST sale on annual store (starter, revenue>=10000)
$annualProduct = ProductType::query()->firstOrCreate(
    ['store_id' => $annualStore->id, 'name' => 'Smoke Annual Product'],
    ['price' => 10, 'stock' => 100, 'category' => 'General', 'company_id' => $company->id]
);
CorrelativoStore::query()->firstOrCreate(
    ['store_id' => $annualStore->id, 'tipo_documento_id' => 1],
    ['correlativo' => 0]
);
// Raise monthly so only annual blocks
$annualStore->dte_monthly_limit = 500;
$annualStore->save();
$block2 = http('POST', '/stores/'.$annualStore->id.'/sales', [
    'json' => [
        'sale_date' => $nowGt->toDateString(),
        'discount_amount' => 0,
        'products' => [['id' => $annualProduct->id, 'quantity' => 1, 'price' => 10]],
        'tipo_documento_id' => 1,
        'payment_method' => 'Efectivo',
    ],
]);
$checks[] = [
    'id' => 2,
    'name' => 'starter annual revenue >=10000 PROCESADO GT year (notes ignored)',
    'result' => ($annualRevenue >= 10000 && ($block2['status'] === 422 && ($block2['json']['error'] ?? '') === 'annual_revenue_limit')) ? 'PASS' : 'FAIL',
    'detail' => sprintf('annual_revenue=%s service_denial=%s http=%s body=%s', $annualRevenue, json_encode($annualDenial), $block2['status'], substr($block2['body'], 0, 400)),
    'http_status' => $block2['status'],
    'evidence' => [
        'annual_revenue' => $annualRevenue,
        'post_sales' => $block2['json'] ?? $block2['body'],
    ],
];

// ---- Check 3: generarDTE / NC / ND 422 via refresh-dte + controller path through HTTP refresh ----
$loginAdmin = login('smoke-omp-admin@example.test', 'Password2025*');
// Get CSRF for form posts
$dash = http('GET', '/stores/'.$monthlyStore->id.'/dashboard', ['accept' => 'text/html', 'follow' => true]);
$csrf = extractCsrf($dash['body'] ?? '') ?? '';
$refSale = http('POST', '/stores/'.$monthlyStore->id.'/sales/'.$pendingSale->id.'/refresh-dte', [
    'accept' => 'text/html',
    'follow' => false,
    'form' => ['_token' => $csrf],
]);
$refCn = http('POST', '/stores/'.$monthlyStore->id.'/creditnotes/'.$pendingCn->id.'/refresh-dte', [
    'accept' => 'text/html',
    'follow' => false,
    'form' => ['_token' => $csrf],
]);
$refDn = http('POST', '/stores/'.$monthlyStore->id.'/debitnotes/'.$pendingDn->id.'/refresh-dte', [
    'accept' => 'text/html',
    'follow' => false,
    'form' => ['_token' => $csrf],
]);
$pendingSaleFresh = $pendingSale->fresh();
$pendingCnFresh = $pendingCn->fresh();
$pendingDnFresh = $pendingDn->fresh();
// Also hit JSON path: invoke through a small in-process HTTP kernel request to generar isnÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢t available;
// verify statuses remain PENDIENTE (refresh converts 422 to redirect+flash).
$check3Pass = $pendingSaleFresh->dte_status === 'PENDIENTE'
    && $pendingCnFresh->dte_status === 'PENDIENTE'
    && $pendingDnFresh->dte_status === 'PENDIENTE'
    && in_array($refSale['status'], [302, 303, 422], true);
// Stronger: call controllers via HTTP kernel simulating JSON Accept on a synthetic route ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â use artisan-free internal:
$ctrl = new App\Http\Controllers\DTEController(
    Mockery::mock(App\Services\DocumentService::class),
    Mockery::mock(App\Services\HaciendaAuthService::class),
    Mockery::mock(App\Services\ReceptionService::class),
    Mockery::mock(App\Services\ContingenciaService::class),
);
$rSale = $ctrl->generarDTE($pendingSaleFresh);
$rCn = $ctrl->generarDTECreditNote($pendingCnFresh, $baseSale->fresh());
$rDn = $ctrl->generarDTEDebitNote($pendingDnFresh, $baseSale->fresh());
$ctrlJsonOk = $rSale->getStatusCode() === 422 && ($rSale->getData(true)['error'] ?? '') === 'dte_monthly_limit'
    && $rCn->getStatusCode() === 422 && $rDn->getStatusCode() === 422;

$checks[] = [
    'id' => 3,
    'name' => 'generarDTE / NC / ND return 422 before signing when over quota',
    'result' => ($check3Pass && $ctrlJsonOk) ? 'PASS' : 'FAIL',
    'detail' => sprintf(
        'refresh_sale=%s refresh_cn=%s refresh_dn=%s statuses=%s/%s/%s ctrl_sale=%s ctrl_body=%s',
        $refSale['status'], $refCn['status'], $refDn['status'],
        $pendingSaleFresh->dte_status, $pendingCnFresh->dte_status, $pendingDnFresh->dte_status,
        $rSale->getStatusCode(), json_encode($rSale->getData(true))
    ),
    'http_status' => $rSale->getStatusCode(),
    'evidence' => [
        'refresh_http' => ['sale' => $refSale['status'], 'cn' => $refCn['status'], 'dn' => $refDn['status']],
        'remain_pendiente' => [
            'sale' => $pendingSaleFresh->dte_status,
            'cn' => $pendingCnFresh->dte_status,
            'dn' => $pendingDnFresh->dte_status,
        ],
        'controller_json' => [
            'sale' => $rSale->getData(true),
            'cn' => $rCn->getData(true),
            'dn' => $rDn->getData(true),
        ],
        'note' => 'refresh-dte HTTP mantiene PENDIENTE; generarDTE* confirma JSON 422 dte_monthly_limit (mismo soft-block que usa Sale/CN/ND store HTTP)',
    ],
];

// ---- Check 4: POST sales over monthly -> 422 + PENDIENTE ----
$loginAdmin = login('smoke-omp-admin@example.test', 'Password2025*');
$beforePend = Sale::query()->where('store_id', $saleBlockStore->id)->where('dte_status', 'PENDIENTE')->count();
$block4 = http('POST', '/stores/'.$saleBlockStore->id.'/sales', [
    'json' => [
        'sale_date' => $nowGt->toDateString(),
        'discount_amount' => 0,
        'products' => [['id' => $product->id, 'quantity' => 1, 'price' => 10]],
        'tipo_documento_id' => 1,
        'payment_method' => 'Efectivo',
    ],
]);
$afterPend = Sale::query()->where('store_id', $saleBlockStore->id)->where('dte_status', 'PENDIENTE')->count();
$signed = Sale::query()->where('store_id', $saleBlockStore->id)->where('id', '>', 0)
    ->orderByDesc('id')->first();
$checks[] = [
    'id' => 4,
    'name' => 'POST sales over monthly quota ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ HTTP 422 dte_monthly_limit + sale PENDIENTE',
    'result' => ($block4['status'] === 422 && ($block4['json']['error'] ?? '') === 'dte_monthly_limit' && $afterPend > $beforePend) ? 'PASS' : 'FAIL',
    'detail' => sprintf('http=%s body=%s pending_before=%s pending_after=%s last_status=%s',
        $block4['status'], substr($block4['body'], 0, 400), $beforePend, $afterPend, $signed?->dte_status),
    'http_status' => $block4['status'],
    'evidence' => [
        'response' => $block4['json'] ?? $block4['body'],
        'pending_before' => $beforePend,
        'pending_after' => $afterPend,
        'latest_sale_status' => $signed?->dte_status,
    ],
];

// ---- Check 5: dte-usage authz ----
$authzResults = [];
@unlink(COOKIE_JAR);
$guest = http('GET', '/stores/'.$authzStore->id.'/dte-usage');
$authzResults['guest'] = ['status' => $guest['status'], 'redirect' => (bool)preg_match('/login/i', $guest['headers'].$guest['body'])];

$loginNorole = login('smoke-omp-norole@example.test', 'Password2025*');
$noroleResp = http('GET', '/stores/'.$authzStore->id.'/dte-usage');
$authzResults['norole'] = ['login' => $loginNorole, 'status' => $noroleResp['status'], 'body' => substr($noroleResp['body'], 0, 200)];

$loginStranger = login('smoke-omp-stranger@example.test', 'Password2025*');
$strangerResp = http('GET', '/stores/'.$authzStore->id.'/dte-usage');
$authzResults['stranger'] = ['login' => $loginStranger, 'status' => $strangerResp['status'], 'body' => substr($strangerResp['body'], 0, 200)];

$loginAdmin = login('smoke-omp-admin@example.test', 'Password2025*');
$adminOk = http('GET', '/stores/'.$authzStore->id.'/dte-usage');
$adminForeign = http('GET', '/stores/'.$foreignStore->id.'/dte-usage');
$authzResults['admin_own'] = ['status' => $adminOk['status'], 'json' => $adminOk['json']];
$authzResults['admin_foreign'] = ['status' => $adminForeign['status'], 'body' => substr($adminForeign['body'], 0, 200)];

$loginCashier = login('smoke-omp-user@example.test', 'Password2025*');
$cashierOk = http('GET', '/stores/'.$authzStore->id.'/dte-usage');
$authzResults['cashier'] = ['status' => $cashierOk['status'], 'json' => $cashierOk['json']];

$loginSa = login('yo@gestok.com', 'Password2025*');
$saNoCompany = http('GET', '/stores/'.$authzStore->id.'/dte-usage');
$authzResults['sa_no_company'] = ['status' => $saNoCompany['status'], 'body' => substr($saNoCompany['body'], 0, 200)];

// Select company via session: hit a page that sets it, or POST if there's an endpoint
// Look for company switch ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â use session via visiting stores index after selecting
// Select smoke company via companies.show (sets selected_company_id)









// Direct DB session update is fragile; use tinker-less approach: call route if exists.
$saWithCompany = ['status' => null];
// Try GET /stores after setting selected_company via known SA flow
$selectCo = http('GET', '/companies/'.$company->id, ['accept' => 'text/html', 'follow' => true]);
$authzResults['sa_select_company'] = ['status' => $selectCo['status']];
$saOk = http('GET', '/stores/'.$authzStore->id.'/dte-usage');
$authzResults['sa_after_select'] = ['status' => $saOk['status'], 'json' => $saOk['json'], 'body' => substr($saOk['body'], 0, 200)];










$authzPass =
    (in_array($guest['status'], [401, 302, 303], true) || ($authzResults['guest']['redirect'] ?? false))
    && in_array($noroleResp['status'], [403, 401], true)
    && in_array($strangerResp['status'], [403, 401], true)
    && $adminOk['status'] === 200
    && in_array($adminForeign['status'], [403, 401], true)
    && $cashierOk['status'] === 200
    && in_array($saNoCompany['status'], [403, 401], true)
    && $saOk['status'] === 200;

$checks[] = [
    'id' => 5,
    'name' => 'GET stores/{store}/dte-usage authz follows store view',
    'result' => $authzPass ? 'PASS' : 'FAIL',
    'detail' => json_encode($authzResults, JSON_UNESCAPED_UNICODE),
    'http_status' => $adminOk['status'],
    'evidence' => $authzResults,
];

// ---- Check 6: critical 40/50 + dashboard badge ----
$loginAdmin = login('smoke-omp-admin@example.test', 'Password2025*');
$usage6 = http('GET', '/stores/'.$criticalStore->id.'/dte-usage');
$dash6 = http('GET', '/stores/'.$criticalStore->id.'/dashboard', ['accept' => 'text/html', 'follow' => true]);
$html = $dash6['body'] ?? '';
$hasCritical = str_contains($html, 'dte-usage-critical');
$hasDanger = str_contains($html, 'alert-danger');
$hasCta = str_contains($html, 'Contactar soporte');
$hasBasicHint = str_contains($html, '200 DTE/mes') || str_contains($html, 'Basic');
$checks[] = [
    'id' => 6,
    'name' => 'starter ~40/50 critical + dashboard badge/CTA Basic 200',
    'result' => (
        $usage6['status'] === 200
        && ($usage6['json']['warning_level'] ?? '') === 'critical'
        && (int)($usage6['json']['used'] ?? 0) === 40
        && $dash6['status'] === 200
        && $hasCritical && $hasDanger && $hasCta && $hasBasicHint
    ) ? 'PASS' : 'FAIL',
    'detail' => sprintf('usage=%s dash=%s critical=%s danger=%s cta=%s basic=%s',
        substr($usage6['body'], 0, 300), $dash6['status'], $hasCritical?'Y':'N', $hasDanger?'Y':'N', $hasCta?'Y':'N', $hasBasicHint?'Y':'N'),
    'http_status' => $usage6['status'],
    'evidence' => [
        'dte_usage' => $usage6['json'],
        'dashboard_status' => $dash6['status'],
        'html_flags' => compact('hasCritical', 'hasDanger', 'hasCta', 'hasBasicHint'),
    ],
];

// Optional: config plans
$plans = config('plans');
$optional[] = [
    'id' => 'config_plans',
    'name' => 'config plans starter/basic/premium/empresarial',
    'result' => (isset($plans['starter'], $plans['basic'], $plans['premium'], $plans['empresarial']) && !isset($plans['free'])) ? 'PASS' : 'FAIL',
    'detail' => json_encode([
        'starter_limit' => $plans['starter']['dte_monthly_limit'] ?? null,
        'basic_limit' => $plans['basic']['dte_monthly_limit'] ?? null,
        'premium_limit' => $plans['premium']['dte_monthly_limit'] ?? null,
        'empresarial_limit' => $plans['empresarial']['dte_monthly_limit'] ?? null,
        'starter_annual' => $plans['starter']['annual_revenue_limit'] ?? null,
    ]),
];

// Optional: SA-only create store
$loginAdmin = login('smoke-omp-admin@example.test', 'Password2025*');
$csrf = extractCsrf((http('GET', '/stores', ['accept'=>'text/html','follow'=>true]))['body'] ?? '') ?? '';
    $adminCreate = http('POST', '/stores/'.$company->id, [
    'accept' => 'text/html',
    'follow' => false,
    'form' => [
        '_token' => $csrf,
        'store_name' => 'Should Fail Admin Create',
        'establecimiento' => 'X001',
        'punto_venta' => 'P001',
        'address' => 'x',
        'phone' => '22220009',
        'manager' => 'x',
        'email' => 'smoke-fail-admin-create-'.uniqid().'@example.test',
        'status' => 'activa',
        'environment' => 'Development',
    ],
]);
$loginSa = login('yo@gestok.com', 'Password2025*');
http('GET', '/companies/'.$company->id, ['accept'=>'text/html','follow'=>true]);



$csrf = extractCsrf((http('GET', '/companies/'.$company->id.'/stores/create', ['accept'=>'text/html','follow'=>true]))['body'] ?? '') ?? $csrf;
$saEmail = 'smoke-sa-create-'.uniqid().'@example.test';
    $saCreate = http('POST', '/stores/'.$company->id, [
    'accept' => 'text/html',
    'follow' => false,
    'form' => [
        '_token' => $csrf,
        'store_name' => 'SMOKE SA Created',
        'establecimiento' => 'S009',
        'punto_venta' => 'P001',
        'address' => 'sa',
        'phone' => '22220010',
        'manager' => 'sa',
        'email' => $saEmail,
        'status' => 'activa',
        'environment' => 'Development',
        'plan' => 'starter',
    ],
]);
$created = Store::query()->where('email', $saEmail)->first();
$optional[] = [
    'id' => 'sa_only_create_store',
    'name' => 'only superadmin can create store / set plan',
    'result' => (
        in_array($adminCreate['status'], [403, 401, 302], true)
        // SA create may need selected_company ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â record evidence either way
    ) ? (( $created && $created->plan === 'starter') ? 'PASS' : 'PARTIAL') : 'FAIL',
    'detail' => sprintf('admin_create_status=%s sa_create_status=%s created=%s plan=%s limit=%s',
        $adminCreate['status'], $saCreate['status'], $created?->id, $created?->plan, $created?->dte_monthly_limit),
    'evidence' => [
        'admin_create_status' => $adminCreate['status'],
        'sa_create_status' => $saCreate['status'],
        'created_store' => $created ? $created->only(['id','plan','dte_monthly_limit','email']) : null,
    ],
];

$failed = array_filter($checks, fn($c) => $c['result'] === 'FAIL');
$overall = count($failed) === 0 ? 'PASS' : 'FAIL';

$report = [
    'ticket' => 'OMP-PLAN-01',
    'pr' => 'https://github.com/mikpidev/gestock_saas/pull/5',
    'sha' => sha(),
    'branch' => trim((string) shell_exec('git -C '.escapeshellarg(__DIR__).' branch --show-current')),
    'mode' => 'live_http',
    'base_url' => BASE,
    'timestamp' => Carbon::now('America/Guatemala')->toIso8601String(),
    'overall' => $overall,
    'checks' => $checks,
    'optional' => $optional,
    'seed' => $seedMeta,
    'notes' => 'Puerto 8000 servÃƒÆ’Ã‚Â­a Gestock-API (otro repo); smoke contra gestock_saas en :8001. Env del shell tenÃƒÆ’Ã‚Â­a DB_CONNECTION=sqlite/:memory: (heredado); se removiÃƒÆ’Ã‚Â³ para MySQL gestock_saas@3308. Fixtures aislados email smoke-omp-plan-01*@. Check3: refresh-dte HTTP + confirmaciÃƒÆ’Ã‚Â³n JSON 422 de generarDTE* (mismo soft-block). Notas en espaÃƒÆ’Ã‚Â±ol.',
];

$json = json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
file_put_contents(REPORT_PC, $json);
file_put_contents(REPORT_BOX_COPY, $json);
echo "OVERALL=$overall\n";
echo "Wrote ".REPORT_PC."\n";
foreach ($checks as $c) {
    echo sprintf("#%d %s %s\n", $c['id'], $c['result'], $c['name']);
}
