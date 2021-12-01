<?php
namespace App\Classes;
use Illuminate\Support\Facades\DB;
use stdClass;

class ShippingCalculator{ 

    public static function shippingPriceCalculator($user, $totalWeight, $serviceId){
        $price = 0;
        if($user->address === '' || $user->address === NULL){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'user does not have address', 'umessage' => 'کاربر فاقد آدرس میباشد'));
            return NULL;
        }
        $addressPack = json_decode($user->address)->addressPack;
        if($addressPack->province == -1){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'user does not have address', 'umessage' => 'کاربر فاقد آدرس میباشد'));
            return NULL;
        }
        if($serviceId === 1){
            $price = 12000;
        }else if($serviceId === 2){
            $price = 15000;
        }else if($serviceId === 3){
            $provinceId = DB::select("SELECT id FROM provinces WHERE name = '$addressPack->province'");
            if(count($provinceId) == 0){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'province could not be found', 'umessage' => 'استان کاربر یافت نشد'));
                return NULL;
            }
            $provinceId = $provinceId[0];
            $provinceId = $provinceId->id;
            $cityId = DB::select("SELECT id FROM cities WHERE city = '$addressPack->city'");
            if(count($cityId) == 0){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'city could not be found', 'umessage' => 'شهر کاربر یافت نشد'));
                return NULL;
            }
            $cityId = $cityId[0];
            $cityId = $cityId->id;
            $pricePlans = DB::select("SELECT * FROM delivery_service_plans WHERE city_id = $cityId OR province_id = $provinceId ORDER BY min_weight ASC");
            if(count($pricePlans) == 0){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not find delivery price', 'umessage' => 'خطا هنگام محاسبه هزینه ارسال'));
                return NULL;
            }
            $found = false;
            foreach($pricePlans as $pp){
                if($pp->min_weight <= $totalWeight && $totalWeight < $pp->max_weight){
                    $found = true;
                    $price = $pp->price;
                    break;
                }
            }
            if($found === false){
                $lastPricePlan = DB::select("SELECT * FROM delivery_service_plans WHERE city_id = $cityId OR province_id = $provinceId ORDER BY max_weight DESC LIMIT 1");
                if(count($lastPricePlan) == 0){
                    echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could nto find delivery price', 'umessage' => 'خطا هنگام محاسبه هزینه ارسال'));
                    return NULL;
                }
                $lastPricePlan = $lastPricePlan[0];
                $price = $lastPricePlan->price;
                for($w1 = $lastPricePlan->max_weight, $w2 = $lastPricePlan->max_weight + 1000; $w1 < $w2 ;$w1 += 1000, $w2 += 1000){
                    $price += 2500;
                    if($w1 <= $totalWeight && $totalWeight < $w2){
                        $price= $price;
                        break;
                    }
                }
            }
        }
        return $price;
    }

}