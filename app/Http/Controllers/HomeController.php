<?php

namespace App\Http\Controllers;

use App\Classes\DiscountCalculator;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use stdClass;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    public function routeInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'route' => 'required|string',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $route = substr($request->route, 22); 
        $r = $request->route;

        $product = DB::select(
            "SELECT * FROM products WHERE url = '$r' LIMIT 1"
        );
        if(count($product) !== 0){
            $product = $product[0];

            /*** PRODUCT INFORMATION ***/
            $productObject = new stdClass();
            $pi = DB::select(
                "SELECT P.id, PP.id AS packId, P.type, P.prodName_fa, P.prodOimages, P.prodID, P.url, P.prodStatus, P.prodUnite, PL.stock AS productStock, PL.pack_stock AS packStock, PP.status, PP.price, PP.base_price, PP.label, PP.count, P.aparat, PC.category 
                FROM products P
                INNER JOIN products_location PL ON PL.product_id = P.id INNER JOIN product_pack PP ON PL.pack_id = PP.id INNER JOIN product_category PC ON P.id = PC.product_id 
                WHERE P.id = $product->id AND PP.status = 1 ");
            if(count($pi) !== 0){
                $pi = $pi[0];
                $productStatus = -1;
                if($pi->prodStatus == 1 && $pi->status == 1 && $pi->packStock >0 && $pi->productStock >0 && ($pi->count * $pi->packStock <= $pi->productStock) ){
                    $productStatus = 1;
                }
                $productObject->productId = $product->id;
                $productObject->productPackId = $pi->packId;
                $productObject->productName = $pi->prodName_fa;
                $productObject->prodID = $pi->prodID;
                $productObject->categoryId = $pi->category;
                $productObject->productPrice = $pi->price;
                $productObject->productUrl = $pi->url;
                $productObject->productBasePrice = $pi->base_price;
                $productObject->maxCount = $pi->packStock; // <-- OK!
                $productObject->productUnitCount = $pi->count;
                $productObject->productUnitName = $pi->prodUnite;
                $productObject->productLabel = $pi->label;
                $productObject->aparat = $pi->aparat;
                $productObject->productOtherImages = $pi->prodOimages;
                $productObject->subProducts = [];
                $productObject->productStatus = $productStatus;
                
                if($productStatus === -1){
                    $productObject->productPrice = 0;
                    $productObject->productBasePrice = 0;
                }


                if($pi->type == 'bundle'){
                    $productObject->type = 'bundle';
                    $sps = DB::select(
                        "SELECT P.id, P.prodName_fa AS name, P.url 
                        FROM bundle_items BI 
                        INNER JOIN product_pack PP ON BI.product_pack_id = PP.id 
                        INNER JOIN products P ON P.id = PP.product_id 
                        WHERE BI.bundle_id = $product->id 
                        ORDER BY P.prodName_fa ASC "
                    );
                    if(count($sps) != 0){
                        $productObject->subProducts = $sps;
                    }
                }else{
                    $productObject->type = 'product';
                }

                $productObject = DiscountCalculator::calculateProductDiscount($productObject);
            }else{
                $pi = DB::select(
                    "SELECT P.id, 0 AS packId, P.prodName_fa, P.prodOimages, P.prodID, P.url, P.prodStatus, P.prodUnite, 0 AS productStock, 0 AS packStock, 0 AS `status`, 0 AS price, 0 AS base_price, '' AS label, 0 AS `count`, P.aparat, PC.category 
                    FROM products P
                    INNER JOIN product_category PC ON P.id = PC.product_id 
                    WHERE P.id = $product->id ");
                    /*  
                        $pi = DB::select(
                        "SELECT P.id, 0 AS packId, P.prodName_fa, P.prodOimages, P.prodID, P.url, P.prodStatus, P.prodUnite, 0 AS productStock, 0 AS packStock, 0 AS `status`, 0 AS price, 0 AS base_price, '' AS label, 0 AS `count`, P.aparat, PC.category 
                        FROM products P
                        INNER JOIN products_location PL ON PL.product_id = P.id INNER JOIN product_pack PP ON PL.pack_id = PP.id INNER JOIN product_category PC ON P.id = PC.product_id 
                        WHERE P.id = $product->id AND PP.status = 1 ");
                    */
                if(count($pi) !== 0){
                    $pi = $pi[0];
                    $productStatus = -1;
                    if($pi->prodStatus == 1 && $pi->status == 1 && $pi->packStock >0 && $pi->productStock >0 && ($pi->count * $pi->packStock <= $pi->productStock) ){
                        $productStatus = 1;
                    }
                    $productObject->productId = $product->id;
                    $productObject->productPackId = $pi->packId;
                    $productObject->productName = $pi->prodName_fa;
                    $productObject->prodID = $pi->prodID;
                    $productObject->categoryId = $pi->category;
                    $productObject->productPrice = $pi->price;
                    $productObject->productUrl = $pi->url;
                    $productObject->productBasePrice = $pi->base_price;
                    $productObject->maxCount = $pi->packStock; // <-- OK!
                    $productObject->productUnitCount = $pi->count;
                    $productObject->productUnitName = $pi->prodUnite;
                    $productObject->productLabel = $pi->label;
                    $productObject->aparat = $pi->aparat;
                    $productObject->productOtherImages = $pi->prodOimages;
                    $productObject->productStatus = $productStatus;
                    
                    if($productStatus === -1){
                        $productObject->productPrice = 0;
                        $productObject->productBasePrice = 0;
                    }

                    $productObject = DiscountCalculator::calculateProductDiscount($productObject);
                }
            }

            /*** FINDING PRODUCT INFORMATION ***/
            $features = [];
            $productMetas = DB::select("SELECT * FROM products_meta WHERE product_id = $product->id ");
            if(count($productMetas) !== 0){
                foreach($productMetas as $productMeta){
                    $enName = substr($productMeta->key, 2);
                    $feature = DB::select("SELECT `name` FROM product_features WHERE en_name = '$enName' ");
                    if(count($feature) !== 0){
                        $feature = $feature[0];
                        array_push($features, ['title' => $feature->name, 'value' => $productMeta->value]);
                    }
                }
            }

            /*** FINDING SIMILAR PRODUCTS AND BREADCRUMB ***/
            $similarProducts= [];
            $breadcrumb = [];
            $productCategory = DB::select("SELECT category FROM product_category WHERE product_id = $product->id");
            if(count($productCategory) !== 0){
                $productCategory = $productCategory[0];
                $category = DB::select("SELECT C.name FROM category C WHERE C.id = $productCategory->category LIMIT 1");
                $category = $category[0];
                //$productCategories = ProductCategory::where('category', $productCategory->category)->where('product_id', '<>', $request->id)->orderBy('id', 'DESC');
                $sproducts = DB::select(
                    "SELECT P.id, PP.id AS packId, PC.category, C.name AS `name`, P.prodName_fa, P.url, P.prodID, PP.price 
                    FROM product_category PC 
                        INNER JOIN products P ON PC.product_id = P.id 
                        INNER JOIN products_location PL ON PL.product_id = P.id 
                        INNER JOIN product_pack PP ON PP.id = PL.pack_id 
                        INNER JOIN category C ON C.id = PC.category 
                    WHERE PC.category = $productCategory->category AND 
                        P.id <> $product->id AND 
                        P.prodStatus = 1 AND 
                        PP.status = 1 AND 
                        PL.stock > 0 AND 
                        PL.pack_stock > 0 AND 
                        (PP.count * PL.pack_stock <= PL.stock) 
                    ORDER BY prodDate DESC 
                    LIMIT 6");
                if(count($sproducts) !== 0){
                    foreach($sproducts as $sp){
                        $object = new stdClass();
                        $object->productId = $sp->id;
                        $object->productPackId = $sp->packId;
                        $object->productName = $sp->prodName_fa;
                        $object->categoryId = $sp->category;
                        $object->categoryName = $sp->name;
                        $object->productUrl = $sp->url;
                        $object->prodID = $sp->prodID;
                        $object->productPrice = $sp->price;
                        array_push($similarProducts, $object);
                    }
                    $similarProducts = DiscountCalculator::calculateProductsDiscount($similarProducts);
                }

                /*** FINDING PRODUCT BREADCRUMB ***/
                $allowContinue = true;
                $categoryId = $productCategory->category;
                do{
                    $category = DB::select("SELECT `name`, `url`, parentID FROM category WHERE id = $categoryId LIMIT 1");
                    if(count($category) != 0){
                        $category = $category[0];
                        array_push($breadcrumb, array('name' => $category->name, 'url' => $category->url));
                        $categoryId = $category->parentID;
                    }else{
                        $allowContinue = false;
                    }
                }while($categoryId !== 0 && $allowContinue);
            }

            echo json_encode(array('status' => 'done', 'found' => true, 'type' => 'product', 'url' => $r, 'id' => $product->id, 'prodID' => $product->prodID, 'name' => $product->prodName_fa, 'information' => $productObject, 'breadcrumb' => array_reverse($breadcrumb), 'similarProducts' => $similarProducts, 'description' => $product->prodDscb, 'features' => $features));
        }else{
            $found = false;
            $category = DB::select(
                "SELECT * FROM category WHERE url = '$route' LIMIT 1"
            );
            if(count($category) !== 0){
                $category = $category[0];
                $found = true;
                //echo json_encode(array('status' => 'done', 'found' => true, 'type' => 'category', 'id' => $category->id, 'name' => $category->name, 'level' => $category->level, 'featureGroupId', $category->feature_group_id));
                //exit();
            }else{
                $urlKey = '';
                for($i=strlen($route) - 1 ; $i >= 0; $i--){
                    if($route[$i] === '/'){
                        break;
                    }
                }
                $routeArray = str_split($route);
                for($i=strlen($route) - 1; $i>=0 ; $i--){
                    if($routeArray[$i] === '/'){
                        break;
                    }
                }
                $urlKey = substr($route, -1 * (strlen($route) - $i - 1));
                $category = DB::select(
                    "SELECT * FROM category WHERE urlKey = '$urlKey' LIMIT 1"
                );
                if(count($category) !== 0){
                    $category = $category[0];
                    $found = true;
                }
                //if($found){
                    
                //}else{
                //    echo json_encode(array('status' => 'failed', 'found' => false, 'url' => $r, 'source' => 'c', 'message' => 'url not found', 'umessage' => 'آدرس اشتباه است'));
                //    exit();
                //}
            }
            if($found){

                /*** FIND CATEGORY BREADCRUMB ***/
                $allowContinue = true;
                $breadcrumb = [];

                $ci = $category->id;
                do{
                    $c = DB::select("SELECT id, `name`, `url`, parentID FROM category WHERE id = $ci LIMIT 1 ");
                    if(count($c) !== 0){
                        $c = $c[0];
                        $ci = $c->parentID;
                        array_push($breadcrumb, array('name' => $c->name, 'url' => $c->url));
                    }else{
                        $allowContinue = false;
                    }
                }while($ci !== 0 && $allowContinue);
                $breadcrumb = array_reverse($breadcrumb);

                /*** LATEST CATEGORY PRODUCTS ***/
                
                $count = 0;
                $products = [];

                $having = " AND P.prodStatus = 1 AND PL.stock > 0 AND  PL.pack_stock > 0 AND PP.status = 1 AND (PP.count * PL.pack_stock <= PL.stock) AND PPC.category = $category->id ";
                $finished = " AND P.prodStatus = 1 AND ((P.id NOT IN (SELECT DISTINCT PLL.product_id FROM products_location PLL )) OR PL.stock = 0 OR PL.pack_stock = 0 ) AND PPC.category = $category->id "; //"AND (P.stock = 0 OR PP.stock = 0 OR  PP.status = 0 OR (PP.count * PP.stock > P.stock))";

                $queryHaving = "SELECT P.id, PP.id AS packId, P.prodName_fa, P.prodID, P.url, P.prodStatus, P.prodUnite, P.stock AS productStock, PP.stock AS packStock, PP.status, PP.price, PP.base_price, PP.label, PP.count, PPC.category FROM products P INNER JOIN products_location PL ON P.id = PL.product_id INNER JOIN product_pack PP ON PL.pack_id = PP.id INNER JOIN product_category PPC ON PPC.product_id = P.id WHERE P.id IN (SELECT DISTINCT PC.product_id FROM product_category PC INNER JOIN category C ON PC.category = C.id
                    WHERE C.id = $category->id OR C.parentID = $category->id) AND PP.status = 1 " . $having . " ORDER BY P.id DESC " ;
                
                $queryFinished = "SELECT P.id, 0 AS packId, P.prodName_fa, P.prodID, P.url, P.prodStatus, P.prodUnite, 0 AS productStock, 0 AS packStock, 0 AS `status`, -1 AS price, 0 AS base_price, '' AS label, 0 AS `count`, PPC.category FROM products P INNER JOIN product_category PPC ON PPC.product_id = P.id INNER JOIN products_location PL ON P.id = PL.product_id WHERE P.id IN (SELECT DISTINCT PC.product_id FROM product_category PC INNER JOIN category C ON PC.category = C.id
                    WHERE C.id = $category->id OR C.parentID = $category->id ) " . $finished . " ORDER BY P.id DESC ";

                $havingProducts = DB::select($queryHaving);
                $finishedProducts = DB::select($queryFinished);

                foreach($havingProducts as $hp){
                    array_push($products, $hp);
                }
                foreach($finishedProducts as $fp){
                    array_push($products, $fp);
                }                
                if(count($products) !== 0){
                    $allResponses = [];
                    $i = 0;
                    foreach($products as $pr){
                        $productObject = new stdClass();
                        $productObject->productId = $pr->id;
                        $productObject->productPackId = $pr->packId;
                        $productObject->productName = $pr->prodName_fa;
                        $productObject->prodID = $pr->prodID;
                        $productObject->categoryId = $pr->category;
                        $productObject->productPrice = $pr->price;
                        $productObject->productUrl = $pr->url;
                        $productObject->productBasePrice = $pr->base_price;
                        $productObject->productUnitCount = $pr->count;
                        $productObject->productUnitName = $pr->prodUnite;
                        $productObject->productLabel = $pr->label;
                        array_push($allResponses, $productObject);
                        $i++;
                        if($i == 12){
                            break;
                        }
                    }
                    $count = count($products);
                    $products = DiscountCalculator::calculateProductsDiscount($allResponses);
                    //echo json_encode(array('status' => 'done', 'found' => true, 'categoryName' => $category->name, 'count' => count($allResponses), 'products' => $response, 'message' => 'products are successfully found'));
                }

                /*** CATEGORY BANNERS ***/
                $time = time();
                $banners = DB::select(
                    "SELECT img AS image, anchor AS url, description AS title
                    FROM banners 
                    WHERE cat_id = $category->id AND (start_date = 0 OR start_date <= $time) AND (end_date = 0 OR end_date >= $time) AND isActive = 1 AND isBanner = 6 
                    ORDER BY _order ASC"
                );
                if(count($banners) == 0){
                    $banners= [];
                }
                echo json_encode(array('status' => 'done', 'found' => true, 'type' => 'category', 'id' => $category->id, 'name' => $category->name, 'level' => $category->level, 'featureGroupId', $category->feature_group_id, 'breadcrumb' => $breadcrumb, 'count' => $count, 'products' => $products, 'banners' => $banners));
            }else{
                echo json_encode((array('status' => 'failed', 'message' => 'category not found')));
            }
        }
    }

    public function homeInformation(Request $request){
        $banners = [];
        $carousel = [];
        $courses = [];
        $products = [];
        $populars = [];
        $topBanners = [];

        /***| FINDING COURSES |***/
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_URL, 'https://academy.honari.com/api/shop/new-four-courses');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //'Content-Type: application/json',
        //));
        $response = curl_exec ($ch);
        curl_close ($ch);
        if($response !== NULL){
            $response = json_decode($response);   
        }
        if($response->status === 'done' && $response->found == true){
            $courses = $response->courses;
        }

        /***| FINDING TOP SIX NEW PRODUCTS |***/
        $ps = DB::select(
            "SELECT P.id AS productId, PP.id AS productPackId, P.prodName_fa AS productName, P.prodID, P.url AS productUrl, P.prodUnite AS productUnitName, PP.stock AS maxCount, PP.price AS productPrice, PP.base_price AS productBasePrice, PP.label AS productLabel, PP.count AS productUnitCount, P.prodDate,  C.id AS categoryId, C.name AS categoryName  
            FROM products P 
            INNER JOIN products_location PL ON PL.product_id = P.id 
            INNER JOIN product_pack PP ON PL.pack_id = PP.id 
            INNER JOIN product_category PC ON P.id = PC.product_id 
            INNER JOIN category C ON PC.category = C.id 
            WHERE P.prodStatus = 1 AND PL.stock > 0 AND PP.status = 1 AND PL.pack_stock > 0 AND (PP.count * PL.pack_stock) <= PL.stock 
            ORDER BY prodDate DESC, productId DESC 
            LIMIT 6"
        );

        if(count($ps) !== 0){
            $products = DiscountCalculator::calculateProductsDiscount($ps);
        }

        $date = time();

        /***| TOP TWO BANNERS |***/
        /*
        $twoBanners = DB::select(
            "SELECT img ,anchor 
            FROM banners 
            WHERE isBanner = 9 AND isActive = 1 AND ((`start_date` = 0 AND end_date = 0) OR (`start_date` >= $date AND end_date <= $date)) 
            ORDER BY _order ASC, `date` DESC 
            LIMIT 2 "
        );
        if(count($twoBanners) !== 0){
            $banners = $twoBanners;
        }
        */

        /***| CAROUSEL BANNERS |***/
        $sliders = DB::select(
            "SELECT img ,anchor 
            FROM banners 
            WHERE isBanner = 8 AND isActive = 1 AND ((`start_date` = 0 AND end_date = 0) OR (`start_date` >= $date AND end_date <= $date)) 
            ORDER BY _order ASC, `date` DESC 
            LIMIT 6 " 
        );
        if(count($sliders) !== 0){
            $carousel = $sliders;
        }

        /***| THE OTHER BANNERS |***/
        $otherBanners = DB::select(
            " SELECT img, anchor 
            FROM banners 
            WHERE isBanner = 9 AND isActive = 1 AND ((`start_date` = 0 AND end_date = 0) OR (`start_date` >= $date AND end_date <= $date)) 
            ORDER BY _order ASC, `date` DESC 
            LIMIT 2 "
        );
        if(count($otherBanners) !== 0){
            $topBanners = $otherBanners;
        }
        
        /***| POPULAR CATEGORIES |***/
        $pcs = DB::select(
            "SELECT img ,anchor, `description` 
            FROM banners 
            WHERE isBanner = 10 AND isActive = 1 AND ((`start_date` = 0 AND end_date = 0) OR (`start_date` >= $date AND end_date <= $date)) 
            ORDER BY _order ASC, `date` DESC 
            LIMIT 6 "
        );
        if(count($pcs) !== 0){
            $populars = $pcs;
        }
        
        $discountedProducts = [];
        /***| TOP DISCOUNTED PRODUCTS |***/
        // this is going to be fantstic
        $time = time();
        $discountInformation = DB::select(
            "SELECT DDT.dependency_id , DT.id AS discountId, P.id AS productId, 
                    PP.id AS productPackId, P.prodName_fa AS productName, 
                    P.prodID, P.url AS productUrl, P.prodStatus, 
                    P.prodUnite AS productUnitName, 
                    PL.pack_stock AS maxCount, 
                    PP.price AS productPrice, PP.base_price AS productBasePrice, PP.label AS ProductLabel, 
                    PP.count AS productUnitCount 
            FROM discount_dependencies DDT INNER JOIN discounts DT ON DT.id = DDT.discount_id INNER JOIN products P ON DDT.dependency_id = P.id INNER JOIN products_location PL ON P.id = PL.product_id INNER JOIN product_pack PP ON DDT.dependency_id = PP.product_id 
            WHERE DDT.discount_id IN (
                SELECT D.id 
                FROM discounts D 
                WHERE D.type_id = 1 AND 
                    (SELECT count(DD.id) FROM discount_dependencies DD WHERE DD.discount_id = D.id AND DD.type_id IN (2,3,4)) = 0 AND 
                    (SELECT count(DD.id) FROM discount_dependencies DD WHERE DD.discount_id = D.id AND DD.type_id = 1 ) <> 0 AND 
                    D.reusable = 1 AND D.status = 1 AND D.neworder = 0 AND (D.numbers_left IS NULL OR D.numbers_left > 0) AND 
                    ((D.expiration_date IS NULL AND D.start_date IS NOT NULL AND D.start_date <= $time AND D.finish_date IS NOT NULL AND D.finish_date >= $time) OR (D.expiration_date IS NOT NULL AND $time <= D.expiration_date AND D.start_date IS NULL AND D.finish_date IS NULL)) AND 
                    D.code IS NULL 
            ) AND P.prodStatus = 1 AND PP.status = 1 AND PL.stock > 0 AND PL.pack_stock > 0 AND P.stock > 0 AND PP.stock > 0 AND (PP.count * PP.stock <= P.stock)
            ORDER BY DT.date DESC
            LIMIT 6"
        );
        if(count($discountInformation) !== 0){
            $discountedProducts = DiscountCalculator::topSixProductsDiscountCalculator($discountInformation);   
        }

        echo json_encode(array('status' => 'done', 'courses' => $courses, 'products' => $products, 'carousel' => $carousel, 'topBanners' => $topBanners, 'populars' => $populars, 'discountedProducts' => $discountedProducts));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
