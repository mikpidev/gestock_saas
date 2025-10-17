<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDte extends Model
{
    // Nombre de la tabla
    protected $table = 'tipo_documento';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'codigo',       // Código de Hacienda
        'descripcion',  // Descripción del tipo de documento
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class, 'tipo_documento_id');
    }
}
