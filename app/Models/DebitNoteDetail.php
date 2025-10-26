<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebitNoteDetail extends Model
{
    //fillable fields

    protected $fillable =[

        'debit_note_id',
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

    public function debitNote()
    {
        return $this->belongsTo(DebitNote::class);
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
