<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleDetail extends Model
{
    use HasFactory;

    protected $table = 'sale_details';

    protected $fillable = [
        'sale_id',
        'product_type_id',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relación con la venta principal
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relación con el tipo de producto (catálogo)
     */
    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    /**
     * Calcula el subtotal automáticamente
     */
    public function calculateSubtotal()
    {
        $this->subtotal = $this->quantity * $this->unit_price;
        return $this->subtotal;
    }

    /**
     * Devuelve la descripción del producto o tipo de servicio
     */
    public function getDescriptionAttribute($value)
    {
        // Si tiene descripción personalizada, usarla
        if (!empty($value)) {
            return $value;
        }

        // Si no, usar la del tipo de producto
        return $this->productType ? $this->productType->nombre : 'Sin descripción';
    }
}
