<?php

namespace App\Http\Controllers;

use App\Classes\UserAuthenticator;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class TestingController extends Controller
{
    public function testingByPostMethod(Request $request){
        echo 'hello world';
        if($request->bearerToken() == null){
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
            exit();
        }
        $token = $request->bearerToken();
        echo UserAuthenticator::validator($token);
    }

    public function testingByGetMethod(Request $request){

    }
}