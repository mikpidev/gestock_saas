<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    //informacion de la tabla
    protected $table = 'customers';
    protected $fillable = [
        'tipoDocumento',
        'numDocumento',
        'nrc',
        'nombre',
        'codActividad',
        'descActividad',
        'nombreComercial',
        'direccion_departamento',
        'direccion_municipio',
        'direccion_complemento',
        'telefono',
        'correo',
        'store_id',
    ];

    //uso de soft deletes
    use SoftDeletes;

    //relacion con la tabla companies
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    //relacion con la tabla stores
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    //relacion con la tabla sales
    public function sale()
    {
        return $this->hasMany(Sale::class);
    }

    // Tipo de documento
    public function tipoDocumentoCatalogo()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipoDocumento', 'codigo');
    }

    // Actividad económica
    public function actividad()
    {
        return $this->belongsTo(CodActividad::class, 'codActividad', 'codigo');
    }

    // Departamento
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'direccion_departamento', 'codigo');
    }

    // Municipio
    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'direccion_municipio', 'codigo');
    }


    



}
