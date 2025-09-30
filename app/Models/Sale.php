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
    //campos que se pueden asignar masivamente
    protected $fillable = [
        'store_id',
        'user_id',
        'customer_id',
        'invoice_number_id',
        'sale_date',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'net_amount',
        'payment_status',
    ];

    //realciones con otros modelos
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    //relacion con el usuario que realizo la venta
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //relacion con el cliente
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    //relacion con los detalles de la venta
    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

    //relacion con invoice numbers

    public function invoiceNumber()
    {
        return $this->belongsTo(InvoiceNumber::class, 'invoice_number_id');
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function details()
    {
        return $this->hasMany(SaleDetail::class);
        
    }

    protected $casts = [
        'sale_date' => 'datetime',
    ];
    


    //uso de soft deletes
    use SoftDeletes, HasFactory;
}
