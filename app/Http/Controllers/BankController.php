<?php

namespace App\Http\Controllers;

use App\Models\Art;
use App\Models\Banner;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WalletController;
use stdClass;

class BankController extends Controller
{

    public function insertTransaction($username, $orderId, $price, $ref, $bankRef, $type, $status, $bank){
        $time = time();
        DB::insert(
            "INSERT INTO transactions ( 
                user, order_id, price, ref, bank_ref, type, date, status, bank
            ) VALUES (
                '$username', $orderId, $price, '$ref', '$bankRef', '$type', $time, $status, '$bank'
            )"
        );
    }

    public function pasargadBankPaymentResult(Request $request){
        if(!isset($request->iD) || !isset($request->iN) || !isset($request->tref)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'اطلاعات ورودی کافی نیست'));
            exit();
        }
        $userId = $request->userId;
        echo $userId;
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
        $orderId = intval($response->InvoiceNumber);
        $amount = intval($response->Amount) / 10;
        $traceNumber = $response->TraceNumber . "";
        $this->insertTransaction(
            $user->username, 
            $orderId, 
            $amount,
            $traceNumber,
            $tref,
            'order',
            0,
            'pasargad'
        );
        $orderController = new OrderController();

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
        DB::update(
            "UPDATE orders 
            SET stat = 1
            WHERE id = $orderId"
        );
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
            //$productId, $username, $userId, $changedStock, $orderId, $kind, $description, $anbarId, $factorId, $newFactorId, $changedCount
            $orderController->manipulateProductAndLog(
                $orderItem->product_id, 
                $user->username, 
                $userId, 
                (-1 * $orderItem->count * $orderItem->pack_count), 
                $orderId, 
                6, 
                $productDescription,
                0,
                "NULL",
                "NULL",
                (-1 * $orderItem->count * $orderItem->pack_count)
            );
            $orderController->manipulatePackAndLog(
                $orderItem->pack_id,
                $userId,
                $orderItem->count, $orderItem->pack_count,
                2,
                $packDescripiton,
                $orderId, 
                "NULL"
            );
        }
        $orderController->updateOrderStatus($orderId, 1);
        if($order->used_stock_user !== 0){
            $desc = "پرداخت بانکی و کم شدن بخشی از هزینه با استفاده از موجودی";
            $orderController->updateUserStockAndLog($user->username, $userId, $user->username, $orderId, $desc, (-1 * $order->used_stock_user), 6);
        }
        echo json_encode(array('status' => 'done', 'successfulPayment' => true, 'message' => 'payment was successful', 'umessage' => 'پرداخت با موفقیت انجام شده است', 'trackingCode' => $response->TraceNumber));
        exit();
    }

    public function pasargadBankChargeResult (Request $request){
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
        
        $usersTrans = DB::select(
            "SELECT * 
            FROM users_trans 
            WHERE ref = '$ref' AND user_id = $userId 
            LIMIT 1 "
        );
        if(count($usersTrans) === 0){
            echo json_encode(array('status' => 'failed', 'successfulPayment' => false, 'message' => 'could not find users request', 'umessage' => 'درخواست اولیه کاربر یافت نشد'));
            exit();
        }
        $usersTrans = $usersTrans[0];
        if($usersTrans->status === 1){
            echo json_encode(array('status' => 'done', 'successfulPayment' => true, 'message' => 'users request had been confirmed before', 'umessage' => 'درخواست کاربر در گذشته تایید شده بود'));
            exit();
        }
        $walletController = new WalletController();
        $walletController->updateWalletChargeRequestStatus(1, $tref, $usersTrans->id);
        $description = 'افزایش موجودی با شارژ حساب';
        $walletController->updateUserStockAndLog($user->username, $userId, $user->username, 0, $description, $amount, 1);
        
        echo json_encode(array('status' => 'done', 'successfulPayment' => true, 'source' => 'c', 'message' => 'users account successfully charged', 'umessage' => 'شارژ حساب کاربری با موفقیت انجام شد'));
    }
}
