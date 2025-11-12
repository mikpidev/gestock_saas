<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashClosure extends Model
{
    protected $table = 'cash_closures'; 

    protected $fillable = [
        'store_id','user_id','from_sale_id','to_sale_id',
        'total_sales','total_credit_notes','total_debit_notes',
        'amount_sales','amount_credit_notes','amount_debit_notes',
        'total_cash','total_card'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Ventas incluidas en el corte
    public function sales()
    {
        return $this->hasMany(Sale::class, 'store_id', 'store_id')
            ->whereBetween('id', [$this->from_sale_id, $this->to_sale_id]);
    }

    
}
