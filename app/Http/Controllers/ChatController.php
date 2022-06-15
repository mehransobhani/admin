<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Classes\DiscountCalculator;
use Illuminate\Support\Facades\Validator;
use stdClass;

class ChatController extends Controller
{
    public function userCrmInformation(Request $request){
	set_time_limit(4);
        $user = DB::select("SELECT * FROM users WHERE id = $request->userId");
        $user = $user[0];
        $key = '12345^&*(H0n@r!54321)*&^54321';
        $response = new stdClass();
        $response->orderNumber = $user->orders_count;
        $response->orderTotal = $user->total_buy;
        $response->lastOrderDate = null;
        $response->lastOrderLink = null;
        $response->courses = null;
        $response->coursesAmount = null;
        $response->coursesCount = null;
        $response->city = null;
	$response->token = null;
        $response->name = $user->name;
        $response->username = $user->username;
        $response->email = $user->email;

        if($user->address != null && $user->address != ''){
            $address = json_decode($user->address);
            $response->city = $address->addressPack->city;
        }

        $lastOrder = DB::select("SELECT O.id, O.date FROM orders O INNER JOIN users U ON U.id = O.id WHERE U.id = $user->id AND O.stat not in (6, 7) ORDER BY O.id DESC LIMIT 1");
        if(count($lastOrder) > 0){
            $lastOrder = $lastOrder[0];
            $response->lastOrderLink = 'https://admin.honari.com/orders/factor/' . $lastOrder->id;
            $response->lastOrderDate = date('Y-m-d', $lastOrder->date);
	}
        $ch = curl_init('https://academy.honari.com/api/user-crm-info');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['exUserId' => $user->ex_user_id]);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: multipart/form-data',
            'token: ' . md5(md5($user->ex_user_id . "." . $key))),
        );

        $result = curl_exec($ch);

        $result = json_decode($result);

        if($result->status == 'done'){
            $response->courses = $result->courses;
            $response->coursesCount = $result->coursesCount;
            $response->coursesAmount = $result->purchasedAmount;
        }

	if($result->status == 'done'){
            $response->courses = $result->courses;
            $response->coursesCount = $result->coursesCount;
            $response->coursesAmount = $result->purchasedAmount;
        }

        $response->token = hash_hmac('sha256',$user->ex_user_id, 'bthZfEnUg5n4rGer19BLqak1');

	    curl_close($ch);

        echo json_encode(array('status' => 'done', 'message' => 'data successfully found', 'information' => $response));

    }
}

