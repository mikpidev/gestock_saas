<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    protected $fillable = ['company_name','address','phone','owner','email','website','plan','deployment_type','status','comments'];

    //Relacion de 1 a muchos con stores
    public function stores(){
        return $this->hasMany(Store::class);
    }

    //relacion de 1 a muchos con store_tax_infos
    public function storeTaxInfos(){
        return $this->hasMany(StoreTaxInfo::class);
    }

    //realacion de 1 a muchos con users
    public function users(){
        return $this->hasMany(User::class);
    }
    
    //relacion de 1 a muchos con product_types
    public function productTypes(){
        return $this->hasMany(ProductType::class);
    }

    //activar soft deletes
    use SoftDeletes;
    protected $dates = ['deleted_at'];

}
