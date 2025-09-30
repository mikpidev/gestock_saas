<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    protected $fillable = [
        'sale_id',
        'product_type_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    // Relación con Sale
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    // Relación con ProductType
    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }
}
