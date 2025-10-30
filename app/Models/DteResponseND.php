<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class DteResponseND extends Model
{
    
    use HasFactory, SoftDeletes;

    protected $table = 'dte_responses_nd';

    protected $fillable = [
        'debit_note_id',
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
    public function debitNote()
    {
        return $this->belongsTo(DebitNote::class);
    }


}
