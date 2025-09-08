<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    //informacion de la tabla
    protected $table = 'customers';
    protected $fillable = [
        'company_id',
        'store_id',
        'nombre',
        'tipo_documento',
        'numero_documento',
        'nrc',
        'razon_social',
        'actividad_economica',
        'direccion_fiscal',
        'email',
        'telefono',
        'tipo_cliente',
        'comentarios',
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



}
