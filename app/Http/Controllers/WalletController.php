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
use App\Classes\payment\pasargad\Pasargad;
use DateTime;
use Illuminate\Support\Facades\Validator;
use stdClass;

class WalletController extends Controller
{
    //@route: /api/user-add-withdrawal-request <--> @middleware: ApiAuthenticationMiddleware
    public function addUserWithdrawalRequest(Request $request){
        $validator = Validator::make($request->all(), [
            'cardNumber' => 'required|string', 
            'cardOwner' => 'required|string', 
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }
        
        $userId = $request->userId;
        $user = DB::select("SELECT * FROM users WHERE id = $userId");
        $user = $user[0];
        if(!isset($request->cardNumber) || !isset($request->cardOwner)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'reason' => 'missingInput', 'message' => 'not enough parameter', 'umessage' => 'مقادیر ورودی کافی نیست'));
            exit();
        }
        if(strlen($request->cardNumber) !== 31){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'reason' => 'wrongInput',  'message' => 'wrong card number', 'umessage' => 'شماره شبا اشتباه است'));
            exit();
        }
        $cardNumber = $request->cardNumber;
        $cardOwner = $request->cardOwner;
        $dateTime = new DateTime();
        $timestamp = $dateTime->getTimestamp();
        $queryResult = DB::insert(
            "INSERT INTO user_balance (user_id, balance_value, card_number, status, card_owner) 
            VALUES ( $userId, $user->user_stock, '$cardNumber' , 0, '$cardOwner')");
        if($queryResult == NULL){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'reason' => 'queryError', 'message' => 'an error occured while inserting a new request in the table', 'umessage' => 'خطا هنگام ذخیره کرده اطلاعات'));
            exit();
        }else if($queryResult){
            echo json_encode(array('status' => 'done', 'message' => 'new request successfully added'));
        }
        /*if(!isset($request->likePrevious) && !isset($request->value)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'reason' => 'missingInput', 'message' => 'not enough parameter'));
            exit();
        }
        $requestedValue = $request->value;
        if($requestedValue < 100){
            echo json_encode(array('status' => 'failed', 'srouce' => 'c', 'reason' => 'inputLimit', 'message' => 'requested price can not be less than 100 tomans'));
            exit();
        }
        if($requestedValue > $user->user_stock){
            echo json_encode(array('status' => 'failed', 'srouce' => 'c', 'reason' => 'wrongInput', 'message' => 'wrong input value for requested price'));
            exit();
        }
        if($request->likePrevious == 1){
            $lastWithdrawalRequest = DB::select("SELECT * FROM user_balance WHERE user_id = $userId AND status <> -1 AND balance_value <> -1 ORDER BY id LIMIT 1");
            if(count($lastWithdrawalRequest) == 0){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'reason' => 'doesNotExist', 'message' => 'last withdrawal request does not exist'));
                exit();
            }
            $lastWithdrawalRequest = $lastWithdrawalRequest[0];
            $timestamp = date("Y-m-d h:i:sa");
            $queryResult = DB::insert(
                "INSERT INTO user_balance (user_id, balance_value, card_number, status, card_owner) 
                VALUES ( $userId, $requestedValue, '$lastWithdrawalRequest->card_number', 0, '$lastWithdrawalRequest->card_owner')");
            if($queryResult == NULL){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'reason' => 'queryError', 'message' => 'an error occured while inserting a new request in the table'));
                exit();
            }else if($queryResult){
                echo json_encode(array('status' => 'done', 'message' => 'new request successfully added'));
            }
        }else if($request->likePrevious == 0){
            if(!isset($request->cardNumber) || !isset($request->cardOwner)){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'reason' => 'missingInput', 'message' => 'not enough parameter'));
                exit();
            }
            if(strlen($request->cardNumber) !== 31){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'reason' => 'wrongInput',  'message' => 'wrong card number'));
                exit();
            }
            $cardNumber = $request->cardNumber;
            $cardOwner = $request->cardOwner;
            $dateTime = new DateTime();
            $timestamp = $dateTime->getTimestamp();
            $queryResult = DB::insert(
                "INSERT INTO user_balance (user_id, balance_value, card_number, status, card_owner) 
                VALUES ( $userId, $requestedValue, '$cardNumber' , 0, '$cardOwner')");
            if($queryResult == NULL){
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'reason' => 'queryError', 'message' => 'an error occured while inserting a new request in the table'));
                exit();
            }else if($queryResult){
                echo json_encode(array('status' => 'done', 'message' => 'new request successfully added'));
            }
        }*/
    }

    //@route: /api/user-withdrawal-history <--> @middleware: ApiAuthenticationMiddleware
    public function userWithdrawalHistory(Request $request){
        $userId = $request->userId;
        $withdrawHistory = DB::select("SELECT * FROM user_balance WHERE user_id = $userId AND status <> -1 AND balance_value <> -1 ORDER BY id DESC ");
        if(count($withdrawHistory) == 0){
            echo json_encode(array());
        }
        if(count($withdrawHistory) !== 0){
            $responseArray = array();
            foreach($withdrawHistory as $w){
                $date = '-----';
                $time = '-----';
                if($w->date !== null && $w->date != ''){
                    $date = jdate('Y/m/d', strtotime($w->date));
                    $time = jdate('H:i', strtotime($w->date));
                }
                array_push($responseArray, array('date' => $date, 'time' => $time, 'balance' => $w->balance_value, 'sheba' => $w->card_number, 'owner' => $w->card_owner, 'status' => $w->status));
            }
            echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'withdrawal history are successfully found', 'history' => $responseArray));
        }else{
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any withdrawal hisotry', 'history' => '[]'));
        }
    }

    //@route: /api/user-last-withdrawal-request <--> @middleware: ApiAuthenticationMiddleware
    public function userLastWithdrawalRequest(Request $request){
        $userId = $request->userId;
        $lastWithdrawalRequest = DB::select("SELECT * FROM user_balance WHERE user_id = $userId AND status <> -1 AND balance_value <> -1 ORDER BY id DESC LIMIT 1");
        if(count($lastWithdrawalRequest) == 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any last withdrawal request', 'information' => '[]'));
            exit();
        }
        $lastWithdrawalRequest = $lastWithdrawalRequest[0];
        $information = new stdClass();
        $information->shebaCode = $lastWithdrawalRequest->card_number;
        $information->ownerName = $lastWithdrawalRequest->card_owner;
        echo json_encode(array(
            'status' => 'done',
            'found' => true,
            'message' => 'successfully found information of last request',
            'information' => $information
        ));
    }

    //@route: /api/user-balance <--> @middleware: ApiAuthenticationMiddleware
    public function userBalance(Request $request){
        $userId = $request->userId;
        $user = DB::select("SELECT * FROM users WHERE id = $userId");
        $user = $user[0];
        echo json_encode(array('status' => 'done', 'balance' => $user->user_stock, 'message' => 'user balance successfully found'));
    }

    //@route: /api/user-charge-wallet <--> @middleware: ApiAuthenticationMiddleware
    public function chargeWallet(Request $request){
        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $price = intval($request->price);
        $userId = $request->userId;
        if($price < 100){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'wrong price input', 'umessage' => 'مقدار درخواستی اشتباه است'));
            exit();
        }
        $time = time();
        $ref = '98' . $time . rand(0, 9999);
        $queryResult = DB::insert(
            "INSERT INTO users_trans (
                user_id, price, ref, date, status, kind, bank, ref_id
            ) VALUES (
                $userId, $price, '$ref', $time, 0, 1, 'pasargad', ''
            )"
        );
        if(!$queryResult){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message'=> 'an error occured while inserting data to users_trans', 'umessage' => 'خطا در ذخیره‌سازی اولیه اطلاعات پرداخت'));
            exit();
        }
        $pasargad = new Pasargad();
        $parameters = [
            'InvoiceNumber' => $ref,
            'InvoiceDate' => date('Y/m/d H:i:s'),
            'Amount' => $price * 10,
            'RedirectAddress' => 'https://honari.com/payment-result/charge/pasargad',
            'Timestamp' => date('Y/m/d H:i:s'),
        ];
        $result = $pasargad->createPaymentToken($parameters);
        if($result['status'] === 'failed'){
            echo json_encode(array('status' => 'failed', 'source' => 'Bank Class', 'message' => $result['message'], 'umessage' => $result['umessage']));
            exit();
        }else if($result['status'] === 'done'){
            echo json_encode(array('status' => 'done', 'stage' => 'payment', 'message' => $result['message'], 'bank' => 'pasargad', 'token' => $result['token'], 'bankPaymentLink' => $result['bankPaymentLink']));
        }else{
            var_dump($result);
        }
    }

    public function updateWalletChargeRequestStatus($status, $tref, $id){
        DB::update(
            "UPDATE users_trans 
            SET status = $status, ref_id = '$tref' 
            WHERE id = $id"
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
}
