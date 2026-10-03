<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$out = [
  'users' => App\Models\User::query()->with('roles')->get(['id','email','company_id','store_id'])->map(function ($x) {
    return ['id'=>$x->id,'email'=>$x->email,'company_id'=>$x->company_id,'store_id'=>$x->store_id,'roles'=>$x->roles->pluck('name')];
  }),
  'companies' => App\Models\Company::query()->get(['id','company_name','status']),
  'stores' => App\Models\Store::query()->get(['id','store_name','plan','dte_monthly_limit','company_id','status','email','establecimiento','punto_venta']),
  'tipo' => App\Models\TipoDte::all(['id','codigo','nombre']),
  'plans' => config('plans'),
];
file_put_contents(__DIR__.'/_smoke_inventory.json', json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "wrote _smoke_inventory.json\n";
