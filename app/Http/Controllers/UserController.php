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
                        'balance' => $user->user_stock
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