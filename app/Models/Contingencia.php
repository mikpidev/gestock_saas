<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contingencia extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'tipo_contingencia_id',
        'codigo_generacion',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'estado',
        'motivo_contingencia',
    ];
    
    protected $casts = [
        'fecha_hora_inicio' => 'datetime',
        'fecha_hora_fin'    => 'datetime',
    ];
    

    //relaciones

    // Relación con la tienda
    public function store()
    {
        return $this->belongsTo(Store::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tipoContingencia()
    {
        return $this->belongsTo(TipoContingencia::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'contingencia_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class, 'contingencia_id');
    }

    public function debitNotes()
    {
        return $this->hasMany(DebitNote::class, 'contingencia_id');
    }

    public function voidDTEs()
    {
        return $this->hasMany(VoidDTE::class);
    }

    public function voidNCs()
    {
        return $this->hasMany(VoidNC::class);
    }

    public function voidNDs()
    {
        return $this->hasMany(VoidND::class);
    }

    public function contingenciaDetails()
    {
        return $this->hasMany(ContingenciaDetails::class);
    }

    public function isActive()
    {
        return is_null($this->fecha_hora_fin);
    }


}
