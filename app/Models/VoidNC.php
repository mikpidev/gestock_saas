<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VoidNC extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'void_nc';

    protected $fillable = [
        'credit_note_id',
        'codigo_generacion',
        'void_date',
        'desc',
        'estado',
        'sello_recibido',
        'response_json',
    ];

    protected $casts = [
        'void_date' => 'datetime',
        'response_json' => 'array',
    ];

    /**
     * Relación con la nota de crédito
     */
    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class);
    }

    /**
     * Obtener el sello recibido de la venta original
     */
    public function selloRecibidoOriginal(): ?string
    {
        $sale = $this->creditNote?->sale;

        if (!$sale) {
            return null;
        }

        $response = $sale->dteResponses()
            ->where('estado', 'PROCESADO')
            ->orderBy('created_at', 'asc')
            ->first();

        return $response?->sello_recibido;
    }
}
