<?php

namespace App\Http\Middleware;

use App\Classes\UserAuthenticator;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /*if(Auth::guard('api')->check()){
            echo 'user is already logged in';
            exit();
        }else{
            echo 'user is not logged in';
            $user = User::where('id', 238856)->first();
            Auth::guard('api')->attempt(['id' => 238856]);
            if(Auth::guard('api')->check()){
                echo 'user now logged in';
                exit();
            }
        }*/
        //echo session('hadi');
        /*$all = session()->all();
        var_dump($all);
        if(session()->exists('hadi')){
            echo session('hadi');
        }else{
            session(['hadi' => 'hosseinpour']);
            echo 'session successfully created';
        }*/
        /*$value = session()->get('hadi');
        if($value !== null){
            echo session()->get('hadi');
        }else{
            session()->put('hadi', 'hosseinpour');
            echo 'session successfully created <br>';
        }*/
        //session_start();
        /*if(isset($_SESSION['hadi'])){
            echo $_SESSION['hadi'];
        }else{
            $_SESSION['hadi'] = 'hosseinpour';
            echo 'session successfully created';
        }*/
        return $next($request);
    }
}