<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Classes\DiscountCalculator;
use stdClass;

class CommentController extends Controller
{
    public function productComments(Request $request){
        if(!isset($request->productId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $productId = $request->productId;
        $product = DB::select(
            "SELECT * FROM products WHERE id = $productId LIMIT 1"
        );
        if(count($product) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'product not found', 'umessage' => 'محصول یافت نشد'));
            exit();
        }
        $comments = DB::select(
            "SELECT C.id, C.comment, C.user_id AS senderId, U.name AS senderName, U.role, C.date 
            FROM comments C
            INNER JOIN users U ON C.user_id = U.id 
            WHERE C.visibilityStatus = 1  AND 
                C.onIDofSection = $productId AND 
                C.replyToID = 0 
            ORDER BY C.date ASC " 
        );
        if(count($comments) === 0){ 
            echo json_encode(array('status' => 'done', 'message' => 'comments not found', 'comments' => []));
            exit();
        }
        $response = [];
        foreach($comments as $comment){
            $c = new stdClass();
            $c->id = $comment->id;
            $c->comment = $comment->comment;
            if($comment->role === ''){
                $c->senderName = $comment->senderName;
            }else{
                $c->senderName = "پشتیبانی سایت";
            }
            $c->date = jdate('d F Y | H:i', $comment->date);
            $c->response = [];
            $answers = DB::select(
                "SELECT C.id, C.comment, C.user_id AS senderId, U.name AS senderName, U.role, C.date  FROM 
                comments C INNER JOIN users U ON C.user_id = U.id 
                WHERE C.replyToID = $comment->id AND C.onIDofSection = $productId AND C.visibilityStatus = 1 
                ORDER BY C.date ASC "
            );
            if(count($answers) !== 0){ 
                foreach($answers as $answer){ 
                    $a = new stdClass(); 
                    $a->id = $answer->id; 
                    $a->comment = $answer->comment; 
                    if($answer->role === ''){ 
                        $a->senderName = $answer->senderName; 
                    }else{ 
                        $a->senderName = 'پشتیبانی سایت';
                    }
                    $a->date =jdate('d F Y | H:i', $answer->date);
                    array_push($c->response, $a);
                }
            }
            array_push($response, $c);
        }
        echo json_encode(array('status' => 'done', 'message' => 'comments successfully found', 'comments' => $response));
    }

    public function replyToComment(Request $request){
        if(!isset($request->commentId) || !isset($request->reply) || !isset($request->productId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $userId = $request->userId;
        $commentId = $request->commentId;
        $reply = $request->reply;
        $productId = $request->productId;
        $time = time();
        $user = DB::select("SELECT * FROM users WHERE id = $userId LIMIT 1");
        $user = $user[0];
        $visibilityStatus = 2;
        $senderName = $user->name;
        if($user->role !== ''){
            $visibilityStatus = 1;
            $senderName = 'پشتیبانی سایت';
        }
        
        $queryResult = DB::insert(
            "INSERT INTO comments (
                comment, `date`, sender, `user_id`, receiver, replyToID, onSection, onIDofSection, repliersId, isReported, EditedAt, visibilityStatus, isNew
            ) VALUES (
                '$reply', $time, '$user->username', $userId, '', $commentId, 1, $productId, 0, 0, $time, $visibilityStatus, 1
            )"
        );
        if($queryResult){
            echo json_encode(array('status' => 'done', 'message' => 'reply successfully inserted', 'senderName' => $senderName));
            exit();
        }else{
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'an error occured while inserting a new reply in table', 'umessage' => 'خطا هنگام ذخیره پاسخ'));
            exit();
        }
    }

    public function addComment(Request $request){
        if(!isset($request->productId) || !isset($request->comment)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'messasge' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $userId = $request->userId;
        $productId = $request->productId;
        $comment = $request->comment;
        $user = DB::select("SELECT * FROM users WHERE id = $userId LIMIT 1");
        $user = $user[0];
        $time = time();
        $visibilityStatus = 2;
        $senderName = $user->name;
        if($user->role !== ''){
            $visibilityStatus = 1;
            $senderName = 'پشتیان سایت';
        }
        $product = DB::select(
            "SELECT id FROM products WHERE id = $productId LIMIT 1"
        );
        if(count($product) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'product not found', 'umessage' => 'محصول یافت نشد'));
            exit();
        }

        $queryResult = DB::insert(
            "INSERT INTO comments (
                comment, `date`, sender, `user_id`, receiver, replyToID, onSection, onIDofSection, repliersId, isReported, EditedAt, visibilityStatus, isNew
            ) VALUES (
                '$comment', $time, '$user->username', $userId, '', 0, 1, $productId, 0, 0, $time, $visibilityStatus, 1
            )"
        );
        if($queryResult){
            $cmnt = DB::select("SELECT id FROM comments WHERE `date` = $time AND user_id = $userId ORDER BY `date` DESC");
            $commentId = 0;
            if(count($cmnt) !== 0){
                $cmnt = $cmnt[0];
                $commentId = $cmnt->id;
            }
            echo json_encode(array('status' => 'done', 'message' => 'comment successfully inserted', 'senderName' => $senderName, 'commentId' => $commentId));
            exit();
        }else{
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'an error occured while inserting a new comment in table', 'umessage' => 'خطا هنگام ذخیره نظر'));
            exit();
        }
    }
}
