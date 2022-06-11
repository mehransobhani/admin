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
use GrahamCampbell\ResultType\Result;
use App\Classes\payment\pasargad\RSAProcessor;
use App\Classes\payment\pasargad\Pasargad;
use Illuminate\Support\Facades\Validator;
use stdClass;

class OrderController extends Controller
{
    /*### without api route ###*/
    public function getUserOrderProducts (Request $request){
        /*
        if(Auth::check()){
            echo json_encode(array('status' => 'failed', 'message' => 'user is not authenticated'));
            exit();
        }
        $user = Auth::user();
        if($user == null){
            echo json_encode(array('status' => 'failed', 'message' => 'user is not authenticated'));
            exit();
        }
        if(!isset($request->order_id)){
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
            exit();
        }
        $order = DB::select("SELECT * FROM orders WHERE id = $request->order_id ORDER BY date DESC LIMIT 1");
        if(count($order) == 0){
            echo json_encode(array('status' => 'failed', 'message' => 'order not found'));
            exit();
        }
        $order = $order[0];
        $orderItems = DB::select("SELECT * FROM order_items WHERE order_id = $order->id ORDER BY id ASC");
        if(count($orderItems) !== 0){
            $responseArray = array();
            foreach($orderItems as $oi){
                $discount = 0;
                if($oi->off > 0 && $oi->off2 == 0){
                    $discount = $oi->off;
                }else if($oi->off ==0 && $oi->off2 > 0){
                    $discount = $oi->off2;
                }else if($oi->off > 0 && $oi->off2 > 0){
                    if($oi->off > $oi->off2){
                        $discount = $oi->off2;
                    }else if($oi->off2 > $oi->off){
                        $discount = $oi->off;
                    }else{
                        $discount = $oi->off;
                    }
                }
                $discount = $discount / 100;
                $discount = $discount * 100;
                array_push($responseArray, array('product_id' => $oi->product_id, 'count' => $oi->count, 'pack_name' => $oi->pack_name, 'price' => $oi->price, 'off' => $oi->discount));
            }
        }else{
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'this order does not have any items', 'items' => '[]'));
        }
        */
    }

    /*### wihtout api route ###*/
    public function getUsersOrders(Request $request){
        $userId = $request->userId;
        $orders = DB::select("SELECT * FROM orders WHERE user_id = " . $userId . " ");
        if(count($orders) == 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'this user does not have any previous order', 'orders' => '[]'));
            exit();
        }
        $responseArray = array();
        foreach($orders as $o){
            $paid = 0;
            $cardLog = DB::select("SELECT * FROM transaction WHERE order_id = $o->id");
            $walletLog = DB::select("SELECT * FROM users_stock WHERE order_id = $o->id AND type = 6");
            if(count($cardLog) !== 0){
                $paid += $cardLog[0]->price;
            }
            if(count($walletLog) !== 0){
                $paid -= $walletLog[0]->chagned_count;
            }
            array_push($responseArray, array('id' => $o->id, 'itemsCount' => 2, 'price' => $paid, 'date' => jdate('Y-m-d H:i', $o->date)));
        }
    }

    // @route: /api/user-order-details <--> @middleware: UserAuthenticationMiddleware
    public function getOrderDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'orderId' => 'required|numeric', 
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $userId = $request->userId;
        $orderId = $request->orderId;
        $user = DB::select("SELECT username FROM users WHERE id = $userId LIMIT 1");
        $user = $user[0];
        $order = DB::select(
            "SELECT * 
            FROM orders 
            WHERE user_id = $userId AND id = $request->orderId"
        );
        if(count($order) === 0){
            echo json_encode(array('status' => 'failed', 'message' => 'order not found'));
            exit();
        }
        $order = $order[0];
        $orderItems = DB::select(
                        "SELECT P.id AS productId, P.prodName_fa AS productName, P.prodUnite AS productUnit, P.prodID, P.url, OI.pack_name AS packName, OI.count, OI.pack_count AS packCount, OI.price AS basePrice, OI.off AS discount, OI.sell_price AS sellPrice, OI.tax_price AS taxPrice, OI.duty_price AS dutyPrice 
                        FROM order_items OI INNER JOIN products P ON OI.product_id = P.id
                        WHERE OI.order_id = $orderId 
                        ORDER BY OI.id ASC"
                    );
        if(count($orderItems) == 0){
            echo json_encode(
                array(
                    'status' => 'failed',
                    'found' => false, 
                    'paid_price' => 0,
                    'order_items' => '[]', 
                    'message' => 'this order does not have any item'
                )
            );
            exit();
        }
        $totalPrice = $order->total_items + $order->shipping_cost;
        $totalDiscount = $order->off + $order->shipping_price_off;
        /*$transactionRecord = DB::select("SELECT * FROM transaction WHERE user = $user->username AND order_id = $orderId");
        $walletRecord = DB::select("SELECT * FROM users_stock WHERE user_id = $userId AND order_id = $orderId AND type = 6");
        if(count($transactionRecord) !== 0){
            $paidPrice += $transactionRecord[0]->price;
        }
        if(count($walletRecord) !== 0){
            $paidPrice -= $walletRecord[0]->changed_count;
        }*/

        echo json_encode(array('status' => 'done', 'found' => true, 'orderStatus' => $order->stat, 'totalPrice' => $totalPrice, 'totalDiscount' => $totalDiscount, 'orderItems' => $orderItems, 'message' => 'order items successfully found'));
    }

    // @route: /api/user-order-details <--> @middleware: UserAuthenticationMiddleware
    public function getUserOrdersHistory(Request $request){
        $userId = $request->userId;
        $user = DB::select("SELECT * FROM users WHERE id = $userId");
        $user = $user[0];
        $orders = DB::select(
            "SELECT O.id, O.orderReferenceID, O.postReferenceID, O.date, OI.address, OI.postal_code, OI.fname, OI.lname, O.total_items, O.shipping_cost, O.stat 
            FROM orders O INNER JOIN order_info OI ON O.info_id = OI.id 
            WHERE O.user_id = $user->id AND O.stat NOT IN (6, 7) 
            ORDER BY O.date DESC");
        if(count($orders) !== 0){
            $responseArray = array();
            foreach($orders as $o){
                array_push($responseArray, array('id' => $o->id, 'status' => $o->stat, 'date' => jdate('Y-m-d', $o->date), 'd' => (time() - $o->date), 
                    'price' => ($o->total_items + $o->shipping_cost), 'orderReferenceId' => $o->orderReferenceID, 'postReferenceId' => $o->postReferenceID, 'postalCode' => $o->postal_code, 'firstName' => $o->fname, 'lastName' => $o->lname));
            }
            echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'successfully found previous orders', 'orders' => $responseArray));
        }else{
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any previous order', 'orders' => '[]'));
        }
    }

    // @route: /api/user-confirm-order <--> @middleware: UserAuthenticationMiddleware
    public function confirmOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'giftCodes' => 'array',
            'userWallet' => 'numeric', 
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $time = time();
        
        $userId = $request->userId;
        $codes = $request->giftCodes;
        $useUserStock = $request->userWallet;
        $user = DB::select(
            "SELECT * 
            FROM users 
            WHERE id = $userId 
            LIMIT 1"
        );
        $user = $user[0];

        $provinceId = 0;
        $cityId = 0;
        $userAddress = '';

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

        $result = UserController::getUserAddress($user);
        if($result->successful){
            $userAddress = $result->address;
        }else{
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => $result->message, 'umessage' => $result->umessage));
            exit();
        }

        $totalWeight = 0;

        $cart = DB::select(
            "SELECT id, products 
            FROM shoppingCarts 
            WHERE user_id = $userId AND active = 1
            ORDER BY timestamp DESC
            LIMIT 1"
        );
        if(count($cart) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'cart not found', 'umessage' => 'سبدخرید یافت نشد'));
            exit();
        }
        $cartProducts = [];
        $totalBuyPrice = 0;
        $cart = $cart[0];
        $cartProductsObject = json_decode($cart->products);
        foreach($cartProductsObject as $key => $value){
            $productInfo = DB::select(
                "SELECT P.id, PP.id AS packId, P.buyPrice, P.prodName_fa, P.type, P.prodID, P.prodWeight, P.url, P.prodStatus, P.prodUnite, P.stock AS productStock, PP.stock AS packStock, PP.status, PP.price, PP.base_price, PP.label, PP.count, PC.category 
                FROM products P
                INNER JOIN products_location PL ON PL.product_id = P.id INNER JOIN product_pack PP ON PL.pack_id = PP.id INNER JOIN product_category PC ON P.id = PC.product_id 
                WHERE PL.stock > 0 AND PL.pack_stock > 0 AND PP.status = 1 AND P.prodStatus = 1 AND PL.pack_id = $key AND P.stock > 0 AND PP.stock > 0 "
            );
            if(count($productInfo) !== 0){
                $productInfo = $productInfo[0];
                $productObject = new stdClass();
                $productObject->productId = $productInfo->id;
                $productObject->productPackId = $productInfo->packId;
                $productObject->productName = $productInfo->prodName_fa;
                $productObject->prodID = $productInfo->prodID;
                $productObject->categoryId = $productInfo->category;
                $productObject->productPrice = $productInfo->price;
                $productObject->productUrl = $productInfo->url;
                $productObject->productBasePrice = $productInfo->base_price;
                $productObject->productCount = $value->count;
                $productObject->productUnitCount = $productInfo->count;
                $productObject->productUnitName = $productInfo->prodUnite;
                $productObject->productLabel = $productInfo->label;
                $productObject->productWeight = $productInfo->prodWeight;
                $productObject->productBuyPrice = $productInfo->buyPrice;
                $productObject->productStock = $productInfo->productStock;
                $productObject->packStock = $productInfo->packStock;
                $productObject->type = $productInfo->type;
                //####### TO BE ADDED ########
                //$productObject->type = 'product';
                array_push($cartProducts, $productObject);
                if($productInfo->prodWeight !== NULL){
                    $totalWeight += ($productInfo->prodWeight) + ($value->count);
                }
                //####### TO BE ADDED ########
                
                if($productInfo->buyPrice !== NULL){
                    $totalBuyPrice += ($value->count * ($productInfo->buyPrice * $productInfo->count));
                }
		/*
                if($productInfo->type === 'bundle'){
                    $productObject->type = 'bundle';
                }
                */
            }
        }
        $allDiscounts = DiscountCalculator::totalDiscount($cartProducts, $user, $provinceId, $cityId);

        $allDiscountIds = $allDiscounts->discountIds;

        $orderPrice = $allDiscounts->orderPrice;
        $shippingPrice = $allDiscounts->shippingPrice;
        $orderDiscountedPrice = $allDiscounts->orderDiscountedPrice;
        $shippingDiscountedPrice = $allDiscounts->shippingDiscountedPrice;

        $deliveryTemporaryInformation = DB::select(
            "SELECT * 
            FROM delivery_service_temporary_information 
            WHERE user_id = $user->id AND $time <= expiration_date 
            ORDER BY expiration_date DESC
            LIMIT 1"
        );
        if(count($deliveryTemporaryInformation) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'selected delivery information not found', 'umessage' => 'سرویس حمل و نقل انتخابی یافت نشد'));
            exit();
        }
        $deliveryTemporaryInformation = $deliveryTemporaryInformation[0];
        foreach($codes as $code){
            $codeValicationResult = DiscountCalculator::validateGiftCode($cartProducts, $user, $provinceId, $code);
            if($codeValicationResult === NULL){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'gift code is not valid', 'umessage' => 'برخی از کدهای وارد شده صحیح نمیباشند'));
                exit();
            }else{
                array_push($allDiscountIds, $codeValicationResult->discountId);
                if($codeValicationResult->discountType === 'order'){
                    if($codeValicationResult->discountPrice !== NULL){
                        if($orderDiscountedPrice - $codeValicationResult->discountPrice < 0){
                            $orderDiscountedPrice = 0;
                        }else{
                            $orderDiscountedPrice -= $codeValicationResult->discountPrice;
                        }
                    }else if($codeValicationResult->discountPercent !== NULL){
                        $p = ($codeValicationResult->discountPercent / 100) * $orderPrice;
                        if($codeValicationResult->discountMaxPrice !== NULL){
                            if($p > $codeValicationResult->discountMaxPrice){
                                $p = $codeValicationResult->discountMaxPrice;
                            }
                        }
                        if($orderDiscountedPrice - $p < 0){
                            $orderDiscountedPrice = 0;
                        }else{
                            $orderDiscountedPrice -= $p;
                        }
                    }
                }else if($codeValicationResult->discountType === 'shipping'){
                    if($codeValicationResult->discountPrice !== NULL){
                        if($shippingDiscountedPrice - $codeValicationResult->discountPrice < 0){
                            $shippingDiscountedPrice = 0;
                        }else{
                            $shippingDiscountedPrice -= $codeValicationResult->discountPrice;
                        }
                    }else if($codeValicationResult->discountPercent !== NULL){
                        $p = ($codeValicationResult->discountPercent / 100) * $shippingPrice;
                        if($codeValicationResult->discountMaxPrice !== NULL){
                            if($p > $codeValicationResult->discountMaxPrice){
                                $p = $codeValicationResult->discountMaxPrice;
                            }
                        }
                        if($shippingDiscountedPrice - $p < 0){
                            $shippingDiscountedPrice = 0;
                        }else{
                            $shippingDiscountedPrice -= $p;
                        }
                    }
                }
            }
        }
        
        $userLat = $user->lat;
        $userLng = $user->lng;
        if($userLat == null){
            $userLat = 'NULL';
        }
        if($userLng == null){
            $userLng = 'NULL';  
        }
        
        /***| insert order information into 'order_info' table |***/
        $lat = ' NULL ';
        $lng = ' NULL ';
        if($user->lat != null){
            $lat = $user->lat;
        }
        if($user->lng != null){
            $lng = $user->lng;
        }

        DB::insert(
            "INSERT INTO order_info
            (fname, lname, 
                city_id, postal_code, 
                address, mobile, 
                phone, area_number, 
                description, 
                post_time, tmp, weight, main_post_price, vat_tax,
                bdok, map, send_type, api_id,  work_time_id, 
                lat, lng) 
            VALUES ('$user->fname', '$user->lname', 
                '$cityId', '$user->postalCode', 
                '$userAddress', '$user->mobile', 
                '$user->telephone', 0, 
                '', 
                $deliveryTemporaryInformation->work_time, 0,  $totalWeight, 0, 0, 'cancel', '', '', 0, 
                $deliveryTemporaryInformation->work_time_id, $lat, $lng )"
        );
        
        $infoId = DB::select("SELECT id FROM order_info WHERE mobile = $user->mobile ORDER BY id DESC LIMIT 1");
        if(count($infoId) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not get latest info id', 'umessage' => 'خطا در گرفتن اطلاعات سفارش ذخیره شده'));
            exit();
        }
        $infoId = $infoId[0]->id;
        $shippingDiscount = $shippingPrice - $shippingDiscountedPrice;
        $orderDiscount = $orderPrice - $orderDiscountedPrice;
        $freePost = 0;
        $usedStockUser = 0;
        if($useUserStock === 1 && $user->user_stock > 0){
            $userStock = $user->user_stock;
            $totalDiscountedPrice = $orderDiscountedPrice + $shippingDiscountedPrice;
            if($userStock !== 0 && $userStock > $totalDiscountedPrice){
                $usedStockUser = $totalDiscountedPrice;
            }else{
                $usedStockUser = $userStock;
            }
        }
        if($shippingDiscountedPrice <= 0){
            $freePost = 1;
        }
        $orderInsertionResult = DB::insert(
            "INSERT INTO orders
                (userID, user_id, 
                info_id, stat, 
                date, totalFee, 
                deliverMode, payMethod, 
                deliverAddress, itemsDetails, 
                postReferenceID, used_stock_user, 
                old_price, orderReferenceID, 
                total_items, shipping_cost, 
                shipping_price_off, off, 
                buy_price, description, 
                sended_sms_time, free_post, 
                carton_id, checking, 
                bank, parent_order_id) 
            VALUES ('$user->username', $user->id, 
                $infoId, 6, 
                $time, '', 
                $deliveryTemporaryInformation->service_id, 3, 
                '', '', 
                '', $usedStockUser, 
                0, '', 
                $orderPrice, $shippingPrice, 
                $shippingDiscount, $orderDiscount, 
                $totalBuyPrice, '', 
                0, $freePost, 
                0, 0, 
                'pasargad', 0)"
        );
        if(!$orderInsertionResult){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not add order', 'umessage' => 'خطا در ذخیره سفارش'));
            exit();
        }
        $orderId = DB::select( 
            "SELECT id 
            FROM orders
            WHERE user_id = $user->id
            ORDER BY id DESC
            LIMIT 1"
        );
        if(count($orderId) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not get order id of the latest order', 'umessage' => 'خطا در دریافت شماره سفارش'));
            exit();
        }
        $orderId = $orderId[0]->id;
        $orderReferenceId = jdate('ynd', $time, '', '', 'en') . $orderId;
        $orderReferenceIdUpdateResult = DB::update(
            "UPDATE orders 
            SET orderReferenceID = $orderReferenceId 
            WHERE id = $orderId "
        );
        if(!$orderReferenceIdUpdateResult){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not update the orderReferenceId', 'umessage' => 'خطا در بروزرسانی کد مرجع'));
            exit();
        }
        foreach($allDiscounts->cart as $cp){
             /***| INSERT ORDER ITEMS INFORMATION |***/
            $off = $cp->productPrice - $cp->discountedPrice;
            $taxPrice = round($cp->productPrice / 109 * 100 * 6 / 100);
            $dutyPrice = round($cp->productPrice / 109 * 100 * 3 / 100);
            $sellPrice = $cp->productPrice - ($taxPrice + $dutyPrice);
            DB::insert(
                "INSERT INTO order_items
                    (product_id, count, 
                    pack_id, pack_count, pack_name, 
                    price, off, 
                    buy_price, off2, 
                    order_id, bundle_id, sell_price, 
                    tax_price, duty_price) 
                VALUES 
                    ($cp->productId, $cp->productCount,
                    $cp->productPackId, $cp->productUnitCount, '$cp->productLabel', 
                    $cp->productPrice, $off, 
                    ($cp->productBuyPrice * $cp->productUnitCount), 0, 
                    $orderId, 0, $sellPrice, 
                    $taxPrice, $dutyPrice) "
            );
            if($cp->type == 'bundle'){
                $subProducts = DB::select(
                    "SELECT P.id AS productId, PP.id AS packId, (BI.count * BI.pack_count) AS `count`, PP.label  
                    FROM bundle_items BI 
                    INNER JOIN product_pack PP ON BI.product_pack_id = PP.id 
                    INNER JOIN products P ON P.id = PP.product_id 
                    WHERE bundle_id = $cp->productId "
                );
                if(count($subProducts) != 0){
                    $bundleCount = count($subProducts);
                    $bundleBuyPrice = $cp->productBuyPrice * $cp->productUnitCount;
                    $eachPrice = $cp->productPrice / $bundleCount;
                    $eachBuyPrice = $bundleBuyPrice / $bundleCount;
                    $tp = round($eachPrice / 109 * 100 * 6 / 100);
                    $dp = round($eachBuyPrice / 109 * 100 * 3 / 100);
                    $sp = $cp->productPrice - ($tp + $dp);
                    
                    foreach($subProducts as $subProduct){
                        DB::insert(
                            "INSERT INTO order_items
                                (product_id, count, 
                                pack_id, pack_count, pack_name, 
                                price, off, 
                                buy_price, off2, 
                                order_id, bundle_id, sell_price, 
                                tax_price, duty_price) 
                            VALUES 
                                ($subProduct->productId, $cp->productCount,
                                $subProduct->packId, $subProduct->count, '$subProduct->label', 
                                $eachPrice, 0, 
                                $eachBuyPrice, 0, 
                                $orderId, $cp->productId, $eachPrice, 
                                $tp, $dp) "
                        );
                    }
                }
            }

            if($cp->productPrice !== $cp->discountedPrice){
                /***| INSERT DISCOUNT INFORMATION OF THE PRODUCTS WHICH THEIR PRICE REDUCED BECAUSE OF DISCOUNTS |***/
                DB::insert(
                    "INSERT INTO orders_discount (
                        product_id, real_price, 
                        off_price, pack_name, 
                        order_id, date
                    ) VALUES (
                        $cp->productId, $cp->productPrice, 
                        ($cp->productPrice - $cp->discountedPrice), $cp->productPackId, 
                        $orderId, $time
                    )"
                );
            }
        }
        

        /***| INSERT LOG OF ORDER WHICH IS NOT PAID YET |***/
        DB::insert(
            "INSERT INTO user_order_status (
                user_id, order_id, status
            ) VALUES (
                $user->id, $orderId, 6
            )"
        );


        /***| INSERT DISCOUNTS LOGS OF THIS ORDER |***/
        foreach($allDiscountIds as $discountId){
            DB::insert(
                "INSERT INTO discount_logs (
                    order_id, user_id, discount_id
                ) VALUES (
                    $orderId, $userId, $discountId
                )"
            );
        }
        
        if(($orderDiscountedPrice + $shippingDiscountedPrice) - $usedStockUser == 0){
            $orderItems = DB::select(
                "SELECT * FROM order_items WHERE order_id = $orderId"
            );
            foreach($orderItems as $orderItem){
                if($orderItem->bundle_id == 0){
                    $this->manipulateProductAndLog(
                        $orderItem->product_id, 
                        $user->username, 
                        $userId, 
                        (-1 * $orderItem->count * $orderItem->pack_count), 
                        $orderId, 
                        5, 
                        'کاهش موجودی - ثبت سفارش - پرداخت از کیف پول',
                        0,
                        "NULL",
                        "NULL",
                        (-1 * $orderItem->count * $orderItem->pack_count)
                    );
                    $this->manipulatePackAndLog(
                        $orderItem->pack_id,
                        $userId,
                        $orderItem->count,
                        $orderItem->pack_count,
                        2,
                        'کاهش موجودی به دلیل ثبت سفارش مشتری', 
                        $orderId, 
                        "NULL"
                    );
                    $this->manipulateProductLocationAndLog(
                        $orderItem->product_id, 
                        $orderItem->pack_id, 
                        $orderItem->count, 
                        $orderItem->pack_count, 
                        $userId, 
                        1, 
                        NULL, 
                        5 
                    );
                }
            }

            $this->updateOrderStatus($orderId, 1);

            $this->updateUserStockAndLog(
                $user->username, 
                $userId, 
                $user->username, 
                $orderId, 
                'پرداخت بانکی و کم شدن بخشی از هزینه با استفاده از موجودی',
                (-1 * $usedStockUser), 
                6
            );
            $this->updateUserOrderCountAndTotalBuy(
                $userId,
                $orderDiscountedPrice + $shippingDiscountedPrice,
                1
            );
            /***| DEACTIVATE USER CART |***/ 
            DB::update(
                "UPDATE shoppingCarts 
                SET active = 0 
                WHERE user_id = $userId "
            );

            $information = [];
            $information['paidPrice'] = ($orderPrice + $shippingPrice) - ($orderDiscount + $shippingDiscount);
            $information['buyPrice'] = $totalBuyPrice;
            $information['userPhone'] = $user->username;
            $information['userId'] = $user->ex_user_id;
            $information['products'] = [];
            $information['categories'] = [];
            foreach($orderItems as $orderItem){
                $info = DB::select(
                    "SELECT OI.count AS `count`, 
                        P.id AS productId, 
                        P.prodName_fa AS productName, 
                        OI.price AS productPrice, 
                        OI.off AS productDiscount, 
                        C.id AS categoryId,  
                        C.name AS categoryName
                    FROM order_items OI 
                        INNER JOIN products P ON OI.product_id = P.id 
                        INNER JOIN product_category PC ON P.id = PC.product_id 
                        INNER JOIN category C ON PC.category = C.id 
                    WHERE OI.product_id = $orderItem->product_id AND OI.order_id = $orderId 
                    LIMIT 1"
                );
                if(count($info) !== 0){
                    $info = $info[0];

                    $productItem = [];
                    $productItem['count'] = $info->count;
                    $productItem['productId'] = $info->productId;
                    $productItem['productName'] = $info->productName;
                    $productItem['productPrice'] = $info->productPrice;
                    $productItem['productDiscount'] = $info->productDiscount;

                    $categoryItem = [];
                    $categoryItem['categoryId'] = $info->categoryId;
                    $categoryItem['categoryName'] = $info->categoryName;

                    array_push($information['products'], $productItem);
                    array_push($information['categories'], $categoryItem);
                }
            }

	    //### SENDING CONFIRMATION MESSAGE (SMS) TO THE USER
	    $message = "با تشکر از خرید شما" . "\n" . 'سفارشتون با موفقیت ثبت شد' . "\n honari.com";
            $data = [
                'receptor' => $user->username, 
                'sender' => '10000055373520', 
                'message' => $message
            ];

            $ch = curl_init("http://api.kavenegar.com/v1/7358684B76496D5079754170615766594F534A31724130495344335152326D4F/sms/send.json");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: multipart/form-data')
            );

            curl_exec($ch);
            curl_close($ch);

            return json_encode(array('status' => 'done', 'stage' => 'done', 'message' => 'order is set', 'umessage' => 'خرید با موفقیت انجام شد', 'orderId' => $orderId, 'information' => $information));
        }else{
            $parameters = [
                'InvoiceNumber' => '' . $orderId,
                'InvoiceDate' => date('Y/m/d H:i:s'),
                'Amount' => floor((($orderDiscountedPrice + $shippingDiscountedPrice) - $usedStockUser)) * 10,
                'RedirectAddress' => 'https://honari.com/payment-result/order/pasargad',
                'Timestamp' => date('Y/m/d H:i:s'),
            ];
            $pasargad = new Pasargad();         
            $result = $pasargad->createPaymentToken($parameters);
            if($result['status'] === 'failed'){ 
                echo json_encode(array('status' => 'failed', 'source' => 'Bank Class', 'message' => $result['message'], 'umessage' => $result['umessage']));
                exit();                         
            }else if($result['status'] === 'done'){
                //'stage' => 'payment', 'message' => 'bank response was successful', 'bank' => 'pasargad', 'token' => $response->Token, 'bankPaymentLink' => 'https://pep.shaparak.ir/payment.aspx?n=' . $response->Token);
                echo json_encode(array('status' => 'done', 'stage' => 'payment', 'message' => $result['message'], 'bank' => 'pasargad', 'token' => $result['token'], 'bankPaymentLink' => $result['bankPaymentLink']));
            }
        }
    }

    public function manipulateProductAndLog($productId, $username, $userId, $changedStock, $orderId, $kind, $description, $anbarId, $factorId, $newFactorId, $changedCount){
        $time = time();
        DB::insert(
            "INSERT INTO product_stock (
                product_id, user, user_id, stock, date, order_id, kind, description, anbar_id, factor_id, new_factor_id, changed_count
            ) VALUES (
                $productId, '$username', $userId, ((SELECT stock FROM products WHERE id = $productId LIMIT 1) + $changedStock), $time, '$orderId', $kind, '$description', $anbarId, $factorId, $newFactorId, $changedCount )"
        );
        DB::update(
            "UPDATE products 
            SET stock = (stock + $changedCount) 
            WHERE id = $productId"
        );
    }

    public function manipulatePackAndLog($packId, $userId, $count, $packCount, $kind, $description, $orderId, $factorId){
        $time = time();
        $changedAmount = $count * $packCount;
        $changedStock = $count;
        if($kind === 2){
            $changedAmount *= -1;
            $changedStock *= -1;
        }
        DB::insert(
            "INSERT INTO pack_stock_log (
                pack_id, user_id, stock, changed_count, kind, description, order_id, factor_id, date
            ) VALUES (
                $packId, $userId, (SELECT stock * `count` FROM product_pack WHERE id = $packId AND status = 1) + $changedAmount, $changedAmount, $kind, '$description', $orderId, $factorId, $time 
            )"
        );
        DB::update(
            "UPDATE product_pack 
            SET stock = stock + $changedStock 
            WHERE id = $packId"
        );
    }

    public function manipulateProductLocationAndLog($productId, $packId, $count, $packCount, $userId, $sourceAnbarId, $destinationAnbarId, $kind){
        
        $fromAnbarId = $sourceAnbarId !== null ? $sourceAnbarId : ' NULL ';
        $toAnbarId = $destinationAnbarId !== null ? $destinationAnbarId : ' NULL ';
        
        $time = time();
        $stock = $count * $packCount;
        DB::insert(
            "INSERT INTO products_location_log (
                product_id, pack_id, stock, pack_stock, `time`, from_anbar, to_anbar, `user_id`, kind 
            ) VALUES (
                $productId, $packId, $stock, $count, $time, $fromAnbarId, $toAnbarId, $userId, $kind
            )"
        );

        if($kind === 5){
            DB::update(
                "UPDATE products_location 
                SET stock = stock - $stock, pack_stock = pack_stock - $count 
                WHERE product_id = $productId AND pack_id = $packId "
            );
        }else if($kind === 6){
            DB::update(
                "UPDATE products_location 
                SET stock = stock + $stock, pack_stock = pack_stock + $count 
                WHERE product_id = $productId AND pack_id = $packId "
            );
        }
    }

    public function insertProductReturns($productId, $pack, $orderId, $count, $full, $desc, $username, $putUser, $putDate){
        $time = time();
        DB::insert(
            "INSERT INTO product_returns (
                product_id, pack, order_id, `count`, full, `desc`, user, date, put_user, put_date 
            ) VALUES (
                $productId, '$pack', $orderId, $count, $full, '$desc', '$username', $time, $putUser, $putDate
            )"
        );
    }

    public function insertOrdersLog($orderId, $username, $action){
        $time = time();
        DB::insert(
            "INSERT INTO orders_log (
                order_id, user, `action`, `date`
            ) VALUES (
                '$orderId', '$username', '$action', $time
            )"
        );
    }

    public function updateOrderStatus($orderId, $status){
        DB::update(
            "UPDATE orders 
            SET stat = $status 
            WHERE id = $orderId"
        );
    }

    public function updateUsersLastShoppingCartStatus($userId, $status){
        DB::update(
            "UPDATE shoppingCarts S 
            SET S.active = $status 
            WHERE Sid = (SELECT SS.id FROM shoppingCarts SS WHERE SS.user_id = $userId AND SS.active <> $status ORDER BY SS.id DESC LIMIT 1)"
        );
    }

    public function updateUserStockAndLog($username, $userId, $changer, $orderId, $desc, $changedCount, $type){
        $time = time();
        DB::insert(
            "INSERT INTO users_stock (
                stock, username, user_id, changer, order_id, `desc`, changed_count, type, date
            ) VALUES (
                ((SELECT user_stock from users WHERE id = $userId) + $changedCount), '$username', $userId, '$changer', '$orderId', '$desc', $changedCount, $type, $time
            )"
        );
        DB::update("UPDATE users SET user_stock = (user_stock + $changedCount) WHERE id = $userId");
    }

    public function updateUserOrderCountAndTotalBuy($userId, $buy, $count){
        DB::update( 
            "UPDATE users 
            SET total_buy = (total_buy + $buy), orders_count = (orders_count + $count) 
            WHERE id = $userId"
        );
    }

    // @route: /api/user-cancel-order <--> @middleware: UserAuthenticationMiddleware
    public function cancelOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'orderId' => 'required|numeric',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $userId = $request->userId;
        $orderId = $request->orderId;
        $user = DB::select("SELECT * FROM users WHERE id = $userId LIMIT 1");
        $user = $user[0];
        $order = DB::select(
            "SELECT * FROM orders WHERE id = $orderId"
        );
        if(count($order) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'order not found', 'umessage' => 'سفارش موردنظر یافت نشد'));
            exit();
        }
        $order = $order[0];
        if($order->stat === 7){
            echo json_encode(array('status' => 'done', 'message' => 'order have been canceled', 'umessage' => 'سفارش لغو شده است'));
            exit();
        }
        if($order->stat !== 1){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'order can not get cancelled', 'umessage' => 'سفارش در این مرحله نمیتواند لغو شود'));
            exit();
        }
        $orderItems = DB::select("SELECT * FROM order_items WHERE order_id = $orderId");
        foreach($orderItems as $orderItem){    
            $productDescription =  'افزایش موجودی به دلیل کنسل شدن سفارش توسط کاربر';
            if($orderItem->bundle_id == 0){
                $this->manipulateProductAndLog(
                    $orderItem->product_id, 
                    $user->username, 
                    $userId, 
                    ($orderItem->count * $orderItem->pack_count), 
                    $orderId, 
                    15, 
                    $productDescription,
                    0,
                    "NULL",
                    "NULL",
                    ($orderItem->count * $orderItem->pack_count)
                );
                $this->manipulatePackAndLog(
                    $orderItem->pack_id,
                    $userId,
                    $orderItem->count,
                    $orderItem->pack_count,
                    5,
                    'افزایش موجودی به دلیل کنسلی سفارش', 
                    $orderId, 
                    "NULL"
                );
                $this->manipulateProductLocationAndLog(
                    $orderItem->product_id, 
                    $orderItem->pack_id, 
                    $orderItem->count, 
                    $orderItem->pack_count, 
                    $userId, 
                    NULL, 
                    1, 
                    6 
                );
                $this->insertProductReturns(
                    $orderItem->product_id, 
                    '', 
                    $orderId,
                    0,
                    0,
                    'کنسل شدن سفارش توسط کاربر',
                    $user->username,
                    "NULL",
                    "NULL"
                );
            }
        }
        $this->insertOrdersLog($orderId, $user->username, 'cancel');
        $this->updateOrderStatus($orderId, 7);
        //$this->updateUsersLastShoppingCartStatus($userId, 1);
        $userPaidPrice = ($order->total_items + $order->shipping_cost) - ($order->off + $order->shipping_price_off);
        $this->updateUserStockAndLog(
            $user->username, 
            $userId, 
            $user->username, 
            $orderId, 
            'افزایش موجودی به دلیل لغو شدن سفارش', 
            $userPaidPrice, 
            7
        );
        $this->updateUserOrderCountAndTotalBuy($userId, (-1 * $userPaidPrice), -1);

        /***| REACTIVATE THE LAST SHOPPING CART IF USER HAS NOT CREATED A NEW CART YET |***/
        $lastShoppingCart = DB::select("SELECT id , active FROM shoppingCarts WHERE `user_id` = $userId ORDER BY id DESC LIMIT 1 "); 
        if(count($lastShoppingCart) !== 0){
            $lastShoppingCart = $lastShoppingCart[0];
            if($lastShoppingCart->active == 0){
                DB::update(
                    "UPDATE shoppingCarts 
                    SET active = 1 
                    WHERE id = $lastShoppingCart->id "
                );
            }
        }
        echo json_encode(array('status' => 'done', 'message' => 'order successfully canceled', 'umessage' => 'سفارش با موفقیت لغو شد'));
    }
}
