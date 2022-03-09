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
            $user = DB::select("SELECT id from users WHERE ex_user_id = $userObject->id LIMIT 1");
            if(count($user) == 0){
                
                $date = time();
                DB::insert(
                    "INSERT INTO users (
                        username, userlevel, 
                        email, hubspot_email, 
                        `timestamp`, `name`, 
                        profilepic, mobile, 
                        telephone, postalCode, 
                        `address`, orders_count, 
                        total_buy, `role`,  
                        token, gcmToken, newGcmToken, androidToken, 
                        user_stock, 
                        fname, lname, 
                        selectedArts, 
                        area, giftcode, followers, `following`, 
                        user_key, ex_user_id
                    ) VALUES (
                        '$userObject->username', 0,
                        '$userObject->email', '$userObject->hubspot_mail' ,
                        $date, '$userObject->name', 
                        '', '$userObject->mobile', 
                        '', '', 
                        '', 0, 
                        0, '$userObject->role', 
                        '', '', '', '', 
                        0, 
                        '', '', 
                        '', 
                        0, 0, 0, 0, 
                        '', $userObject->id
                    ) "
                );
                $user = DB::select("SELECT * FROM uses WHERE ex_user_id = $userObject->id LIMIT 1");
                if(count($user) === 0){
                    echo json_encode(array('status' => 'failed', 'source' => 'm', 'message' => 'could not create the new user', 'umessage' => 'خطا در ایجاد کاربر جدید'));
                    exit();
                }else{
                    $user = $user[0];
                    $request->userId = $user->id;
                    DB::insert("INSERT INTO users_authentication_tokens (token, user_id, status, expiration_date) values ('$token', $user->id, 1, $userObject->token_expires_at)");
                    return $next($request);
                }
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