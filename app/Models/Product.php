<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    public $timestamps = false;

    public function pack(){
        return $this->hasOne(ProductPack::class, 'product_id', 'id');
    }

    public function productCategory(){
        return $this->hasOne(ProductCategory::class, 'product_id', 'id');
    }

}
