<?php
namespace App\Classes;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;

class UserAuthenticator{

    public static function validator($token){
        if(Auth::check() && Auth::user() != null){
            return 1;
        }
        Auth::logout();
        if(!isset($token)){
            return 2;
        }
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
            return 0;
        }
        if($server_output == null){
            return 2;
        }
        $userObject = json_decode($server_output);
        if(!is_object($userObject)){
            echo 'hi';
            var_dump($userObject);
            return 2;
        }
        $exUserId = $userObject->data->id;
        $user = User::where('ex_user_id', $exUserId);
        if($user->count() == 0){
            return 2;
        }
        $user = $user->first();
        /*$user = DB::select(
            "SELECT * 
            FROM users 
            WHERE ex_user_id = $exUserId 
            LIMIT 1"
        );
        if(count($user) == 0){
            return 2;
        }*/

        //$user = $user[0];
        Auth::login($user);
        if(Auth::check()){
            return 1;
        }else{
            return 2;
        }
    }

}