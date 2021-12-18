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
        if($user->address === '' || $user->address === NULL){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'user does not have address', 'umessage' => 'کاربر فاقد آدرس میباشد'));
            exit();
        }
        $addressPack = json_decode($user->address)->addressPack;
        if($addressPack->province == -1){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'user does not have address', 'umessage' => 'کاربر فاقد آدرس میباشد'));
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
        $provinceId = 0;
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
        $provinceId = DB::select("SELECT id FROM provinces WHERE name = '$addressPack->province'");
        if(count($provinceId) == 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'province could not be found', 'umessage' => 'استان کاربر یافت نشد'));
            exit();
        }
        $provinceId = $provinceId[0];
        $provinceId = $provinceId->id;
        $cityId = DB::select("SELECT id FROM cities WHERE city = '$addressPack->city'");
        if(count($cityId) == 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'city could not be found', 'umessage' => 'شهر کاربر یافت نشد'));
            exit();
        }
        $cityId = $cityId[0];
        $cityId = $cityId->id;

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
        $shippingPrice = 0;
        if($deliveryServiceId == 11){
            $shippingPrice = 12000;   
        }else if($deliveryServiceId == 12){
            $shippingPrice = 15000;
        }
        else if($deliveryServiceId == 3){
            $pricePlans = DB::select("SELECT * FROM delivery_service_plans WHERE city_id = $cityId OR province_id = $provinceId ORDER BY min_weight ASC");
            if(count($pricePlans) == 0){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not find delivery price', 'umessage' => 'خطا هنگام محاسبه هزینه ارسال'));
                exit();
            }
            $found = false;
            foreach($pricePlans as $pp){
                if($pp->min_weight <= $totalWeight && $totalWeight < $pp->max_weight){
                    $found = true;
                    $shippingPrice = $pp->price;
                    break;
                }
            }
            if($found === false){
                $lastPricePlan = DB::select("SELECT * FROM delivery_service_plans WHERE city_id = $cityId OR province_id = $provinceId ORDER BY max_weight DESC LIMIT 1");
                if(count($lastPricePlan) == 0){
                    echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could nto find delivery price', 'umessage' => 'خطا هنگام محاسبه هزینه ارسال'));
                    exit();
                }
                $lastPricePlan = $lastPricePlan[0];
                $price = $lastPricePlan->price;
                for($w1 = $lastPricePlan->max_weight, $w2 = $lastPricePlan->max_weight + 1000; $w1 < $w2 ;$w1 += 1000, $w2 += 1000){
                    $price += 2500;
                    if($w1 <= $totalWeight && $totalWeight < $w2){
                        $shippingPrice= $price;
                        break;
                    }
                }
            }
        }
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