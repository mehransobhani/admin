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
use stdClass;

class ProductController extends Controller
{
    

    public function productBasicInformation(Request $request){
        if(!isset($request->productId)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی ناکافی است'));
            exit();
        }
        $productId = $request->productId;
        $product = DB::select(
            "SELECT P.id, P.prodName_fa, P.prodID, P.url, P.prodStatus, P.prodUnite, P.stock AS productStock, PP.stock AS packStock, PP.status, PP.price, PP.base_price, PP.label, PP.count, PC.category 
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
        if(isset($request->id)){
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
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function productFeatures(Request $request){
        if(isset($request->id)){
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
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function productBreadCrumb(Request $request){
        if(isset($request->id)){
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
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }

    public function similarProducts(Request $request){
        if(isset($request->id)){
            //$productCategory = ProductCategory::where('product_id', $request->id);
            $productCategory = DB::select("SELECT category FROM product_category WHERE product_id = $request->id");
            if(count($productCategory) !== 0){
                $productCategory = $productCategory[0];
                $category = DB::select("SELECT C.name FROM category C WHERE C.id = $productCategory->category LIMIT 1");
                $category = $category[0];
                //$productCategories = ProductCategory::where('category', $productCategory->category)->where('product_id', '<>', $request->id)->orderBy('id', 'DESC');
                $products = DB::select("SELECT P.id, PC.category, P.prodName_fa, P.url, P.prodID, PP.price FROM product_category PC INNER JOIN products P ON PC.product_id = P.id INNER JOIN product_pack PP ON PP.product_id = P.id
                    WHERE PC.category = $productCategory->category AND P.id <> $request->id AND P.prodStatus = 1 AND PP.status = 1 AND P.stock > 0 AND PP.stock > 0 AND (PP.count * PP.stock <= P.stock) ORDER BY prodDate DESC LIMIT 6");
                if(count($products) !== 0){
                    $response = [];
                    foreach($products as $product){
                        $object = new stdClass();
                        $object->productId = $product->id;
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
                    echo json_encode(array('status' => 'done', 'found' => false, 'message' => 'could not find any available similar products'));
                }
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'product not found'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }
    }
}
