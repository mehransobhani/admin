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

class DiscountController extends Controller
{
    //@route: /api/user-check-gift-code <--> @middleware: ApiAuthenticationMiddleware
    public function checkGiftCode(Request $request){
        if(!isset($request->giftCode) || !isset($request->has)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'اطلاعات ورودی ناقص است'));
            exit();
        }
        $userId = $request->userId;
        $giftCode = $request->giftCode;
        $has = $request->has;
        $user = DB::select("SELECT * FROM users WHERE id = $userId");
        $user = $user[0];
        
        $provinceId = 0;
        $cityId = 0;

        $result = UserController::getProvinceId($user);
        if($result->successful){
            $provinceId = $result->provinceId;
        }else{
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => $result->message, 'umessage' => $result->umessage));
            exit();
        }

        $result = UserController::getCityId($user);
        if($result->successful){
            $cityId = $result->cityId;
        }else{
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => $result->message, 'umessage' => $result->umessage));
            exit();
        }

        $shoppingCart = DB::select("SELECT products FROM shoppingCarts WHERE user_id = $user->id AND active = 1");
        if(count($shoppingCart) == 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'users cart is empty', 'umessage' => 'سبد خرید کاربر خالی است'));
            exit();
        }
        $shoppingCart = $shoppingCart[0];
        if($shoppingCart->products == '' || $shoppingCart->products == '{}'){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'users cart is empty', 'umessage' => 'سبد خرید کاربر خالی است'));
            exit();
        }
        $shoppingCart = json_decode($shoppingCart->products);
        $cartPrice = 0;
        $productIds = [];
        $categoryIds = [];
        $totalWeight = 0;
        foreach($shoppingCart as $key => $value){
            $productInformation = DB::select(
                "SELECT P.id, PC.category, PP.price, P.prodWeight 
                FROM products P INNER JOIN product_pack PP ON P.id = PP.product_id INNER JOIN product_category PC ON P.id = PC.product_id
                WHERE PP.id = $key AND PP.status = 1 AND P.prodStatus = 1 AND P.stock > 0 AND PP.stock > 0 AND (PP.count * PP.stock <= P.stock) AND $value->count <= PP.stock
                LIMIT 1"
            );
            if(count($productInformation) === 0){
                echo json_encode(array('status' => 'redirect', 'source' => 'c', 'message' => 'product is not available', 'umessage' => 'کالایی از سبد خرید شما ناموجود شده است'));
                exit();
            }
            $productInformation = $productInformation[0];
            array_push($productIds, $productInformation->id);
            array_push($categoryIds, $productInformation->category);
            $cartPrice += ($productInformation->price * $value->count);
            $totalWeight += $productInformation->prodWeight;
        }

        $time = time();
        $tempo = DB::select(
            "SELECT service_id 
            FROM delivery_service_temporary_information
            WHERE user_id = $userId AND expiration_date >= $time 
            ORDER BY expiration_date DESC 
            LIMIT 1"
        );
        if(count($tempo) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'selected delivery service not found', 'umessage' => 'سرویس حمل و نقل انتخاب شده یافت نشد'));
            exit();
        }
        $tempo = $tempo[0];
        $deliveryServiceId = $tempo->service_id;
        $shippingPrice = DeliveryServiceController::calculateDeliveryPrice($deliveryServiceId, $provinceId, $cityId, $totalWeight, 12, 12);

        $result = DiscountCalculator::evaluateGiftCode($giftCode, $userId, $cartPrice, $shippingPrice, $provinceId, $productIds, $categoryIds, $has);
        echo json_encode($result);
    }

     // @route: /api/user-cart-and-shipping-prices-with-discounts <--> @middleware: UserAuthenticationMiddleware 
     public function cartAndShippingPricesWithDiscounts(Request $request){ 
        $userId = $request->userId; 
        $userCart = DB::select( 
            "SELECT * 
            FROM shoppingCarts 
            WHERE user_id = $userId AND active = 1 
            ORDER BY id DESC 
            LIMIT 1"
        );
        if(count($userCart) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'cart is empty', 'umessage' => 'سبد خرید خالی است'));
            exit();
        }
        $userCart = $userCart[0];
        
    }
    
}