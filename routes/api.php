<?php

use App\Http\Controllers\ArtController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DeliveryServiceController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\ApiAuthenticationMiddleware;
use App\Http\Middleware\Cors;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPack;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
//header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization, Accept,charset,boundary,Content-Length');
//header('Access-Control-Allow-Origin: *');


Route::post('login', 'API\UserController@login');
Route::post('register', 'API\UserController@register');

Route::group(['middleware' => 'auth:api'], function(){
    Route::post('details', 'API\UserController@details');
});


/*Route::get('/get-banners', function () {
    $first = Banner::where('isActive', 1)->where('isBanner', 3)->where('_order', 1)->where(function($query){
        return $query->where(function($query){
            return $query->where('start_date', 0)->where('end_date', 0);
        })->orWhere(function($query){
            $time = time();
            return $query->where('start_date', '<=', $time)->where('end_date', '>=', $time);
        });
    })->orderBy('date', 'DESC');
    $second= Banner::where('isActive', 1)->where('isBanner', 3)->where('_order', 2)->where(function($query){
        return $query->where(function($query){
            return $query->where('start_date', 0)->where('end_date', 0);
        })->orWhere(function($query){
            $time = time();
            return $query->where('start_date', '<=', $time)->where('end_date', '>=', $time);
        });
    })->orderBy('date', 'DESC');
    $third = Banner::where('isActive', 1)->where('isBanner', 3)->where('_order', 3)->where(function($query){
        return $query->where(function($query){
            return $query->where('start_date', 0)->where('end_date', 0);
        })->orWhere(function($query){
            $time = time();
            return $query->where('start_date', '<=', $time)->where('end_date', '>=', $time);
        });
    })->orderBy('date', 'DESC');

    $firstBannerImage = null;
    $firstBannerAnchor = null;
    $secondBannerImage = null;
    $secondBannerAnchor = null;
    $thirdBannerImage = null;
    $thirdBannerAnchor = null;

    if($first->count() !== 0){
        $first = $first->first();
        $firstBannerImage = $first->img;
        $firstBannerAnchor = $first->anchor;
    }
    
    if($second->count() !== 0){
        $second = $second->first();
        $secondBannerImage = $second->img;
        $secondBannerAnchor = $second->anchor;
    }
    
    if($third->count() !== 0){
        $third = $third->first();
        $thirdBannerImage = $third->img;
        $thirdBannerAnchor = $third->anchor;
    }
    echo json_encode(array('status' => 'done', 'banners' => array('firstImage' => $firstBannerImage, 'firstAnchor' => $firstBannerAnchor, 'secondImage' => $secondBannerImage, 'secondAnchor' => $secondBannerAnchor, 'thirdImage' => $thirdBannerImage, 'thirdAnchor' => $thirdBannerAnchor)));
});*/

Route::get('/get-six-new-product', function(){
    $products = Product::where('prodStatus', 1)->orderBy('prodDate', 'DESC')->take(6);
    if($products->count() !== 0){
        $products = $products->get();
        $prods = [];
        foreach($products as $product){
            $categoryName = '';
            $pack = ProductPack::where('product_id', $product->id)->first();
            $categoryLink = ProductCategory::where('product_id', $product->id);
            if($categoryLink->count() !== 0){
                $categoryLink = $categoryLink->first();
                $category = Category::where('id', $categoryLink->category);
                if($category->count() !== 0){
                    $category = $category->first();
                    $categoryName = $category->name;
                }else{
                    $categoryName = null;
                }
            }else{
                $categoryName = null;
            }
            array_push($prods, array('id' => $product->id, 'prodID' => $product->prodID, 'name' => $product->prodName_fa, 'categoryName' => $categoryName, 'url' => $product->url, 'price' => $pack->price));
        }
        echo json_encode(array('status' => 'done', 'products' => $prods));
    }else{
        echo json_encode(array('status' => 'failed'));
    }
});

    Route::post('/route-info', [HomeController::class, 'routeInfo']);
 

    /*##### Product routes #####*/
    Route::get('six-new-products',                      [ProductController::class, 'sixNewProducts']);;
    Route::post('/product-basic-information',           [ProductController::class, 'productBasicInformation']);
    Route::post('/product-description',                 [ProductController::class, 'productDescription']);
    Route::post('/product-features',                    [ProductController::class, 'productFeatures']);
    Route::post('/product-breadcrumb',                  [ProductController::class, 'productBreadcrumb']);
    Route::post('/similar-products',                    [ProductController::class, 'similarProducts']);
    Route::post('/top-six-bestseller-similar-products', [ProductController::class, 'topSixBestsellerSimilarProducts']); // USELESS!
    Route::get('/top-six-bestseller-products',          [ProductController::class, 'topSixBestsellerProducts']); // TO BE TESTED!
    Route::get('/top-six-discounted-products',          [ProductController::class, 'topSixDiscountedProducts']); // OK!
    Route::post('/filtered-paginated-new-products',     [ProductController::class, 'filterPaginatedNewProducts']);

    /*##### Category routes #####*/
    Route::post('/subcategories',                       [CategoryController::class, 'subCategories']);
    Route::post('/root-category-six-new-products',      [CategoryController::class, 'rootCategorySixNewProducts']);
    Route::post('/filtered-paginated-category-products',[CategoryController::class, 'filterPaginatedcategoryProducts']);
    Route::post('/category-filters',                    [CategoryController::class, 'categoryFilters']);
    Route::post('/category-breadcrumb',                 [CategoryController::class, 'categoryBreadcrumb']);
    Route::get('/top-six-categories',                   [CategoryController::class, 'topSixBestSellerCategories']);

    /*##### Banner routes #####*/
    Route::post('/category-banners',                    [BannerController::class, 'categoryBanners']);
    Route::get('/top-three-home-banners',               [BannerController::class, 'topThreeHomeBanners']);

    /*##### Menu routes ###*/
    Route::get('/menu',                                 [MenuController::class, 'menuItemsInformation']); // OK!

    /*##### Art routes ###*/
    Route::post('/art-information',                     [ArtController::class,   'artInformation']); // OK!

    /*##### User routes  #####*/
    Route::middleware([ApiAuthenticationMiddleware::class])->group(function () {
        Route::post('/user-information',                [UserController::class,             'userInformation']); // OK!
        Route::post('/user-cart',                       [CartController::class,             'userCart']); // OK!
        Route::post('/user-special-cart',               [CartController::class,             'userSpecialCart']); // OK!
        Route::post('/user-increase-cart-by-one',       [CartController::class,             'increaseCartByOne']); // OK!
        Route::post('/user-decrease-cart-by-one',       [CartController::class,             'decreaseCartByOne']); // OK!
        Route::post('/user-add-to-cart',                [CartController::class,             'addToCart']); // OK!
        Route::post('/user-remove-from-cart',           [CartController::class,             'removeFromCart']); // OK!
        Route::post('/user-wipe-cart',                  [CartController::class,             'wipeCart']); // OK!
        Route::post('/user-cart-change',                [CartController::class,             'userChangeCart']);
        Route::post('/user-cart-raw',                   [CartController::class,             'userCartRaw']); // TO BE TESTED ...
        Route::post('/user-all-return-requests',        [ReturnController::class,           'userAllReturnRequests']); // TEST
        Route::post('/user-accepted-return-requests',   [ReturnController::class,           'userAcceptedReturnRequests']); // TEST
        Route::post('/user-pending-return-requests',    [ReturnController::class,           'userPendingReturnRequests']); // TEST
        Route::post('/user-considered-return-requests', [ReturnController::class,           'userConsideredReturnRequests']); // is seems that is has authentication problem
        Route::post('/user-orders-history',             [OrderController::class,            'getUserOrdersHistory']); // OK!
        Route::post('/user-order-details',              [OrderController::class,            'getOrderDetails']); // OK!
        Route::post('/user-balance',                    [WalletController::class,           'userBalance']); // OK!
        Route::post('/user-withdrawal-history',         [WalletController::class,           'userWithdrawalHistory']); // OK!
        Route::post('/user-last-withdrawal-request',    [WalletController::class,           'userLastWithdrawalRequest']); // OK!
        Route::post('/user-add-withdrawal-request',     [WalletController::class,           'addUserWithdrawalRequest']); // OK!
        Route::post('/user-check-address',              [UserController::class,             'checkUserAddress']); //OK!
        Route::post('/user-delivery-options',           [DeliveryServiceController::class,  'getAvailableDeliveryServices']); //------------
        Route::post('/user-delivery-service-work-times',[DeliveryServiceController::class,  'getDeliveryServiceWorkTimes']); // OK!
        Route::post('/user-set-delivery-service-temporary-information', [DeliveryServiceController::class, 'setDeliveryServiceTemporaryInformation']);
        Route::post('/user-check-temporary-delivery-info-existance', [DeliveryServiceController::class, 'checkTemporaryDeliveryServiceInformationExistance']);
        Route::post('/user-check-gift-code',            [DiscountController::class,         'checkGiftCode']); // OK!
        Route::post('/user-final-cart',                 [CartController::class,             'cartFinalInformation']); // OK!
        Route::post('/user-confirm-order',              [OrderController::class,            'confirmOrder']); // TO BE TESTED! *
        Route::post('/user-cancel-order',               [OrderController::class,            'cancelOrder']); // TO BE TESTED! *
        Route::post('/user-charge-wallet',              [WalletController::class,           'chargeWallet']); // OK!!
        Route::post('/user-set-product-reminder',       [ProductController::class,          'setProductReminder']); // TO BE TESTED!

        /***| BANK ROUTES |***/
       
        Route::post('/user-pasargad-charge-result',     [BankController::class,             'pasargadBankChargeResult']); // OK! 
        Route::post('/user-pasargad-payment-result',    [BankController::class,             'pasargadBankPaymentResult']); // OK!

        /***| COMMENT ROUTES |***/
        Route::post('/add-comment',                     [CommentController::class,          'addComment']); // TO BE TESTED!
        Route::post('/reply-to-comment',                [CommentController::class,          'replyToComment']); // OK!
    });   

    /***| SEARCH ROUTES |***/
    Route::post('/product-comments',                    [CommentController::class,          'productComments']); // OK!

    /***| SEARCH ROUTES |***/
    Route::post('/search-autocomplete',                 [SearchController::class,           'getAutocomplete']); // OK!
    Route::post('/search-with-category',                [SearchController::class,           'searchWithCategoryResults']); // OK!
    Route::post('/search-products',                     [SearchController::class,           'searchProductsResult']); // OK!

    /*##### Guest Based Routes #####*/
    Route::post('/guest-cart',                          [CartController::class,             'guestCart']); // OK!
    Route::post('/guest-add-to-cart',                   [CartController::class,             'guestAddToCart']); // OK!
    Route::post('/guest-check-cart-changes',            [CartController::class,             'checkGuestCartChanges']); // ****** I HAVE TO WORKD ON THIS FIRST ******
    //Route::post('/cpd', [CategoryController::class, 'calculateProductsDiscount']);

    /*#####| USER API ROUTES |#####*/
    Route::post('/UserUpdate',                          [UserController::class,             'updateUser']); // TO BE TESTED!

    Route::post('/product-gtm-information',             [ProductController::class,          'productGoogleTagManagerInformation']);

    /*#####| HOME ROUTES |#####*/
    Route::get('/home-information',                     [HomeController::class,             'homeInformation']);

    Route::get('/testdate', function(Request $request){
        $d = '2022-1-12 14:33:24';
        $time = strtotime($d);
        echo jdate('y-m-d H:i:s', 1647691388);
    });
    Route::get('/tokensss', function(){
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI1IiwianRpIjoiOWU4MzlkM2JlMzk2M2Y1ODMzNDk4MTg2YzRiYjEwYTUwMDJiZTg1YjQ2ZWI1MTNmYzIxMWM3ZmYxYWQzNWY3NTUwZmE3Njc4Mzc0ZGIyNDIiLCJpYXQiOiIxNjQ0NzQxMzMwLjIwMzkxMSIsIm5iZiI6IjE2NDQ3NDEzMzAuMjAzOTIyIiwiZXhwIjoiMTY3NjI3NzMzMC4xOTU2OTIiLCJzdWIiOiIyNDAzMDgiLCJzY29wZXMiOltdfQ.THjjkHvO3cAvOWAK41HL-DEIacFENdF_QkducBzkywSQP-7HrwQrzCfi2TjztCBwA_wzeql-zCP9ANoO_cnffufw2mvGLA07HPuzYbVEMfRsVMLMfzkQtlOZ0tS3G6X40b1jcgicBwi9LqmwJGglQy5JsGGuca-oGUElT6nnk5w5DX5meBdY4eBRp5phV-kLORc7_85ix3VUF8hIoWgWCzEh_zLr1MW-AG_2xVz5UY8131V1C2fQ6Cc6qd6N6MdhkYp9wJIgq2PSdMcFy7yGQrjtgxbOB_GRTWrWu2-O9-sgvLexICz85e4_jwQeMPKXKYChkluoWQxPcapo_6SPcXHT3e33K88ViiJlI3qjH-hNjqzqf0jALpNh7p05V-yW790UW1q9vMETI04TQptOgARWFaymgxUnvOsx95k6uCjWq95olkiLv9pv4PAV75yytDEiR8tu6TWTAn8Le1345gDabiTGPQ5R0XFeSUQX1nO77w9K_p9Z_FmizXJxOVb1s16X-sT9iUIV-VmeO_e3EWnEHhadOUGwe50NsSd4aQwLqkSoqKlyxrnH_PWinhmmMPnTlxe66Beq6qyFf0MlfoEH2KKApiuRVPPjuznx4tUCgrHTpAqQYShDRKJtxLgveW9sMyJS9oG2w2b0vVgrIom1tUHK7NrRHAjOBSpmuUY';
        $t = md5($token);
        echo $token;
        echo '<br />';
        echo 'md5 : ' . $t;
        echo '<br />';
        echo 'md5 : ' . hash('md5', $token);
        echo '<br />';
        echo 'sha1 : ' . hash('sha1', $token);
        echo '<br />';
        echo 'sha256 : ' . hash('sha256', $token);
    });