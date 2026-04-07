<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;



class MHAccess extends Model
{
    protected $table = 'mh_access';
    
    // Definir primary key si no es 'id'
    protected $primaryKey = 'store_id';
    public $incrementing = false;
    protected $keyType = 'int';
    


    protected $fillable = [
        'store_id',
        'api_key',
        'password_pri',
        'port_firma_digital',
    ];

    //encrypted attributes
    protected $casts = [
        'api_key' => 'encrypted',
        'password_pri' => 'encrypted',
    ];

    //softdelete
    protected $dates = ['deleted_at'];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
    
    //sales relation
    public function sales()
    {
        return $this->hasMany(Sale::class, 'mh_access_id');
    }

}
