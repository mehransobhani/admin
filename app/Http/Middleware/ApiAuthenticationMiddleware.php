<?php

namespace App\Http\Middleware;

use App\Classes\UserAuthenticator;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiAuthenticationMiddleware
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
        if($request->bearerToken() == NULL){
            echo json_encode(array('status' => 'failed', 'source' => 'middleware', 'reason' => 'missingHeader', 'message' => 'user token is missing'));
            exit();
        }
        $token = $request->bearerToken();
        $userAuthentication = DB::select("SELECT * FROM users_authentication_tokens WHERE token = '$token' ORDER BY expiration_date DESC LIMIT 1");
        if(count($userAuthentication) == 0){
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
            if($server_output == 'user is not authenticate.'){
                echo json_encode(array('status' => 'failed', 'source' => 'middleware', 'reason' => 'wrongHeader', 'message' => 'wrong token'));
                exit();
            }
		    if($server_output == NULL){
                echo json_encode(array('status' => 'failed', 'source' => 'middleware', 'reason' => 'authConnection', 'message' => 'auth server connection error'));
                exit();     
            }
            $userObject = json_decode($server_output);
		    if(!is_object($userObject)){
                echo json_encode(array('status' => 'failed', 'source' => 'middleware', 'reason' => 'authResponse', 'message' => 'auth server response error', 'token' => $token));
                exit();
            }
            $userObject = $userObject->data;
            //$user = User::where('ex_user_id', $userObject->data->id)->orderBy('id', 'DESC')->first();
            $user = DB::select("SELECT id from users WHERE ex_user_id = $userObject->id LIMIT 1");
            if(count($user) == 0){
                echo json_encode(array('status' => 'failed', 'source' => 'middleware', 'reason' => 'missingUser', 'message' => 'user does not exist yet'));
                exit();
            }
            $user = $user[0];
            $insertQueryResult = DB::insert("INSERT INTO users_authentication_tokens (token, user_id, status, expiration_date) values ('$token', $user->id, 1, $userObject->token_expires_at)");
            if($insertQueryResult){
                $request->userId = $user->id;
                return $next($request);
            }else{
                echo json_encode(array('status' => 'failed', 'source' => 'middleware', 'reason' => 'queryResultError', 'message' => 'an error occured while inserting a new token'));
                exit();
            }
        }else{
            $userAuthentication = $userAuthentication[0];
            if($userAuthentication->status == 0){
                echo json_encode(array('status' => 'failed', 'source' => 'middleware', 'reason' => 'tokenInvalid', 'message' => 'token is not valid'));
                exit();
            }
            if($userAuthentication->expiration_date < time()){
                echo json_encode(array('status' => 'failed', 'source' => 'middleware', 'reason' => 'tokenExpired', 'message' => 'token is expired'));
                exit();
            }
            $request->userId = $userAuthentication->user_id;
            return $next($request);
            exit();
        }
    }
}