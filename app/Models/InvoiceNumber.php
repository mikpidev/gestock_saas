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
    public static function getNextNumber($storeId, $tipoDTE, $establecimiento, $puntoVenta)
    {
        // Obtener último número
        $last = self::where('store_id', $storeId)->latest('number')->first();
        $nextNumber = $last ? $last->number + 1 : 1;

        // Prefijo según tipo DTE
        $prefix = "DTE-{$tipoDTE}-";

        // Parte central conformada por establecimiento y punto de venta
        $partCentral = $establecimiento . $puntoVenta;

        //obtengo correlativo de la factura
        \Log::info('Antes de CorrelativoStore::next', [
            'storeId' => $storeId,
            'tipoDTE' => $tipoDTE,
        ]);


        $correlativo = CorrelativoStore::next($storeId, $tipoDTE);

        \Log::info('Después de CorrelativoStore::next', [
            'correlativo' => $correlativo,
        ]);


        // Parte final secuencial grande basado en correlativo
        $partFinal = str_pad($correlativo, 15, '0', STR_PAD_LEFT);

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

    public static function getCGVoidNumber($storeId)
    {
        $last = self::where('store_id', $storeId)->latest('number')->first();

        $nextNumber = $last ? $last->number + 1 : 1;

        return self::create([
            'store_id' => $storeId,
            'number' => $nextNumber,
            'used' => true,
            'numero_control' => null,
            'codigo_generacion' => strtoupper(Str::uuid()->toString()),
        ]);
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
