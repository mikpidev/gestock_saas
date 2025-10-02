<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $table = 'departamentos';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['codigo', 'nombre'];

    public function municipios()
    {
        return $this->hasMany(Municipio::class, 'codigo_departamento', 'codigo');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'direccion_departamento', 'codigo');
    }
}
