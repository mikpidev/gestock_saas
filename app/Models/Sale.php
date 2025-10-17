<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Store;
use App\Models\User;
use App\Models\Customer;
use App\Models\SaleDetail;

class Sale extends Model
{
    use SoftDeletes, HasFactory;

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'store_id',
        'user_id',
        'tipo_documento_id',
        'customers_id',
        'sale_date',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'net_amount',
        'payment_status',
        'numero_control',      // número de control para DTE
        'codigo_generacion',   // UUID para DTE
        'tipo_moneda',
        'tipo_operacion',
        'condicion_operacion',
        'total_no_gravado',
        'total_exenta',
        'total_gravada',
        'total_iva',
    ];

    // Casting de fechas
    protected $casts = [
        'sale_date' => 'datetime',
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

    // Relación con los detalles de la venta
    public function details()
    {
        return $this->hasMany(SaleDetail::class);
    }

    //relacion con tipo_documento TipoDTE
    public function tipoDte()
    {
        return $this->belongsTo(TipoDte::class, 'tipo_documento_id');
    }

}
