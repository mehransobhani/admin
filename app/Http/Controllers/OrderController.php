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
use stdClass;

class OrderController extends Controller
{
    /*### without api route ###*/
    public function getUserOrderProducts (Request $request){
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
                        "SELECT P.id AS product_id, P.prodName_fa, P.prodUnite, P.prodID, P.url, OI.pack_name, OI.count, OI.pack_count, OI.price, OI.off, OI.sell_price, OI.tax_price, OI.duty_price 
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
        $responseArray = [];
        foreach($orderItems as $oi){
            $tax = 0;
            $duty = 0;
            $sell = 0;
            if($oi->sell_price != null && $oi->sell_price != 0){
                $sell = $oi->sell_price;
            }else{
                $sell = (91.75 * $oi->sell_price / 100);
            }
            if($oi->tax_price != null && $oi->tax_price != 0){
                $tax = $oi->tax_price;
            }else{
                $tax = 6 * $sell / 100; 
            }
            if($oi->duty_price != null && $oi->duty_price != 0){
                $duty = $oi->duty_price;
            }else{
                $duty = 3 * $sell / 100;
            }
            array_push($responseArray, array(
                'productId' => $oi->product_id,
                'productName' => $oi->prodName_fa,
                'productUnit' => $oi->prodUnite,
                'prodID' => $oi->prodID,
                'url' => $oi->url,
                'packName' => $oi->pack_name,
                'count' => $oi->count,
                'packCount' => $oi->pack_count,
                'basePrice' => $oi->price,
                'discount' => $oi->off,
                'sellPrice' => $sell,
                'taxPrice' => $tax,
                'dutyPrice' => $duty
            ));
        }
        $paidPrice = 0;
        $transactionRecord = DB::select("SELECT * FROM transaction WHERE user = $user->username AND order_id = $orderId");
        $walletRecord = DB::select("SELECT * FROM users_stock WHERE user_id = $userId AND order_id = $orderId AND type = 6");
        if(count($transactionRecord) !== 0){
            $paidPrice += $transactionRecord[0]->price;
        }
        if(count($walletRecord) !== 0){
            $paidPrice -= $walletRecord[0]->changed_count;
        }
        echo json_encode(array('status' => 'done', 'found' => true, 'orderStatus' => $order->stat, 'paidPrice' => $paidPrice, 'orderItems' => $responseArray, 'message' => 'order items successfully found'));
    }

    // @route: /api/user-order-details <--> @middleware: UserAuthenticationMiddleware
    public function getUserOrdersHistory(Request $request){
        $userId = $request->userId;
        $user = DB::select("SELECT * FROM users WHERE id = $userId");
        $user = $user[0];
        $orders = DB::select(
            "SELECT O.id, O.date, OI.address, OI.postal_code, OI.fname, OI.lname, O.total_items, O.shipping_cost, O.stat 
            FROM orders O INNER JOIN order_info OI ON O.info_id = OI.id 
            WHERE O.user_id = $user->id 
            ORDER BY O.date DESC");
        if(count($orders) !== 0){
            $responseArray = array();
            foreach($orders as $o){
                $orderItems = DB::select("SELECT COUNT(id) FROM order_items WHERE order_id = " . $o->id . " ");
                $itemsCount = 0; 
                if(count($orderItems) != 0){
                    $itemsCount = count($orderItems);
                }
                array_push($responseArray, array('id' => $o->id, 'status' => $o->stat, 'date' => jdate('Y-m-d', $o->date),
                    'price' => ($o->total_items + $o->shipping_cost), 'postalCode' => $o->postal_code, 'firstName' => $o->fname, 'lastName' => $o->lname));
            }
            echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'successfully found previous orders', 'orders' => $responseArray));
        }else{
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any previous order', 'orders' => '[]'));
        }
    }

    // @route: /api/user-confirm-order <--> @middleware: UserAuthenticationMiddleware
    public function confirmOrder(Request $request){
        $time = time();
        if(!isset($request->giftCodes) || !isset($request->userWallet)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'اطلاعات ورودی کافی نیست'));
            exit();
        }
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
        if($user->address === '' || $user->address === NULL){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'user does not have address', 'umessage' => 'کاربر فاقد آدرس میباشد'));
            exit();
        }
        $addressPack = json_decode($user->address)->addressPack;
        if($addressPack->province == -1){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'user does not have address', 'umessage' => 'کاربر فاقد آدرس میباشد'));
            exit();
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
            return NULL;
        }
        $cityId = $cityId[0];
        $cityId = $cityId->id;
        $totalWeight = 0;
        $cart = DB::select(
            "SELECT products 
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
        $cart = json_decode($cart[0]->products);
        foreach($cart as $key => $value){
            $productInfo = DB::select(
                "SELECT P.id, PP.id AS packId, P.buyPrice, P.prodName_fa, P.prodID, P.prodWeight, P.url, P.prodStatus, P.prodUnite, P.stock AS productStock, PP.stock AS packStock, PP.status, PP.price, PP.base_price, PP.label, PP.count, PC.category 
                FROM products P
                INNER JOIN product_pack PP ON P.id = PP.product_id INNER JOIN product_category PC ON P.id = PC.product_id 
                WHERE PP.status = 1 AND P.prodStatus = 1 AND PP.id = $key AND P.stock > 0 AND PP.stock > 0 AND (P.stock >= PP.stock * PP.count)"
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
                array_push($cartProducts, $productObject);
                if($productInfo->prodWeight !== NULL){
                    $totalWeight += $productInfo->prodWeight;
                }
                if($productInfo->buyPrice !== NULL){
                    $totalBuyPrice += ($productInfo->count * $productInfo->buyPrice);
                }
            }
        }
        $allDiscounts = DiscountCalculator::totalDiscount($cartProducts, $user, $provinceId);

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
                var_dump($codeValicationResult);
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
        $totalPrice = $orderDiscountedPrice + $shippingDiscountedPrice;
        /*if($totalPrice <= 0){
            echo json_encode(array('status' => 'done', 'message' => 'order successfully inserted', 'umessage' => 'سفارش شما با موفقیت ثبت شد'));
            exit();
        }
        if($useUserStock === 1){
            $totalPrice -= $user->user_stock;
        }
        if($totalPrice <= 0){
            echo json_encode(array('status' => 'done', 'message' => 'order successfully inserted', 'umessage' => 'سفارش شما با موفیت ثبت شد'));
            exit();
        }*/
        //creating order info record
        $orderInsertionResult = DB::insert(
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
                '$addressPack->address', '$user->mobile', 
                '$user->telephone', 0, 
                '', 
                $deliveryTemporaryInformation->work_time, 0,  $totalWeight, 0, 0, 'cancel', '', '', 0, 
                $deliveryTemporaryInformation->work_time_id, $user->lat, $user->lng )"
        );
        if(!$orderInsertionResult){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not add order info', 'umessage' => 'خطا در ذخیره سازی اطلاعات سفارش'));
            exit();
        }
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
            WHERE id = $orderId"
        );
        if(!$orderReferenceIdUpdateResult){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not update the orderReferenceId', 'umessage' => 'خطا در بروزرسانی کد مرجع'));
            exit();
        }
        foreach($allDiscounts->cart as $cp){
            $off = $cp->productPrice - $cp->discountedPrice;
            $taxPrice = round($cp->productPrice / 109 * 100 * 6 / 100);
            $dutyPrice = round($cp->productPrice / 109 * 100 * 3 / 100);
            $sellPrice = $cp->productPrice - ($taxPrice + $dutyPrice);
            $orderItemsInsertionResults = DB::insert(
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
                    $cp->productBuyPrice, 0, 
                    $orderId, 0, $sellPrice, 
                    $taxPrice, $dutyPrice)"
            );
            if(!$orderItemsInsertionResults){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not enter the order item', 'umessage' => 'خطا هنگام ذخیره موارد سفارش'));
                exit();
            }
        }
        if(($orderDiscountedPrice + $shippingDiscountedPrice) - $usedStockUser > 0){

        }else if(($orderDiscountedPrice + $shippingDiscountedPrice) - $usedStockUser === 0){
            $time = time();
            $resultStatus = DB::update(
                "UPDATE orders 
                SET stat = 1 
                WHERE id = $orderId"
            );
            if(!$resultStatus){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not update order status', 'umessage' => 'خطا هنگام بروزرسانی وضعیت سفارش'));
                exit();
            }
            $stockDeficit = $user->user_stock - $usedStockUser;
            $logStock = -1 * $usedStockUser;
            $desc = "پرداخت تمام هزینه با استقاده از موجودی";
            $resultStatus = DB::insert(
                "INSERT INTO users_stock 
                    (stock, username, 
                    user_id, changer, 
                    order_id, `desc`, 
                    changed_count, 
                    type, date) 
                VALUES 
                    ($stockDeficit, '$user->username', 
                    $user->id, '$user->username', 
                    $orderId, '$desc', 
                    $logStock, 6, $time)"
            );
            if(!$resultStatus){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not create a new log for user stocks', 'umessage' => 'خطا هنگام ایجاد لاگ تغییر موجودی کاربر'));
                exit();
            }
            $resultStatus = DB::update(
                "UPDATE users 
                SET user_stock = $stockDeficit 
                WHERE id = $user->id"
            );
            if(!$resultStatus){
                echo json_encode(array('status' => 'failed', 'message' => 'could not update users wallet', 'umessage' => 'خطا در بروزرسانی کیف پول حساب کاربری'));
                exit();
            }

            /*##### insert product and pack logs #####*/
            
        }else{
            // user will get redirected to the bank
        }
        /*
        insertANewOrder();
        insertSomeOrderInfo();*/
        
    }
    
}