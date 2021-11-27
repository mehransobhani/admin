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
use DateTime;
use stdClass;

class WalletController extends Controller
{
    //@route: /api/user-add-withdrawal-request <--> @middleware: ApiAuthenticationMiddleware
    public function addUserWithdrawalRequest(Request $request){
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
}