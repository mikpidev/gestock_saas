<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DteResponse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_id',
        'version',
        'ambiente',
        'version_app',
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
        'observaciones' => 'array',
        'fh_procesamiento' => 'datetime',
    ];

    /**
     * Relación con la venta
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relación con los VoidDTE de la venta
     */
    public function voids()
    {
        return $this->hasMany(VoidDTE::class, 'sale_id', 'sale_id');
    }

    // Relación con las notas de crédito asociadas a esta venta
    public function creditNotes()
    {
        return $this->belongsTo(CreditNote::class);
    }
}
