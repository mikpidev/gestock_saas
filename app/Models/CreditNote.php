<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditNote extends Model
{
    //fillable

    use SoftDeletes, HasFactory;


    protected $fillable = [
        'store_id',
        'user_id',
        'customers_id',
        'credit_note_date',
        'sale_id',
        'sale_date',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'net_amount',

        // Campos nuevos para DTE
        'tipo_moneda',
        'numero_control',
        'codigo_generacion',
        'tipo_operacion',
        'tipo_contingencia',
        'motivo_contingencia',
        'condicion_operacion',

        //Documento Relacionado
        'documento_relacionado',

        // Totales desglosados
        'total_no_gravado',
        'total_exenta',
        'total_gravada',
        'total_iva',

        'dte_status'
    ];

    // Casting de fechas
    protected $casts = [
        'sale_date' => 'datetime',
        'credit_note_date' => 'datetime',

    ];

    // Relación con la tienda
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // Relación con el usuario que realizó la venta
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con el cliente
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customers_id');
    }



    //relacion con tipo_documento TipoDTE
    public function tipoDte()
    {
        return $this->belongsTo(TipoDte::class, 'tipo_documento_id');
    }

    //relacion con la venta original
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function creditNoteDetails()
    {
        return $this->hasMany(CreditNoteDetail::class);
    }

    //relacion con dteResponse
    public function dteResponses()
    {
        return $this->hasMany(DteResponseNC::class);
    }

    public function voids()
    {
        return $this->hasMany(VoidNC::class);
    }
    

}
