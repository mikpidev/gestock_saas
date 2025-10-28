<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DteResponseNC extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dte_responses_nc';

    protected $fillable = [
        'credit_note_id',
        'version',
        'ambiente',
        'versionApp',
        'estado',
        'codigo_generacion',
        'sello_recibido',
        'fh_procesamiento',
        'clasifica_msg',
        'codigo_msg',
        'descripcion_msg',
        'observaciones',
    ];

    protected $casts = [
        'fh_procesamiento' => 'datetime',
        'observaciones' => 'array',
    ];

    /**
     * Relación con la nota de crédito
     */
    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class);
    }
}
