<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreTaxInfo extends Model
{
    use SoftDeletes;

    
    // Campos que se pueden asignar de forma masiva
    protected $fillable = [
        'company_id',
        'store_id',
        'nit',
        'nrc', // renombrado
        'razon_social',
        'actividad_economica',
        'direccion_fiscal',
        'direccion_departamento',
        'direccion_municipio',
        'codActividad',
        'email',
        'telefono',
        'estado',
        'comentarios',
    ];


    // Relación con la compañía (inversa 1:N)
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Relación con la tienda
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // Actividad económica
    public function actividad()
    {
        return $this->belongsTo(CodActividad::class, 'codActividad', 'codigo');
    }

    // Departamento (clave foránea: direccion_departamento → departamentos.id)
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'direccion_departamento', 'codigo');
    }

    // Municipio (clave foránea: direccion_municipio → municipios.id)
    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'direccion_municipio', 'codigo');
    }
}
