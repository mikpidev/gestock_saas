<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Legacy companies.plan → stores.plan.
     * Self-service `starter` is not in this map; legacy free becomes basic.
     *
     * @var array<string, string>
     */
    public array $legacyMap = [
        'free' => 'basic',
        'basic' => 'premium',
        'premium' => 'empresarial',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'plan')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->enum('plan', ['starter', 'basic', 'premium', 'empresarial'])
                    ->default('basic')
                    ->after('company_id');
                $table->unsignedInteger('dte_monthly_limit')->nullable()->after('plan');
            });
        }

        if (Schema::hasColumn('companies', 'plan')) {
            $this->backfillFromCompanies();

            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('plan');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('companies', 'plan')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->enum('plan', ['free', 'basic', 'premium'])->default('free');
            });
        }

        if (! Schema::hasColumn('stores', 'plan')) {
            return;
        }

        // Lossy: new starter and remapped legacy free (now basic)
        // both become companies.plan = free. Multiple stores keep the first row.
        $reverse = [
            'starter' => 'free',
            'basic' => 'free',
            'premium' => 'basic',
            'empresarial' => 'premium',
        ];

        $seen = [];
        $stores = DB::table('stores')->select('id', 'company_id', 'plan')->orderBy('id')->get();
        foreach ($stores as $store) {
            if (isset($seen[$store->company_id])) {
                continue;
            }
            $seen[$store->company_id] = true;
            DB::table('companies')->where('id', $store->company_id)->update([
                'plan' => $reverse[$store->plan] ?? 'free',
            ]);
        }

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['plan', 'dte_monthly_limit']);
        });
    }

    public function backfillFromCompanies(): void
    {
        $companies = DB::table('companies')->select('id', 'plan')->get();

        foreach ($companies as $company) {
            $plan = $this->legacyMap[$company->plan] ?? 'basic';
            DB::table('stores')->where('company_id', $company->id)->update([
                'plan' => $plan,
                'dte_monthly_limit' => config("plans.{$plan}.dte_monthly_limit"),
            ]);
        }
    }
};
