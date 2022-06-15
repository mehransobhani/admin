<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionCat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaqController extends Controller
{

    public function cats_list()
    {
        $model=QuestionCat::query()->where("status","=","1")->get();

        return Response()->json($model,200);
    }
     public function get_answer()
    {
        $model=Question::query()->where('top','=','1')->where('status','=','1')->get();
        return Response()->json($model);
    }


    public function get_fullanswer($id)
    {
try{
        $model = DB::table('questions')->where('questions.id',$id)
            ->join('question_cats', 'question_cats.id', '=', 'questions.question_cats_id')
            ->select('questions.*', 'question_cats.title')->get();
        return Response()->json($model[0]);
}
 catch (\Exception $e)
        {
            return ;
        }    
}

    public function get_cat_answers($id)
    {

        $model=Question::query()->where('top','=','1')->where('status','=','1')->get();
        return Response()->json($model,200);

    }
    public function show($id)
    {
try{
        $model=QuestionCat::find($id);
        return $model->title;
}
 catch (\Exception $e)
       {
           return;
  }
    }
    public function search(Request $request)
    {
        $inputs=$request->only([
            "title",
        ]);
        $model=Question::query()->where('question', 'like',  "%".implode(",",$inputs)."%" )
            ->where('status','=','1')
            ->get();
        return Response()->json($model,200);

    }
}

