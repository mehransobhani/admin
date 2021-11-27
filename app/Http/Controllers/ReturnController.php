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

class ReturnController extends Controller
{
    //@route: /api/user-all-return-requests <--> @middleware: ApiAuthenticationMiddleware
    public function userAllReturnRequests(Request $request){
        if(!Auth::check()){
            echo json_encode(array('status' => 'failed', 'message' => 'user is not authenticated'));
            exit();
        }
        $user = Auth::user();
        if($user == null){
            echo json_encode(array('status' => 'failed', 'message' => 'user is not authenticated'));
            exit();
        }
        $all = DB::select("SELECT P.id AS product_id, R.item_id, R.order_id, P.prodName_fa, R.description, R.reason, R.image, R.status FROM returned R INNER JOIN order_items OI ON R.item_id = OI.id
            INNER JOIN products P ON OI.product_id = P.id WHERE R.user_id = " . $user->id . " ORDER BY R.id DESC ");

        // https://honari.com/image/returned/big/returned_202905_1632827438_70.jpg
        // 0 : waiting
        // 1 : first acceptance
        // 2 : accepted
        // 3 : ignored
        // 4 : archive
        // 5 : deficit

        if(count($all) !== 0){
            $allReturns = array();
            foreach($all as $r){
                array_push($allReturns, array('product_id' => $r->product_id, 'order_id' => $r->order_id, 'item_id' => $r->item_id,
                    'product_name' => $r->prodName_fa, 'reason' => $r->reason, 'description' => $r->description, 'image' => $r->image, 'status' => $r->status));
            }
            echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'return requests are successfully found', 'requests' => $allReturns));
        }else{
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any return request', 'requests' => '[]'));
        }
    }

    //@route: /api/user-accepted-return-requests <--> @middleware: ApiAuthenticationMiddleware
    public function userAcceptedReturnRequests(Request $request){
        if(!Auth::check()){
            echo json_encode(array('status' => 'failed', 'message' => 'user is not authenticated'));
            exit();
        }
        $user = Auth::user();
        if($user == null){
            echo json_encode(array('status' => 'failed', 'message' => 'user is not authenticated'));
            exit();
        }
        $ignored = DB::select("SELECT P.id AS product_id, R.item_id, R.order_id, P.prodName_fam R.description, R.reason, R.image mR.status FROM returned R INNER JOIN order_items OI ON R.item_id = OI.id
        INNER JOIN products P ON OI.product_id = P.id WHERE R.user_id = " . $user->id . " AND R.status = 3 ORDER BY R.id DESC ");

        if(count($ignored) != 0){
            $ignoredRequests = array();
            foreach($ignored as $r){
                array_push($ignoredRequests, array('product_id' => $r->product_id, 'order_id' => $r->order_id, 'item_id' => $r->item_id,
                    'product_name' => $r->prodName_fa, 'reason' => $r->reason, 'description' => $r->description, 'image' => $r->image, 'status' => $r->status));
            }
            echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'return requests are successfully found', 'requests' => $ignoredRequests));
        }else{
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any return request', 'requests' => '[]'));
        }
    }

    //@route: /api/user-pending-return-requests <--> @middleware: ApiAuthenticationMiddleware
    public function userPendingReturnRequests(Request $request){
        if(!Auth::check()){
            echo json_encode(array('status' => 'failed', 'message' => 'user is not authenticated'));
            exit();
        }
        $user = Auth::user();
        if($user == null){
            echo json_encode(array('status' => 'failed', 'message' => 'user is not authenticated'));
            exit();
        }
        $pending = DB::select("SELECT P.id AS product_id, R.item_id, R.order_id, P.prodName_fa, R.description, R.reason, R.image, R.status FROM returned R INNER JOIN order_items OI ON R.item_id = OI.id
            INNER JOIN products P ON OI.product_id = P.id WHERE R.user_id = " . $user->id . " AND R.status = 0 ORDER BY R.id DESC ");
        if(count($pending) != 0){
            $pendingRequests = array();
            foreach($pending as $r){
                array_push($pendingRequests, array('product_id' => $r->product_id, 'order_id' => $r->order_id, 'item_id' => $r->item_id,
                    'product_name' => $r->prodName_fa, 'reason' => $r->reason, 'description' => $r->description, 'image' => $r->image, 'status' => $r->status));
            }
            echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'return requests successfully found', 'requests' => $pendingRequests));
        }else{
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any return request', 'requests' => '[]'));
        }
    }

    //@route: /api/user-confirmed-return-requests <--> @middleware: ApiAuthenticationMiddleware
    public function userConfirmedReturnRequests(Request $request){
        if(!Auth::check()){
            echo json_encode(array('status' => 'failed', 'message' => 'user is not authenticated'));
            exit();
        }
        $user = Auth::user();
        if($user == null){
            echo json_encode(array('status' => 'failed', 'mdessage' => 'user is not authenticated'));
            exit();
        }
        $confirmed = DB::select("SELECT P.id AS product_id, R.item_id, R.order_id, P.prodName_fa, R.description, R.reason, R.image, R.status FROM returned R INNER JOIN order_items OI ON R.item_id = OI.id
        INNER JOIN products P ON OI.product_id = P.id WHERE R.user_id = " . $user->id . " AND R.status = 5 ORDER BY R.id DESC ");

        if(count($confirmed) != 0){
            $confirmedRequests = array();
            foreach($confirmed as $r){
                array_push($confirmedRequests, array('product_id' => $r->product_id, 'order_id' => $r->order_id, 'item_id' => $r->item_id,
                    'product_name' => $r->prodName_fa, 'reason' => $r->reason, 'description' => $r->description, 'image' => $r->image, 'status' => $r->status));
            }
            echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'return requests successfully found', 'requests' => $confirmedRequests));
        }else{
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any return request', 'requests' => '[]'));
        }
    }

    //@route: /api/user-considered-return-requests <--> @middleware: ApiAuthenticationMiddleware
    public function userConsideredReturnRequests(Request $request){
        if(!Auth::check()){
            echo json_encode(array('status' => 'failed', 'message' => 'user is not authenticated'));
            exit();
        }
        $user = Auth::user();
        if($user == NULL){
            echo json_encode(array('status' => 'failed', 'message' => 'user is not authenticated'));
            exit();
        }
        $considered = DB::select(
            "SELECT P.id AS product_id, R.item_id, R.order_id, P.prodName_fa, R.description, R.reason, R.image, R.status 
            FROM returned R INNER JOIN order_items OI ON R.item_id = OI.id INNER JOIN products P ON OI.product_id = P.id 
            WHERE R.user_id = " . $user->id . " AND R.status = 1 
            ORDER BY R.id DESC"
        );

        if(count($considered) != 0){
            $consideredRequests = array();
            foreach($considered as $r){
                array_push(
                    $consideredRequests, 
                    array('product_id' => $r->product_id, 
                            'order_id' => $r->order_id, 
                            'item_id' => $r->item_id,
                            'product_name' => $r->prodName_fa, 
                            'reason' => $r->reason, 
                            'description' => $r->description, 
                            'image' => $r->image, 
                            'status' => $r->status
                        )
                );
            }
            echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'return requests successfully found', 'requests' => $consideredRequests));
            exit();
        }else{
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any return request', 'requests' => '[]'));
            exit();
        }
    }
}