<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNoteDetail extends Model
{
    //fillable fields

    protected $fillable =[

        'credit_note_id',
        'sale_detail_id',
        'product_type_id',
        'quantity',
        'unit_price',
        'subtotal',
        'tax_amount'
    ];

    protected $casts = [

        'quantity' => 'decimal:2',
        'unit_price'=> 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount'=> 'decimal:2'

    ];

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class);
    }

    // Relación con los detalles de la venta
    public function saleDetail()
    {
        return $this->belongsTo(SaleDetail::class);
    }

    //relacion con la venta original
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }


}
