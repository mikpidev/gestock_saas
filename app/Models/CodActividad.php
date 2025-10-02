<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodActividad extends Model
{
    protected $table = 'cod_actividad';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['codigo', 'descripcion'];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'codActividad', 'codigo');
    }
}
