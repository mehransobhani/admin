<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Classes\DiscountCalculator;
use stdClass;

class UserController extends Controller
{
    /*### without api route ###*/
    public function checkUser(Request $request){
        if(isset($request->username)){
            $username = $request->username;
            $user = DB::select("SELECT id FROM users WHERE username = $username");
            if(count($user) !== 0){
                $user = $user[0];
            }else{
                echo json_encode(array('status' => 'done', 'new' => true, 'message' => 'user is new'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    //@route: /api/user-information <--> @middleware: ApiAuthenticationMiddleware
    public function userInformation(Request $request){
        $userId = $request->userId;
        $user = DB::select("SELECT * FROM users WHERE id = $userId");
        $user = $user[0];
        $information = array(
                        'id' => $user->id, 
                        'username' => $user->username, 
                        'firstName' => $user->fname, 
                        'lastName' => $user->lname, 
                        'name' => $user->name, 
                        'email' => $user->email, 
                        'telephone' => $user->telephone, 
                        'mobile' => $user->mobile, 
                        'postalCode' => $user->postalCode, 
                        'nationalCode' => $user->national_code, 
                        'address' => $user->address, 
                        'latitude' => $user->lat, 
                        'longitude' => $user->lng,
                        'balance' => $user->user_stock,
                        'eui' => $user->ex_user_id,
                    );
        echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'user information successfully found', 'information' => $information));
    }

    //@route: /api/user-check-address <--> @middleware: ApiAuthenticationMiddleware
    public function checkUserAddress(Request $request){
        $userId = $request->userId;
        $user = DB::select("SELECT * FROM users WHERE id = $userId");
        if(count($user) == 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'user not found', 'umessage' => 'کاربر موردنظر یافت نشد'));
            exit();
        }
        $user = $user[0];
        $userHasAddress = false;
        if($user->address !== NULL && $user->address !== ''){
            $userHasAddress = true;
        }
        echo json_encode(array('status' => 'found', 'message' => 'successfully got user information'));
    }

    //@route: /api/UserUpdate <--> @middleware: ApiAuthenticationMiddleware
    /*public function updateUser(Request $request){
        $token = '';
        if(!isset($request->hasHeader('token')) || !isset($request->id)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough information', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $token = $request->hasHeader('token');
        $exUserId = $request->id;
        $user = DB::select(
            "SELECT * FROM users WHERE ex_user_id = $exUserId "
        );
        if(count($user) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'user not found', 'umessage' => 'کاربر یافت نشد'));
            exit();
        }
        $user = $user[0];
        
        $provinceName = isset($request->province_name) ? $request->province_name : '';
        $cityName = isset($request->city_name) ? $request->city_name : '';
        $postal = isset($request->postal_code) ? trim($request->postal_code) : '';
        $address = isset($request->address_text) ? trim($request->address_text) : '';

        $addressArray = [
            'addressPack' => [
                'province' => $provinceName,
                'city' => $cityName,
                'postal' => $postal,
                'address' => $address
            ]
        ];

        $newUser = new stdClass();
        $newUser->fname = isset($request->fname) ? $request->fname : $user->fname;
        $newUser->lname = isset($request->lname) ? $request->lname : $user->lname;
        $newUser->name = isset($request->name) ? $request->name : $user->name;
        $newUser->email = isset($request->email) ? $request->email : $user->email;
        $newUser->telephone = isset($request->telephone) ? $request->telephone : $user->telephone;
        $newUser->postalCode = isset($request->postal_code) ? $request->postal_code : $user->postalCode;
        $newUser->national_code = isset($request->national_code) ? $request->national_code : $user->national_code;
        $newUser->address = (empty($provinceName) && empty($cityName) && empty($postal) && empty($address)) ? $user->address : json_encode($addressArray, JSON_UNESCAPED_UNICODE);
        $newUser->lat = isset($request->lat) ? $request->lat : $user->lat;
        $newUser->lng = isset($request->lng) ? $request->lng : $user->lng;
        $newUser->profilepic = isset($request->profilepic) ? $request->profilepic : $user->profilepic;
        $newUser->mobile = isset($request->mobile) ? $request->mobile : $user->mobile;
        $newUser->role = isset($request->role) ? $request->role : $user->role;

        foreach($newUser as $key => $value){
            if($value === NULL){
                $newUser->$key = "NULL";
            }
        }

        $updateResult = DB::update(
            "UPDATE users SET 
            fname = '$newUser->fname', lname = '$newUser->lname', 
            name = '$newUser->name', email = '$newUser->email', 
            telephone = '$newUser->telephone', postalCode = '$newUser->postalCode', 
            national_code = '$newUser->national_code', `address` = '$newUser->address', 
            lat = $newUser->lat, lng = $newUser->lng, 
            profilepic = '$newUser->profilepic', mobile = '$newUser->mobile', `role` = '$newUser->role' 
            WHERE id = $user->id"
        );

        if(!$updateResult){
            echo json_encode(array('status' => 'faile', 'source' => 'sql', 'message' => 'an error while updating users information', 'umessage' => 'خطا در بروزرسانی اطلاعات کاربر'));
            exit();
        }
        echo json_encode(array('status' => 'done', 'message' => 'user successfully updated'));
    }*/

    public function createuserKey($username){
        $hash = md5('amin' . time()) . 'honari' . md5(strrev($username) . 'behnam');
        return md5($hash);
    }

    public function createUserAddress($user){
        if($user->address === NULL || $user->adderss === ''){
            return '';
        }
        $address = new stdClass();
        $addressPack = new stdClass();
        $addressPack->province = $user->get_province->name;
        $addressPack->city = $user->get_city->city;
        $addressPack->postal = $user->postalCode;
        $addressPack->address = $user->address;
        $address->addressPack = $addressPack;
        return json_encode($address);
    }

    public function addUser($token){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://auth.honari.com/api/user-info");
        //curl_setopt($ch, CURLOPT_POSTFIELDS, ['token' => $token]);
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
            $response = new stdClass();
            $response->status = 'failed';
            $response->message = 'token is not valid';
            $response->umessage = 'کاربر شناسایی نشد';
            return $response;
        }
        if($server_output == null){
            $response = new stdClass();
            $response->status = 'failed';
            $response->message = 'could not connect to user server';
            $response->umessage = 'خطا در دسترسی به اطلاعات کاربر';
            return $response;
        }
        $userObject = json_decode($server_output);
        if(!is_object($userObject)){
            $response = new stdClass();
            $response->status = 'failed';
            $response->message = 'response is not json';
            $response->umessage = 'خطا در نوع پاسخ دریافتی';
            return $response;
        }
        $time = time();

        /*user table:
        #username: string mobile
        #password: hashedkpassword which is reserve : e10adc3949ba59abbe56e057f20f883e
        #userlevel: 0
        email: user email string it should be empty for user that does not have email
        hubspot_mail: ...
        timestamp: date of user first log in
        valid: 0
        name: full name string
        profilepic: ''
        mobile: phone number
        telephone: ''
        postalCode: ''
        address: ''
        orders_count: 0
        total_buy: 0
        role: ''
        token: ''
        gcmToken: ''
        newGcmToken: ''
        androidToken: ''
        user_stock: 0,
        fname: '',
        lname: ''
        selectedArts = ''
        area: 0
        giftcode: 0
        followers: 0
        following: 0
        user_key: ? string
        can_cash_pay: 1
        last_update: string timestamp of last update
        v_id: NULL
        national_code: NULL
        lat: NULL,
        lng: NULL,
        ex_user_id: <id></id>
                                */

        $urlKey = $this->createuserKey($userObject->username);

        $userAddress = $this->createUserAddress($userObject);

        DB::insert(
            "INSERT INTO users (
                username , `password`, 
                userlevel: 0, email, 
                hubspot_mail, `timestamp`: $time, 
                valid, `name`, 
                profilepic: '', mobile, 
                telephone, postalcode, 
                `address`, orders_count: 0, 
                total_buy: 0, `role`, 
                token: '', gcmToken: '', 
                newGcmToken: '', androidToken: '', 
                user_stock: 0, fname, 
                lname, selectedArts: '', 
                area: 0, giftcode: 0, 
                followers: 0, followings: 0, 
                user_key, can_cash_pay: 1, 
                last_update: $time, v_id: NULL, 
                national_code: NULL, lat,
                lng, ex_user_id 
            ) VALUES (
                '$userObject->username' 'e10adc3949ba59abbe56e057f20f883e', 
                0, '$userObject->email', 
                '$userObject->email', $time, 
                0, '$userObject->name', 
                '', '$userObject->username', 
                '$userObject->telephone', '$userObject->postalCode', 
                '$userAddress', 0, 
                0, '$userObject->role', 
                '', '', 
                '', '', 
                0, $userObject->fname, 
                $userObject->lname, '', 
                0, 0, 
                0, , 
                '', 1, 
                $time, NULL, 
                NULL, $userObject->lat, 
                $userObject->lng, $userObject->id
            )"
        );

        /*

        #################################################################
{
    "success": true,
    "data": {
        "user": {
            "id": 240308,
            "username": "09109495026",
            "email": "hadi1998goodboy@gmail.com",
            "name": "هادی حسین پور",
            "profilepic": null,
            "mobile": "09109495026",
            "telephone": "02112345678",
            "postalCode": "1234567891",
            "address": "آدرس - خیابان - کوچه - پلاک",
            "role": "admin",
            "fname": "هادی",
            "lname": "حسین پور",
            "national_code": "0020978431",
            "lat": 35.6651,
            "lng": 51.0562,
            "province_id": 1,
            "city_id": 3351,
            "address_2": null,
            "can_password_reset": 1,
            "get_province": {
                "id": 1,
                "name": "تهران",
                "iso_code": "1"
            },
            "get_city": {
                "id": 3351,
                "parent_province": 1,
                "city": "شهریار",
                "fee": 0,
                "status": 1
            }
        }
    },
    "message": "here is user information."
}*/
        /*DB::insert(
            "INSERT INTO users 
            SET "
        );*/
    }

}

/*namespace App\Http\Controllers\API;
use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 
use App\User; 
use Illuminate\Support\Facades\Auth; 
use Validator;
class UserController extends Controller 
{
public $successStatus = 200;

    public function login(){ 
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){ 
            $user = Auth::user(); 
            $success['token'] =  $user->createToken('MyApp')-> accessToken; 
            return response()->json(['success' => $success], $this-> successStatus); 
        } 
        else{ 
            return response()->json(['error'=>'Unauthorised'], 401); 
        } 
    }

    public function register(Request $request) 
    { 
        $validator = Validator::make($request->all(), [ 
            'name' => 'required', 
            'email' => 'required|email', 
            'password' => 'required', 
            'c_password' => 'required|same:password', 
        ]);
if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }
$input = $request->all(); 
        $input['password'] = bcrypt($input['password']); 
        $user = User::create($input); 
        $success['token'] =  $user->createToken('MyApp')-> accessToken; 
        $success['name'] =  $user->name;
return response()->json(['success'=>$success], $this-> successStatus); 
    }

    public function details() 
    { 
        $user = Auth::user(); 
        return response()->json(['success' => $user], $this-> successStatus); 
    } 
}
*/