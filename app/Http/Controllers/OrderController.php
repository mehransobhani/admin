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
    
}