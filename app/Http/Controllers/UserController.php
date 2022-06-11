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
use Exception;
use Illuminate\Support\Facades\Validator;
use stdClass;

class UserController extends Controller
{
    public static function getProvinceId($user){
        $responseObject = new stdClass();
        if($user->address === '' || $user->address === NULL){
            $responseObject->successful = false;
            $responseObject->message = "user does not have address";
            $responseObject->umessage = "کاربر فاقد آدرس میباشد";
            $responseObject->provinceId = null;
            return $responseObject;
        }

        try{
            $addressPack = json_decode($user->address)->addressPack;
        }catch(Exception $e){
            $responseObject->successful = false;
            $responseObject->message = "user address has wrong format";
            $responseObject->umessage = "خطا هنگام خواندن آدرس کابر";
            $responseObject->provinceId = null;
            return $responseObject;
        }

        if($addressPack->province == -1){
            $responseObject->successful = false;
            $responseObject->message = "user does not have address";
            $responseObject->umessage = "کاربر فاقد آدرس میباشد";
            $responseObject->provinceId = null;
            return $responseObject;
        }

        $provinceId = DB::select("SELECT id FROM provinces WHERE name = '$addressPack->province'");
        if(count($provinceId) == 0){
            $responseObject->successful = false;
            $responseObject->message = "province could not be found";
            $responseObject->umessage = "استان کاربر یافت نشد";
            $responseObject->provinceId = null;
            return $responseObject;
        }

        $provinceId = $provinceId[0];
        $provinceId = $provinceId->id;

        $responseObject->successful = true;
        $responseObject->message = "province id successfully found";
        $responseObject->umessage = "کد استان کاربر با موفقیت یافت شد";
        $responseObject->provinceId = $provinceId;
        return $responseObject;

        $cityId = DB::select("SELECT id FROM cities WHERE city = '$addressPack->city'");
        if(count($cityId) == 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'city could not be found', 'umessage' => 'شهر کاربر یافت نشد'));
            return NULL;
        }
        $cityId = $cityId[0];
        $cityId = $cityId->id;
    }

    public static function getCityId($user){
        $responseObject = new stdClass();
        if($user->address === '' || $user->address === NULL){
            $responseObject->successful = false;
            $responseObject->message = "user does not have address";
            $responseObject->umessage = "کاربر فاقد آدرس میباشد";
            $responseObject->cityId = null;
            return $responseObject;
        }

        try{
            $addressPack = json_decode($user->address)->addressPack;
        }catch(Exception $e){
            $responseObject->successful = false;
            $responseObject->message = "user address has wrong format";
            $responseObject->umessage = "خطا هنگام خواندن آدرس کابر";
            $responseObject->cityId = null;
            return $responseObject;
        }

        if($addressPack->province == -1){
            $responseObject->successful = false;
            $responseObject->message = "user does not have address";
            $responseObject->umessage = "کاربر فاقد آدرس میباشد";
            $responseObject->cityId = null;
            return $responseObject;
        }
        
        $cityId = DB::select("SELECT id FROM cities WHERE city = '$addressPack->city'");
        if(count($cityId) == 0){
            $responseObject->successful = false;
            $responseObject->message = "user city could not be found";
            $responseObject->umessage = "شهر کاربر یافت نشد";
            $responseObject->cityId = null;
            return $responseObject;
        }
        
        $cityId = $cityId[0];
        $cityId = $cityId->id;

        $responseObject->successful = true;
        $responseObject->message = "city id successfully found";
        $responseObject->umessage = "کد شهر کاربر با موفقیت یافت شد";
        $responseObject->cityId = $cityId;
        return $responseObject;
    }

    public static function getUserAddress($user){
        $responseObject = new stdClass();
        if($user->address === '' || $user->address === NULL){
            $responseObject->successful = false;
            $responseObject->message = "user does not have address";
            $responseObject->umessage = "کاربر فاقد آدرس میباشد";
            $responseObject->cityId = null;
            return $responseObject;
        }

        try{
            $addressPack = json_decode($user->address)->addressPack;
        }catch(Exception $e){
            $responseObject->successful = false;
            $responseObject->message = "user address has wrong format";
            $responseObject->umessage = "خطا هنگام خواندن آدرس کابر";
            $responseObject->cityId = null;
            return $responseObject;
        }

        $responseObject->successful = true;
        $responseObject->message = "user address successfully found";
        $responseObject->umessage = "آدرس کاربر با موفقیت دریافت شد";
        $responseObject->address = $addressPack->address;
        return $responseObject;
    }

    /*### without api route ###*/
    public function checkUser(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'required|string', 
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $username = $request->username;
        $user = DB::select("SELECT id FROM users WHERE username = $username");
        if(count($user) !== 0){
            $user = $user[0];
        }else{
            echo json_encode(array('status' => 'done', 'new' => true, 'message' => 'user is new'));
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
			'role' => $user->role,   
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
    public function updateUser(Request $request){
        $headers = $request->header();
        if(!isset($headers['token'])){
            echo json_encode(array('status' => 'failed', 'source' => 'm', 'message' => 'header is missing', 'umessage' => 'بروز خطا در شناسایی مبدا'));
            exit();
        }

        $headerToken = $headers['token'][0];

        $parameters = $request->json()->all();
        
        $key = "12345^&*(H0n@r!54321)*&^54321";

        if(!isset($parameters['id'])){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'messsage' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }

        if($headerToken !== md5(md5($parameters['id'] . "." . $key))){
            echo json_encode(array('status' => 'failed', 'source' => 'm', 'message' => 'user is not authenticated', 'umessage' => 'خطا در شناسایی اطلاعات مبدا'));
            exit();
        }

        if(!isset($parameters['fname'])){
            $exUserId = $parameters['id'];
            $name = $parameters['name'];
            $email = $parameters['email'];
            $user = DB::select("SELECT * FROM users WHERE ex_user_id = $exUserId LIMIT 1 ");
            if(count($user) === 0){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'user is not created yet', 'umessage' => 'کاربر درحال حاضر ایجاد نشده است'));
                exit();
            }
            DB::update(
                "UPDATE users 
                SET `name` = '$name', email = '$email' 
                WHERE ex_user_id = $exUserId "
            );
            echo json_encode(array('status' => 'done', 'message' => 'users information successfully updated'));
            exit();
        }else{
            $exUserId = $parameters['id'];
            $fname = $parameters['fname'];
            $lname = $parameters['lname'];
            $mobile = $parameters['mobile'];
            $telephone = $parameters['telephone'];
            $postalCode = $parameters['postal_code'];
            $nationalCode = $parameters['national_code'];
            $lat = $parameters['lat'];
            $lng = $parameters['lng'];
            $provinceName = $parameters['province_name'];
            $cityName = $parameters['city_name'];
            $addressText = $parameters['address_text'];

            if($lat === null){
                $lat = ' NULL ';
            }
            if($lng === null){
                $lng = ' NULL ';
            }

            $user = DB::select("SELECT * FROM users WHERE ex_user_id = $exUserId LIMIT 1");
                
            if(count($user) !== 0){
                //{"addressPack":{"province":"تهران","city":"تهران","postal":"1453683374","address":"خیابان سازمان آب بین پل یادگار و خ نیرو پلاک۱۵۴ واحد۶"}}
                $addressObject = new stdClass();
                $addressObject->addressPack = new stdClass();
                $addressObject->addressPack->province = $provinceName;
                $addressObject->addressPack->city = $cityName;
                $addressObject->addressPack->postal = $postalCode;
                $addressObject->addressPack->address = $addressText;
                $addressString = json_encode($addressObject);
                DB::update(
                    "UPDATE users 
                    SET fname  = '$fname' ,
                        lname = '$lname' ,
                        mobile = '$mobile' , 
                        telephone = '$telephone' , 
                        postalCode = '$postalCode' , 
                        national_code = '$nationalCode' ,
                        `address` = '$addressString' 
                    WHERE ex_user_id = $exUserId "
                );
                echo json_encode(array('status' => 'done', 'message' => 'users address successfully updated'));
                exit();
            }else{
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'user does not exist', 'umessage' => 'کاربر وجود ندارد'));
                exit();
            }
        }

        /*
        $exUserId = $request->id;
        $key = "12345^&*(H0n@r!54321)*&^54321";
        $generagedKey = md5(md5($exUserId . "." . $key));
        if($generagedKey !== $headerToken){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'headers mismatch'));
            exit();
        }
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
            WHERE id = $user->id "
        );

        if(!$updateResult){
            echo json_encode(array('status' => 'faile', 'source' => 'sql', 'message' => 'an error while updating users information', 'umessage' => 'خطا در بروزرسانی اطلاعات کاربر'));
            exit();
        }
        echo json_encode(array('status' => 'done', 'message' => 'user successfully updated'));
        */
    }

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



    
}

