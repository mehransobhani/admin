<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'category';
    public $timestamps = false;

    public function info(){
        return $this->hasOne(CategoryInfo::class, 'category_id', 'id');
    }

    public function stock(){
        return $this->hasOne(ProductPack::class, 'category', 'id');
    }
}
