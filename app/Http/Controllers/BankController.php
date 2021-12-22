<?php

namespace App\Http\Controllers;

use App\Classes\payment\pasargad\Pasargad;
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
            "INSERT INTO `transaction` ( 
                user, order_id, price, ref, bank_ref, type, date, status, bank
            ) VALUES (
                '$username', $orderId, $price, '$ref', '$bankRef', '$type', $time, $status, '$bank'
            )"
        );
    }

    public function checkUserOrderPaymentIsValidatedOrNot($tref){
        $result = DB::select(
            "SELECT * FROM transaction WHERE bank_ref = '$tref' LIMIT 1"
        );
        if(count($result) !== 0){
            $result = $result[0];
            $returnObject = new stdClass();
            $returnObject->done = true;
            $returnObject->trackingCode = $result->ref;
            return $returnObject;
        }else{
            $returnObject = new stdClass();
            $returnObject->done = false;
            $returnObject->trackingCode = '';
            return $returnObject;
        }
    }

    public function checkUserChargePaymentIsValidatedOrNot($tref){
        $result = DB::select(
            "SELECT * FROM users_trans WHERE ref_id = '$tref' LIMIT 1"
        );
        if(count($result) !== 0){
            $result = $result[0];
            $returnObject = new stdClass();
            $returnObject->done = true;
            $returnObject->trackingCode = $result->ref;
            return $returnObject;
        }else{
            $returnObject = new stdClass();
            $returnObject->done = false;
            $returnObject->trackingCode = '';
            return $returnObject;
        }
    }

    public function pasargadBankPaymentResult(Request $request){
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
        $previousResult = $this->checkUserOrderPaymentIsValidatedOrNot($tref);
        if($previousResult->done){
            echo json_encode(array('status' => 'done', 'successfulPayment' => true, 'trackingCode' => $previousResult->trackingCode, 'message' => 'users payment was confirmed', 'umessage' => 'سفارش کاربر با موفقیت ثبت شده بود'));
            exit();
        }
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
        var_dump($response);
        exit();
        if($response->IsSuccess === false){
            echo json_encode(array('status' => 'done', 'successfulPayment' => false, 'message' => 'payment was not successful', 'umessage' => 'پرداخت موفقیت آمیز نبود'));
            exit();
        }
        $pasargad = new Pasargad();
        $verificationResult = $pasargad->verifyPayment($response->InvoiceNumber, $response->InvoiceDate, $response->Amount);
        if($verificationResult['status'] == 'failed'){
            echo json_encode($verificationResult);
            exit();
        }
        $orderId = intval($response->InvoiceNumber);
        $amount = intval($response->Amount) / 10;
        $traceNumber = $response->TraceNumber . "";
        $order = DB::select(
            "SELECT * FROM orders WHERE id = $orderId"
        );
        if(count($order) === 0){
            echo json_encode(array('status' => 'failed', 'successfulPayment' => true, 'source' => 'c', 'message' => 'order not found', 'umessage' => 'سفارش موردنظر یافت نشد'));
            exit();
        }
        $order = $order[0];
        if($order->stat !== 6){
            echo json_encode(array('status' => 'done', 'successfulPayment' => true, 'source' => 'c', 'message' => 'order had been confirmed', 'umessage' => 'سفارش تایید شده بود'));
            exit();
        }
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
        $orderController->updateUserOrderCountAndTotalBuy($userId,(($order->total_items + $order->shipping_cost) - ($order->off + $order->shipping_price_off)),1);
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
        $previousResult = $this->checkUserChargePaymentIsValidatedOrNot($tref);
        if($previousResult->done){
            echo json_encode(array('status' => 'done', 'successfulPayment' => true, 'message' => 'users payment was confirmed', 'umessage' => 'سفارش کاربر با موفقیت ثبت شده بود'));
            exit();
        }
        /*$currentTref = DB::select(
            "SELECT * FROM users_trans WHERE ref_id = '$tref' AND status = 1 LIMIT 1"
        );
        if(count($currentTref) !== 0){
            $currentTref = $currentTref[0];
            echo json_encode(array('status' => 'done', 'successfulPayment' => false, 'tractionCode' => $currentTref->ref, 'message' => 'payment was successful', 'umessage' => 'پرداخت با موفقیت انجام شد'));
            exit();
        }*/
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
        $pasargad = new Pasargad();
        $verificationResult = $pasargad->verifyPayment($response->InvoiceNumber, $response->InvoiceDate, $response->Amount);
        if($verificationResult['status'] == 'failed'){
            echo json_encode($verificationResult);
            exit();
        }
        $ref = intval($response->InvoiceNumber);
        $amount = intval($response->Amount) / 10;
        
        $usersTrans = DB::select(
            "SELECT * 
            FROM users_trans 
            WHERE ref = '$ref' 
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
