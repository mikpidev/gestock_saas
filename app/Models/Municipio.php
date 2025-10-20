<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    protected $table = 'municipios';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['codigo', 'nombre', 'codigo_departamento'];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'codigo_departamento', 'codigo');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'direccion_municipio', 'codigo');
    }

    
    public function storetaxInfo()
    {
        return $this->hasMany(StoreTaxInfo::class, 'direccion_municipio', 'codigo');
    }
}
