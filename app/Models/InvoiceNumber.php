<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InvoiceNumber extends Model
{
    protected $fillable = ['store_id', 'number', 'used', 'numero_control', 'codigo_generacion'];

    /**
     * Obtiene el siguiente número de factura y genera número de control + código de generación.
     */
    public static function getNextNumber($storeId, $tipoDTE)
    {
        // Obtener último número
        $last = self::where('store_id', $storeId)->latest('number')->first();
        $nextNumber = $last ? $last->number + 1 : 1;

        // Prefijo según tipo DTE
        $prefix = "DTE-{$tipoDTE}-";

        // Parte central aleatoria
        $partCentral = 'S' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT)
                       . 'P' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

        // Parte final secuencial grande
        $partFinal = str_pad(rand(0, 999999999999999), 15, '0', STR_PAD_LEFT);

        // Combinar todo
        $numeroControl = $prefix . $partCentral . '-' . $partFinal;

        // Código de generación único para Hacienda
        $codigoGeneracion = strtoupper(Str::uuid()->toString());

        // Guardar en tabla
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


    public function creditNote()
    {
        return $this->hasOne(CreditNote::class);
    }
}
