<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductFeature;
use App\Models\ProductMeta;
use App\Models\ProductPack;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Classes\DiscountCalculator;
use Illuminate\Support\Facades\Validator;
use stdClass;

class ProductController extends Controller
{
    

    public function productBasicInformation(Request $request){
        $validator = Validator::make($request->all(), [
            'productId' => 'required|numeric',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $productId = $request->productId;
        $product = DB::select(
            "SELECT P.id, PP.id AS packId, P.prodName_fa, P.prodOimages, P.prodID, P.url, P.prodStatus, P.prodUnite, P.stock AS productStock, PP.stock AS packStock, PP.status, PP.price, PP.base_price, PP.label, PP.count, P.aparat, PC.category 
            FROM products P
            INNER JOIN product_pack PP ON P.id = PP.product_id INNER JOIN product_category PC ON P.id = PC.product_id 
            WHERE P.id = $productId AND PP.status = 1");
        if(count($product) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'product not found', 'umessage' => 'محصول یافت نشد'));
            exit();
        }
        $product = $product[0];
        $productStatus = -1;
        if($product->prodStatus == 1 && $product->status == 1 && $product->packStock >0 && $product->productStock >0 && ($product->count * $product->packStock <= $product->productStock) ){
            $productStatus = 1;
        }
        $productObject = new stdClass();
        $productObject->productId = $product->id;
        $productObject->productPackId = $product->packId;
        $productObject->productName = $product->prodName_fa;
        $productObject->prodID = $product->prodID;
        $productObject->categoryId = $product->category;
        $productObject->productPrice = $product->price;
        $productObject->productUrl = $product->url;
        $productObject->productBasePrice = $product->base_price;
        $productObject->maxCount = $product->packStock; // <-- OK!
        $productObject->productUnitCount = $product->count;
        $productObject->productUnitName = $product->prodUnite;
        $productObject->productLabel = $product->label;
        $productObject->aparat = $product->aparat;
        $productObject->productOtherImages = $product->prodOimages;
        $productObject->productStatus = $productStatus;
        
        if($productStatus === -1){
            $productObject->productPrice = 0;
            $productObject->productBasePrice = 0;
        }

        $productObject = DiscountCalculator::calculateProductDiscount($productObject);
        echo json_encode(array('status' => 'done', 'message' => 'product information received', 'information' => $productObject));
        exit();
        
        /*if(isset($request->id)){
            $product = DB::select("SELECT * FROM products WHERE id = $request->id");
            if(count($product) !== 0){
                $product = $product[0];
                $productCategory = DB::select("SELECT category FROM product_category WHERE product_id = $product->id");
                if(count($productCategory) !== 0){
                    $productCategory = $productCategory[0];
                }else{
                    $productCategory = null;
                }
                $productPack = DB::select("SELECT * FROM product_pack WHERE Product_id = $request->id AND status = 1");
                if(count($productPack) !== 0){
                    $productPack = $productPack[0];
                }else{
                    $productPack = null;
                }
                $productStock = DB::select("SELECT id FROM product_stock WHERE product_id = $request->id");
                if(count($productStock) == 0){
                    $productStock = null;
                }
                if($product->stock == 0 && $productStock == null && $productCategory != null){
                    echo json_encode(array(
                        'status' => 'done', 
                        'message' => 'product is comming soon',
                        'information' => array(
                            'productId' => $product->id,
                            'categoryId' => $productCategory->category, 
                            'productName' => $product->prodName_fa, 
                            'prodID' =>  $product->prodID, 
                            'productUrl' => $product->url,
                            'productPrice' => 0, 
                            'productStock' => 0,
                            'productBasePrice' => 0,
                            'productStatus' => 0,
                            'discountedPrice' => -1,
                            'discountPercent' => -1
                        )
                    ));
                }else if($productPack != null && $productCategory != null){
                    $productStatus = -1;
                    if($product->prodStatus == 1 && $productPack->status == 1 && $product->stock > 0 && $productPack->stock > 0 && ($product->stock >= $productPack->count * $productPack->stock)){
                        $productStatus = 1;
                    }
                    if($productStatus === 1){
                        $pr = new stdClass();
                        $pr->productId = $product->id;
                        $pr->categoryId = $productCategory->category;
                        $pr->productName = $product->prodName_fa;
                        $pr->prodID = $product->prodID;
                        $pr->productPrice = $productPack->price;
                        $pr->productStock = $productPack->stock;
                        $pr->productUrl = $product->url;
                        $pr->productLabel = $productPack->label;
                        $pr->productBasePrice = $productPack->base_price;
                        $pr->productStatus = 1;
                        //$pr = $this->calculateProductDiscount($pr);
                        $pr = DiscountCalculator::calculateProductDiscount($pr);
                        echo json_encode(array(
                            'status' => 'done',
                            'message' => 'product is available',
                            'information' => $pr
                        ));
                    }else{
                        echo json_encode(array(
                            'status' => 'done', 
                            'message' => 'product is finished', 
                            'information' => array(
                                'productId' => $product->id,
                                'categoryId' => $productCategory->category,
                                'productName' => $product->prodName_fa,
                                'prodID' => $product->prodID,
                                'productUrl' => $product->url,
                                'productPrice' => -1,
                                'productStock' => 0,
                                'productBasePrice' => -1,
                                'productStatus' => -1,
                                'discountedPrice' => -1,
                                'discountPercent' => -1
                        )));
                    }
                }
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'product not found'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }*/
    }

    public function productDescription(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $product = Product::where('id', $request->id);
        if($product->count() !== 0){
        $product = $product->first();
        /*$count = strlen($product->prodDscb)/100;
        $i = 0;
        $response = [];
        echo '{"status":"done","description":[';
        while($i <= $count){
            echo '"' . substr($product->prodDscb, $i*100, 100) . '"';
            $i++;
            if($i <= $count){
                echo ',';
            }
        }
        echo ']}';*/
        echo $product->prodDscb;
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'product not found'));
        }
    }

    public function productFeatures(Request $request){
	set_time_limit(4);
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $productMetas = ProductMeta::where('product_id', $request->id);
        if($productMetas->count() !== 0){
            $productMetas = $productMetas->get();
            $features = [];
            foreach($productMetas as $meta){
                $feature = ProductFeature::where('en_name', substr($meta->key, 2));
                if($feature->count() !== 0){
                    $feature = $feature->first();
                    array_push($features, ['title' => $feature->name, 'value' => $meta->value]);
                }
            }
            echo json_encode(array('status' => 'done', 'found' => true, 'features' => $features));
        }else{
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'this product does not have any feature'));
        }
    }

    public function productBreadCrumb(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $productId = $request->id;
        $productCategory = ProductCategory::where('product_id', $productId);
        if($productCategory->count() !== 0){
            $productCategory = $productCategory->first();
            $categoryId = $productCategory->category;
            $categories = [];
            do{
                $category = Category::where('id', $categoryId)->first();
                array_push($categories, array('name' => $category->name, 'url' => $category->url));
                $categoryId = $category->parentID;

            }while($categoryId !== 0);
            echo json_encode(array('status' => 'done', 'message' => 'categories successfully found', 'categories' => array_reverse($categories)));
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'category not found'));
        }
    }

    public function similarProducts(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }
        
        $productCategory = DB::select("SELECT category FROM product_category WHERE product_id = $request->id");
        if(count($productCategory) !== 0){
            $productCategory = $productCategory[0];
            $category = DB::select("SELECT C.name FROM category C WHERE C.id = $productCategory->category LIMIT 1");
            $category = $category[0];
            $products = DB::select("SELECT P.id, PP.id AS packId, PC.category, P.prodName_fa, P.url, P.prodID, PP.price FROM product_category PC INNER JOIN products P ON PC.product_id = P.id INNER JOIN product_pack PP ON PP.product_id = P.id
                WHERE PC.category = $productCategory->category AND P.id <> $request->id AND P.prodStatus = 1 AND PP.status = 1 AND P.stock > 0 AND PP.stock > 0 AND (PP.count * PP.stock <= P.stock) ORDER BY prodDate DESC LIMIT 6");
            if(count($products) !== 0){
                $response = [];
                foreach($products as $product){
                    $object = new stdClass();
                    $object->productId = $product->id;
                    $object->productPackId = $product->packId;
                    $object->productName = $product->prodName_fa;
                    $object->categoryId = $product->category;
                    $object->categoryName = $category->name;
                    $object->productUrl = $product->url;
                    $object->prodID = $product->prodID;
                    $object->productPrice = $product->price;
                    array_push($response, $object);
                }
                //$response = $this->calculateProductsDiscount($response);
                $resoponse = DiscountCalculator::calculateProductsDiscount($response);
                echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'similar products are successfully found', 'products' => $response));
            }else{
                echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'could not find any available similar products', 'umessage' => 'محصولات مشابه این محصول یافت نشد'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'product not found', 'umessage' => 'محصول یافت نشد'));
        }
    }

    public function sixNewProducts(Request $request){
	set_time_limit(4);
        $products = DB::select("SELECT P.id, PP.id AS packId, P.prodName_fa, P.prodID, P.url, P.prodStatus, P.prodUnite, P.stock AS productStock, PP.stock AS packStock, PP.status, PP.price, PP.base_price, PP.label, PP.count, C.id AS category, C.name AS categoryName
            FROM products P INNER JOIN product_pack PP ON P.id = PP.product_id INNER JOIN product_category PC ON P.id = PC.product_id INNER JOIN category C ON PC.category = C.id 
            WHERE PP.status = 1 AND P.prodStatus = 1 AND P.stock > 0 AND  PP.stock > 0 AND PP.status = 1 AND (PP.count * PP.stock <= P.stock) 
            ORDER BY P.id DESC LIMIT 6");
        if(count($products) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'new products not found', 'umessage' => 'محصول جدیدی یافت نشد'));
            exit();
        }
        $productArray = [];
        foreach($products as $product){
            $productObject = new stdClass();
            $productObject->productId = $product->id;
            $productObject->productPackId = $product->packId;
            $productObject->productName = $product->prodName_fa;
            $productObject->prodID = $product->prodID;
            $productObject->categoryId = $product->category;
            $productObject->categoryName = $product->categoryName;
            $productObject->productPrice = $product->price;
            $productObject->productUrl = $product->url;
            $productObject->productBasePrice = $product->base_price;
            $productObject->maxCount = $product->packStock;
            $productObject->productUnitCount = $product->count;
            $productObject->productUnitName = $product->prodUnite;
            $productObject->productLabel = $product->label;
            array_push($productArray, $productObject);
        }
        $productArray = DiscountCalculator::calculateProductsDiscount($productArray);
        echo json_encode(array('status' => 'done', 'message' => 'products found successfully', 'products' => $productArray));
    }

    public function sixTopsellerProducts(){
        
    }

    public function topSixDiscountedProducts(Request $request){
        $time = time();
        $discountInformation = DB::select(
            "SELECT DDT.dependency_id , DT.id AS discountId, P.id AS productId, 
                    PP.id AS productPackId, P.prodName_fa AS productName, 
                    P.prodID, P.url AS productUrl, P.prodStatus, 
                    P.prodUnite AS productUnitName, 
                    PP.stock AS maxCount, 
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
        if(count($discountInformation) === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'there is not any timered discount available', 'umessage' => 'درحال حاضر تخفیف فعالی وجود ندارد'));
            exit();
        }
        
        $discountInformation = DiscountCalculator::topSixProductsDiscountCalculator($discountInformation);
        echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'products successfully found', 'products' => $discountInformation));
    }

    public function filterPaginatedNewProducts(Request $request) {
	set_time_limit(4);
        $validator = Validator::make($request->all(), [
            'page' => 'required|numeric',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $page = $request->page;

        $products = DB::select(
            "(  SELECT 1 AS sort, P.id AS productId, PL.pack_id AS productPackId, P.prodName_fa AS productName, P.prodID, P.url AS productUrl, P.prodUnite AS productUnitName, PL.pack_stock AS maxCount, PP.price AS productPrice, PP.base_price AS productBasePrice, PP.label AS productLabel, PP.count AS productUnitCount, P.prodDate,  C.id AS categoryId, C.name AS categoryName  
                FROM products P INNER JOIN products_location PL ON P.id = PL.product_id  INNER JOIN product_pack PP ON PL.pack_id = PP.id INNER JOIN product_category PC ON P.id = PC.product_id INNER JOIN category C ON PC.category = C.id 
                WHERE P.prodStatus = 1 AND P.stock > 0 AND PP.status = 1 AND PP.stock > 0 AND (PP.count * PL.pack_stock) <= PL.stock AND PL.stock > 0 AND PL.pack_stock > 0 
             ) UNION (
                SELECT 2 AS sort, P.id AS productId, PP.id AS productPackId, P.prodName_fa AS productName, P.prodID, P.url AS productUrl, P.prodUnite AS productUnitName, -1 AS maxCount, -1 AS productPrice, -1 AS productBasePrice, PP.label AS productLabel, PP.count AS productUnitCount, P.prodDate,  C.id AS categoryId, C.name AS categoryName  
                FROM products P INNER JOIN products_location PL ON P.id = PL.product_id INNER JOIN product_pack PP ON PL.pack_id = PP.id INNER JOIN product_category PC ON P.id = PC.product_id INNER JOIN category C ON PC.category = C.id  
                WHERE P.prodStatus = 1 AND (P.stock = 0 OR PP.status <> 1 OR PP.stock = 0 OR (PP.count * PL.pack_stock) > PL.stock OR PL.stock <= 0 OR PL.pack_stock <= 0 ) 
             )
             ORDER BY sort ASC, prodDate DESC, productId DESC "
        );

        if(count($products) === 0){
            echo json_encode(array('status' => 'failed', 'found' => false, 'count' => 0, 'source' => 'c', 'message' => 'no result found', 'products' => [], 'umessage' => 'موردی یافت نشد'));
            exit();
        }
        $selectedItem = 0;
        //$count = count($products);
	$productIds = [];
	$ps = [];
	foreach($products as $p){
		if(array_search($p->productId, $productIds) == false){
			array_push($productIds, $p->productId);
			array_push($ps, $p);	
		}
	}
	$products = $ps;
	$count = count($products);
        $selectedProducs = [];
        $firstItem = ($page - 1) * 12;
        for($i=0; $i<12 && $firstItem + $i < $count ; $i++){
            array_push($selectedProducs, $products[$firstItem + $i]);
        }
        /***| CALCULATE DISCOUNT OF THESE PRODUCTS |***/
        $selectedProducs = DiscountCalculator::calculateProductsDiscount($selectedProducs);
        echo json_encode(array('status' => 'done', 'found' => true, 'count' => $count, 'message' => 'no result found', 'products' => $selectedProducs));
    }
    public function topSixBestsellerProducts(Request $request){
        $time = time();
        $orders = DB::select(
            "SELECT * 
            FROM orders O
            INNER JOIN order_items OI ON O.id = OI.order_id 
            WHERE O.stat = 9 
            ORDER BY O.date DESC 
            LIMIT 30 "
        );
        $newQuery = DB::select(
            "SELECT OI.product_id, OI.count  
            FROM order_items OI 
            WHERE OI.order_id IN (
                SELECT O.id 
                FROM orders O 
                WHERE O.stat = 9 
                ORDER BY O.date DESC 
                LIMIT 30 
            )"
        );
        
    }

    public function setProductReminder(Request $request){
        $validator = Validator::make($request->all(), [
            'productId' => 'required|numeric',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $userId = $request->userId;
        $productId = $request->productId;
        $user = DB::select("SELECT * FROM users WHERE id = $userId LIMIT 1");
        $user = $user[0];
        $time = time();
        $product = DB::select(
            "SELECT PP.id 
            FROM products P INNER JOIN product_pack PP ON P.id = PP.product_id 
            WHERE P.id = $productId AND P.prodStatus = 1 AND PP.status = 1 AND P.stock > 0 AND PP.stock > 0 AND (PP.count * PP.stock <= P.stock ) 
            ORDER BY P.id DESC 
            LIMIT 1"
        );
        
        if(count($product) !== 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'product already is available', 'umessage' => 'محصول درحال حاضر موجود است'));
            exit(); 
        } 
        $userPreviousRequestOfThisProduct = DB::select( 
            "SELECT id 
            FROM remember_products 
            WHERE user = '$user->username' AND sended = 0 AND product_id = $productId 
            LIMIT 1 "
        );
        if(count($userPreviousRequestOfThisProduct) !== 0){
            echo json_encode(array('status' => 'done', 'message' => 'products reminder had been set', 'umessage' => 'شما درگذشته یادآور این محصول را ثبت کرده بودید'));
            exit();
        }
        DB::insert(
            "INSERT INTO remember_products (
                product_id, user, sended, date
            ) VALUES (
                $productId, '$user->username', 0, $time
            )"
        );
        echo json_encode(array('status' => 'done', 'message' => 'reminder successfully got set', 'umessage' => 'یادآور با موفقیت ایجاد شد'));
    }

    public function productGoogleTagManagerInformation(Request $request){
        set_time_limit(4);
	$validator = Validator::make($request->all(), [
            'productId' => 'required|numeric',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $productId = $request->productId;

        $information = DB::select(
            "SELECT PP.price AS productPrice, P.prodUnite AS productUnit, C.name AS productCategory 
            FROM products P 
            INNER JOIN product_pack PP ON P.id = PP.product_id 
            INNER JOIN product_category PC ON P.id = PC.product_id 
            INNER JOIN category C ON PC.category = C.id 
            WHERE P.id = $productId AND P.prodStatus = 1 AND P.stock > 0 AND PP.stock > 0 AND PP.status = 1 
            LIMIT 1 "
        );
        
        if(count($information) !== 0){
            $information = $information[0];
            echo json_encode(array('status' => 'done', 'message' => 'product information successfully found', 'productUnit' => $information->productUnit, 'productPrice' => $information->productPrice, 'productCategory' => $information->productCategory));
        }else{
            $information = DB::select(
                "SELECT 0 AS productPrice, P.prodUnite AS productUnit, C.name AS productCategory 
                FROM products P 
                INNER JOIN product_category PC ON P.id = PC.product_id 
                INNER JOIN category C ON PC.category = C.id 
                WHERE P.id = $productId
                LIMIT 1 "
            );
            if(count($information) !== 0){
                $information = $information[0];
                echo json_encode(array('status' => 'done', 'message' => 'product information successfully found', 'productUnit' => $information->productUnit, 'productPrice' => $information->productPrice, 'productCategory' => $information->productCategory));
            }else{
                echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'product not found', 'umessage' => 'محصول موردنظر یافت نشد'));
            }
        }
    }
}
