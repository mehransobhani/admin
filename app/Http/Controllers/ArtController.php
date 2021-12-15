<?php

namespace App\Http\Controllers;

use App\Classes\DiscountCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class ArtController extends Controller
{

    public function artInformation(Request $request){
        if(!isset($request->url)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'art not found', 'umessage' => 'آدرس وارد شده یافت نشد'));
            exit();
        }
        $time = time();
        $artUrl = $request->url;
        $result = DB::select(
            "SELECT AP.title, AP.img AS image, AP.description, A.artName AS name, A.catID AS categoryId, AP.art_id, C.url AS categoryUrl FROM art_page AP INNER JOIN arts A ON AP.art_id = A.id
            INNER JOIN category C ON C.id = A.catID 
            WHERE AP.url_fa = '$artUrl' OR AP.url = '$artUrl' LIMIT 1"
        );
        if(count($result) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'url did not found', 'umessage' => 'صفحه‌ی موردنظر یافت نشد'));
            exit();
        }
        $result = $result[0];
        $banners = DB::select(
            "SELECT img, anchor 
            FROM banners 
            WHERE artID = $result->art_id AND (start_date = 0 OR start_date <= $time) AND (end_date = 0 OR end_date >= $time) AND isActive = 1 and isBanner = 5
            ORDER BY _order ASC "
        );
        $topSixProducts = DB::select(
            "SELECT P.id, PP.id AS packId, P.prodName_fa, P.prodID, P.url, P.prodStatus, P.prodUnite, P.stock AS productStock, PP.stock AS packStock, PP.status, PP.price, PP.base_price, PP.label, PP.count, PC.category 
            FROM product_category PC INNER JOIN products P ON P.id = PC.product_id INNER JOIN product_pack PP ON P.id = PP.product_id 
            where PC.category IN (SELECT id FROM category C WHERE C.parentId = $result->categoryId OR C.id = $result->categoryId) AND P.prodStatus = 1 AND PP.status = 1 AND P.stock > 0 AND PP.stock > 0 AND (PP.stock * PP.count <= P.stock) 
            ORDER BY P.prodDate DESC 
            LIMIT 6"
        );
        $sixNewProducts = [];
        foreach($topSixProducts as $tsp){
            $productObject = new stdClass();
            $productObject->productId = $tsp->id;
            $productObject->productPackId = $tsp->packId;
            $productObject->productName = $tsp->prodName_fa;
            $productObject->prodID = $tsp->prodID;
            $productObject->categoryId = $tsp->category;
            $productObject->productPrice = $tsp->price;
            $productObject->productUrl = $tsp->url;
            $productObject->productBasePrice = $tsp->base_price;
            $productObject->maxCount = $tsp->packStock;
            $productObject->productUnitCount = $tsp->count;
            $productObject->productUnitName = $tsp->prodUnite;
            $productObject->productLabel = $tsp->label;
            array_push($sixNewProducts, $productObject);
        }
        if(sizeof($sixNewProducts) !== 0){
            $sixNewProducts = DiscountCalculator::calculateProductsDiscount($sixNewProducts);   
        }
        $result->banners = $banners;
        $result->topSixProducts = $sixNewProducts;

        echo json_encode(array('status' => 'done', 'message' => 'results sucessfully found', 'result' => $result));
    }
}
