<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    protected $table = 'tipo_documento_identificacion';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['codigo', 'descripcion'];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'tipoDocumento', 'codigo');
    }
}
