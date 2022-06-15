<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionCat extends Model
{
    protected $fillable = ['title','status'];
    use HasFactory;
    public function Questions()
    {
        return $this->hasMany(Question::class, 'question_cats_id', 'id');
    }
}

