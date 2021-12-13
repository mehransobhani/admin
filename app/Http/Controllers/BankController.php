<?php

namespace App\Http\Controllers;

use App\Models\Art;
use App\Models\Banner;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use stdClass;

class BankController extends Controller
{
    public function pasargadBankPaymentResult(Request $request){
        if(!isset($request->iD) || !isset($request->iN) || !isset($request->tref)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'اطلاعات ورودی کافی نیست'));
            exit();
        }
        $userId = $request->userId;
        $user = DB::select("SELECT * FROM users WHERE id = $request->userId LIMIT 1");
        $user = $user[0];
        $orderId = 202159;
        $iN = $request->iN;
        $iD = $request->iD;
        $tref = $request->tref;
        $time = time();
        $data = [
            'transactionReferenceID' => $tref
        ];
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, 'https://pep.shaparak.ir/Api/v1/Payment/CheckTransactionResult');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        ));
        $response = curl_exec ($ch);
        curl_close ($ch);
        if($response === NULL){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not connect to bank', 'umessage' => 'خطا در برقراری ارتباط با بانک پاسارگاد'));
            exit();
        }
        $response = json_decode($response);
        if($response->IsSuccess === false){
            echo json_encode(array('status' => 'done', 'successfulPayment' => false, 'message' => 'payment was not successful', 'umessage' => 'پرداخت موفقیت آمیز نبود'));
            exit();
        }
        $orderId = intval($response->InvoiceNumber);
        $amount = intval($response->Amount) / 10;
        $traceNumber = $response->TraceNumber . "";
        $queryResult = DB::insert(
            "INSERT INTO transaciton (
                user, order_id, 
                price, ref, 
                bank_ref, type, 
                date, status, bank
            ) VALUES (
                '$user->username', $orderId, 
                $amount, '$traceNumber', 
                '$response->TransactionReferenceID', 'order', 
                $time, 0, 'pasargad'
            )"
        );
        if(!$queryResult){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not insert a transaction log', 'umessage' => 'خطا در ثبت اطلاعات تراکنش'));
            exit();
        }

        $order = DB::select(
            "SELECT * 
            FROM orders 
            WHERE id = $orderId LIMIT 1 "
        );
        if(count($order) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not find user order', 'umessage' => 'سفارش کاربر یافت نشد'));
            exit();
        }
        $order = $order[0];
        if($order->stat !== 6){
            echo json_encode(array('status' => 'done', 'successfulPayment' => true, 'manipulated' => false, 'message' => 'user had paid this order before', 'umessage' => 'کاربر در گذشته این پرداخت را انجام داده است'));
            exit();
        }
        $queryResult = DB::update(
            "UPDATE orders 
            SET stat = 1
            WHERE id = $orderId"
        );
        if(!$queryResult){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'an error occured while updating the order status', 'umessage' => 'خطا هنگام بروزرسانی وضعیت سفارش'));
            exit();
        }
        $orderItems = DB::select(
            "SELECT * 
            FROM order_items 
            WHERE order_id = $orderId"
        );

        if(count($orderItems) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not find users order items', 'umessage' => 'خطا در یافتن اطلاعات سفارش'));
            exit();
        }

        $productDescription = "کاهش موجودی-ثبت سفارش-پرداخت درگاه بانکی";
        $packDescripiton = "کاهش موجودی به دلیل ثبت سفارش مشتری";

        foreach($orderItems as $orderItem){
            DB::insert(
                "INSERT INTO product_stock ( 
                    product_id, user, 
                    user_id, stock, 
                    date, order_id, 
                    kind, description, anbar_id, 
                    changed_count
                )
                VALUES (
                    $orderItem->product_id, '$user->username', 
                    $user->id, ((SELECT stock FROM products WHERE id = $orderItem->product_id) - ($orderItem->count * $orderItem->pack_count)), 
                    $time, $orderId, 
                    6, '$productDescription', 0, (-1 * $orderItem->count * $orderItem->pack_count)
                )"
            );
            DB::update(
                "UPDATE products 
                SET stock = (stock - ($orderItem->count * $orderItem->pack_count)) 
                WHERE id = $orderItem->product_id"
            );
            DB::insert(
                "INSERT INTO pack_stock_log (
                    pack_id, user_id,
                    stock, changed_count, 
                    kind, description, 
                    order_id, date
                ) VALUES (
                    $orderItem->pack_id, $user->id, 
                    (SELECT stock FROM products WHERE id = $orderItem->product_id), (-1 * $orderItem->count * $orderItem->pack_count), 
                    2, '$packDescripiton',
                    $orderId, $time
                )"
            );
            DB::update(
                "UPDATE product_pack 
                SET stock = stock - $orderItem->count 
                WHERE product_id = $orderItem->product_id" 
            );
        }

        $desc = "پرداخت بانکی و کم شدن بخشی از هزینه با استفاده از موجودی";
        $resultStatus = DB::insert(
            "INSERT INTO users_stock 
                (stock, username, 
                user_id, changer, 
                order_id, `desc`, 
                changed_count, 
                type, date) 
            VALUES 
                ($user->stock - $order->used_stock_user, '$user->username', 
                $user->id, '$user->username', 
                $orderId, '$desc', 
                (-1 * $order->used_stock_user), 6, $time)"
        );
        if(!$resultStatus){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not insert a new user stock log', 'umessage' => 'خطا در ثبت درخواست استفاده از موجودی حساب کاربری'));
            exit();
        }
        $resultStatus = DB::update(
            "UPDATE users SET stock = $order->used_stock_user WHERE id = $user->id"
        );
        if(!$resultStatus){
            echo json_encode(array('status' => 'failed', 'message' => 'could not update users stock value', 'umessage' => 'خطا هنگام بروزرسانی موجودی حساب کاربری'));
            exit();
        }
        
        echo json_encode(array('status' => 'done', 'successfulPayment' => true, 'message' => 'payment was successful', 'umessage' => 'پرداخت با موفقیت انجام شده است', 'trackingCode' => $response->TraceNumber));
        exit();
    }

    public function bankChargeResult (Request $request){
        if(!isset($request->iD) || !isset($request->iN) || !isset($request->tref)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'اطلاعات ورودی کافی نیست'));
            exit();
        }
        $userId = $request->userId;
        $user = DB::select("SELECT * FROM users WHERE id = $userId LIMIT 1");
        $user = $user[0];
        $iN = $request->iN;
        $iD = $request->iD;
        $tref = $request->tref;
        $time = time();
        $data = [
            'transactionReferenceID' => $tref
        ];
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, 'https://pep.shaparak.ir/Api/v1/Payment/CheckTransactionResult');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        ));
        $response = curl_exec ($ch);
        curl_close ($ch);
        if($response === NULL){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'could not connect to bank', 'umessage' => 'خطا در برقراری ارتباط با بانک پاسارگاد'));
            exit();
        }
        $response = json_decode($response);
        if($response->IsSuccess === false){
            echo json_encode(array('status' => 'done', 'successfulPayment' => false, 'message' => 'payment was not successful', 'umessage' => 'پرداخت موفقیت آمیز نبود'));
            exit();
        }
        $ref = intval($response->InvoiceNumber);
        $amount = intval($response->Amount) / 10;
        $traceNumber = $response->TraceNumber . "";
        $result = DB::select(
            "SELECT * 
            FROM users_trans 
            WHERE ref = '$ref' AND user_id = $userId AND kind = 1 AND status = 0 
            LIMIT 1 "
        );
        if(count($result) !== 1){
            echo json_encode(array('status' => 'done', 'successfulPayment' => true, 'message' => 'payment was successful', 'umessage' => 'پرداخت موفقیت آمیز بود'));
            exit();
        }
        $transId = $result[0]->id;
        $queryResult = DB::update(
            "UPDATE users_trans 
            SET status = 1, ref_id = '$tref' 
            WHERE id = $transId"
        );
        if(!$queryResult){
            echo json_encode(array('status' => 'failed', 'successfulPayment' => false, 'message' => 'error while updating user transaction state', 'umessage' => 'خطا هنگام بروزرسانی اولیه اطلاعات پرداخت'));
            exit();
        }
        $description = 'افزایش موجودی با شارژ حساب';
        $queryResult = DB::insert(
            "INSERT INTO users_stock (
                stock, username, user_id, changer, order_id, desc, changed_count, type, date
            ) VALUES (
                ($user->user_stock + $amount), '$user->username', $userId, '$user->username', 0, '$description', $amount, 1, $time
            )"
        );
        if(!$queryResult){
            echo json_encode(array('status' => 'failed', 'successfulPayment' => false, 'c', 'source' => 'c', 'message' => 'an error occured while inserting new user stock log', 'umessage' => 'خطا هنگام ایجاد سند پرداخت کاربر'));
            exit();
        }
        $queryResult = DB::update(
            "UPDATE users 
            SET user_stock = $amount + $user->user_stock 
            WHERE id = $userId"
        );
        if(!$queryResult){
            echo json_encode(array('status' => 'failed', 'successfulPayment' => false, 'source' => 'c', 'message' => 'an error occured while updating users stock value', 'umessage' => 'خطا هنگام بروزرسانی حساب کاربر'));
            exit();
        }
        echo json_encode(array('status' => 'done', 'successfulPayment' => true, 'source' => 'c', 'message' => 'users account successfully charged'));
    }
}
