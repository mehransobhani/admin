<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $table = 'product_category';
    public $timestamps = false;

    public function category(){
        return $this->hasOne(Category::class, 'id', 'category');
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

}
