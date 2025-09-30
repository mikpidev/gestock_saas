<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceNumber extends Model
{
    protected $fillable = ['store_id', 'number', 'used'];

    public static function getNextNumber($storeId)
    {
        $last = self::where('store_id', $storeId)->latest('number')->first();
        $nextNumber = $last ? $last->number + 1 : 1;

        return self::create([
            'store_id' => $storeId,
            'number' => $nextNumber,
            'used' => true
        ])->number;
    }

        public function sale()
    {
        return $this->hasOne(Sale::class);
    }


}
