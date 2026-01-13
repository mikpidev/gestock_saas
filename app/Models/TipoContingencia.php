<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoContingencia extends Model
{
    protected $fillable = ['codigo', 'nombre'];

    public function contingencias()
    {
        return $this->hasMany(Contingencia::class, 'tipo_contingencia_id');
    }
}

