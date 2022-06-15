<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = ['question','answer','top','status','question_cats_id','short_answer'];

    public function questioncat()
    {
        return $this->hasOne(QuestionCat::class, 'id', 'question_cats_id');
    }

}

