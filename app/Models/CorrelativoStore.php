<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CorrelativoStore extends Model
{
    protected $table = 'correlativo_stores';

    //fillable

    protected $fillable = [
        'store_id',
        'tipo_documento_id',
        'correlativo',
    ];
    public static function next($storeId, $codigoDte)
    {
        $tipo = TipoDte::where('codigo', $codigoDte)->firstOrFail();
        \Log::info('Antes de CorrelativoStore::next', [
            'storeId' => $storeId,
            'tipoDte' => $tipo,
        ]);
        return DB::transaction(function () use ($storeId, $tipo) {

            $correlativo = self::where('store_id', $storeId)
                ->where('tipo_documento_id', $tipo->id)
                ->lockForUpdate()
                ->firstOrFail();

            $correlativo->increment('correlativo');
            \Log::info('DB Transaction::next', [
                'storeId' => $storeId,
                'tipoDte' => $tipo,
                'correlativo' => $correlativo,

            ]);
            return $correlativo->correlativo;
        });
    }

    //relacion con Store
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    //relacion con TipoDocumento
    public function tipoDte()
    {
        return $this->belongsTo(TipoDte::class, 'tipo_documento_id');
    }
}
