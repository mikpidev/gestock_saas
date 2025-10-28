<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VoidDTE extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'void_dtes';

    protected $fillable = [
        'sale_id',             // Relación con la venta
        'codigo_generacion',   // Código de generación del DTE a anular
        'void_date',           // Fecha de anulación
        'desc',                // Motivo de la anulación
        'estado',              // Estado de la anulación (PROCESADO, ERROR, etc.)
        'response_json',       // Guardar la respuesta completa de Hacienda
    ];

    protected $casts = [
        'void_date' => 'datetime',
        'response_json' => 'array',
    ];

    // Agregar atributo virtual para sello recibido
    protected $appends = ['sello_recibido'];

    /**
     * Relación con la venta
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
    
    /**
     * Relación con el DteResponse de la venta
     */
    public function dteResponse()
    {
        return $this->sale->dteResponses()->latest('created_at')->first();
    }
    
    /**
     * Obtener el último sello recibido
     */
    public function selloRecibido()
    {
        return $this->dteResponse()?->sello_recibido;
    }

    // Relación con las notas de crédito asociadas a esta venta
    public function creditNotes()
    {
        return $this->belongsTo(CreditNote::class);
    }

}
