<?php

namespace App\Http\Middleware;

use App\Classes\UserAuthenticator;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserAuthenticationMiddleware
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
        /*
        ##### this middleware is no longer used #####
        if(is_null($request->bearerToken())){
            echo json_encode(array('status' => 'failed', 'message' => 'token is missing'));
            exit();
        }
        if(Auth::check() && Auth::user() != NULL){
            $response = $next($request);
            return $response;
        }
        Auth::logout();
        $token = $request->bearerToken();
        // Sending given token to the AUTH server
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://auth.honari.com/api/check-token");
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['token' => $token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'Content-Length: ' . strlen(json_encode(['token' => $token]))
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $server_output = curl_exec ($ch);
        curl_close ($ch);
        if($server_output == '"user is not authenticate."'){
            echo json_encode(array('status' => 'failed', 'message' => 'received token is expired or incorrect'));
            exit();
        }
        if($server_output == null){
            echo json_encode(array('status' => 'failed', 'message' => 'something went wrong while accessing to the authenticator server'));
            exit();
        }
        $userObject = json_decode($server_output);
        if(!is_object($userObject)){
            echo json_encode(array('status' => 'failed', 'message' => 'wrong format of response from authentication server'));
            exit();
        }
        $exUserId = $userObject->data->id;
        $user = User::where('ex_user_id', $exUserId);
        if($user->count() == 0){
            echo json_encode(array('staus' => 'failed', 'message' => 'user not found'));
            exit();
        }
        $user = $user->first();
        Auth::login($user);
        if(Auth::check()){
            $response = $next($request);
            return $request;
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'could not login the user in the last step'));
            exit();
        }
        */
    }
}
