<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceNumber extends Model
{
    protected $fillable = ['store_id', 'number', 'used'];

    public static function getNextNumber($storeId)
{
    $last = self::where('store_id', $storeId)->latest('number')->first();
    $nextNumber = $last ? $last->number + 1 : 1;

    // Generar número de control y código de generación según Hacienda
    $randomAlphaNum = strtoupper(\Illuminate\Support\Str::random(8));
    $randomNumber15 = str_pad(rand(0, 999999999999999), 15, '0', STR_PAD_LEFT);
    $numeroControl = "DTE-01-{$randomAlphaNum}-{$randomNumber15}";
    $codigoGeneracion = \Illuminate\Support\Str::uuid()->toString();

    $invoice = self::create([
        'store_id' => $storeId,
        'number' => $nextNumber,
        'used' => true,
        'numero_control' => $numeroControl,
        'codigo_generacion' => $codigoGeneracion,
    ]);

    return $invoice;
}

        public function sale()
    {
        return $this->hasOne(Sale::class);
    }


}
