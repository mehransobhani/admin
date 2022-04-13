<?php

namespace App\Http\Controllers;

use App\Models\Art;
use App\Models\Banner;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use stdClass;

class SearchController extends Controller
{

    private static $BASE_URL = 'http://search.honari.com';
    private static $AUTO_COMPLETE_URL = '/api/v1/management/search/autocomplete';
    private static $PRODUCTS_API_TOKEN = '21bb3b6e-0f96-4718-8d6c-8f03a538927e';
    private static $PRODUCTS_AND_COURSES_API_TOKEN = 'a880cb7b-1194-416f-94cc-87b6db4fb450';
    public function getAutocomplete(Request $request){
        $validator = Validator::make($request->all(), [
            'input' => 'required|string',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $query = $request->input;
        $autoCompleteUrl = self::$BASE_URL . self::$AUTO_COMPLETE_URL . '/?apiToken=' . self::$PRODUCTS_AND_COURSES_API_TOKEN . '&query=' . urlencode($query);
        $ch = curl_init($autoCompleteUrl);
        curl_setopt($ch, CURLOPT_USERAGENT, 'HONARI USER');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        //curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            //'Authorization: Bearer ' . '',
            //'Content-Length: ' . strlen($jsonData)
        ));
        $result = curl_exec($ch);
        $error = curl_error($ch);
        if($error){
            echo json_encode(array('status' => 'failed', 'source' => 'curl', 'message' => 'curl had some errors', 'umessage' => 'خطا هنگام خواندن نتایج جستجو'));
            exit();
        }
        $result = json_decode($result);
        echo json_encode(array('status' => 'done', 'message' => 'search was successfull', 'result' => $result));
    }

    public function searchWithCategoryResults(Request $request){
        $validator = Validator::make($request->all(), [
            'category' => 'required|string', 
            'facets' => 'array', 
            'page' => 'required|numeric', 
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }

        $category = trim($request->category);
        $facets = $request->facets;
        $page = $request->page;
        $found = false;
        /*$beginingWhitespaceCategoreies = DB::select(
            "SELECT id, name, url, parentId FROM category WHERE name LIKE ' %' AND name NOT LIKE ' % ' AND hide = 0 "
        );
        $finishingWhitespaceCategories = DB::select(
            "SELECT id, name, url, parentId FROM category WHERE name LIKE '% ' AND name NOT LIKE ' % ' AND hide = 0 "
        );
        $bothSideWhitespaceCatedgories = DB::select(
            ""
        );*/
        $beginingWhitespaceCategoreies = [
            'نمد دوزی',
            'بند کیف', 
            'خمیر دورگیر ويترای',
            'بیدوسفر',
            'چسب ،وارنیش ، مدیوم',
            'ابزار و لوازم خیاطی',
            'چوب طبیعی',
            'وارنیش فیکساتیو و براق کننده'
        ];
        $finishingWhitespaceCategories = [
            'تخته و گیره',
            'قیچی',
            'قلم مو',
            'ظروف سرامیکی',
            'شابلون هویه کاری',
            'کاموا',
            'بسته های شروع و پروژه ها',
            'ملیله',
            'پارچه شماره دوزی',
            'نخ کنفی',
            'رنگ تخته سیاه',
            'چرم طبیعی',
            'پروژه شماره دوزی',
            'سنگ عقیق',
            'پارچه کتان',
            'رزین و چوب',
            'مهره فیمو واشری',
            'لوازم شمع سازی',
            'پارافین'
        ];
        $bothSideWhitespaceCatedgories = [
            'اکلیل'
        ];
        
        if(in_array($category, $beginingWhitespaceCategoreies)){
            $category = ' ' . $category;
        }else if(in_array($category, $finishingWhitespaceCategories)){
            $category = $category . ' ';
        }else if(in_array($category, $bothSideWhitespaceCatedgories)){
            $category = ' ' . $category . ' ';
        }

        $data = [
            'category' => $category,
            'facets' => $facets,
            'indexName' => 'products',
            'query' => null,
            'page' => $page,
            'size' => 12,
            'sort' => 'has_stock'
        ];
    
        $data = json_encode($data);
      
        $url = 'http://search.honari.com/api/v1/management/search/searchWithCategory';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'HONARI USER');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            //'Authorization: Bearer ',
            //'Content-Length: ' . strlen($jsonData)
        ));
        $result = curl_exec($ch);
        $result = json_decode($result);
        echo json_encode(array('status' => 'done', 'message' => 'data is successfully found', 'result' => $result));
    }

    public function searchProductsResult(Request $request){
        $validator = Validator::make($request->all(), [
            'category' => 'required|string', 
            'page' => 'required|numeric', 
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'خطا در دریافت مقادیر ورودی'));
            exit();
        }
        
        $query = urlencode($request->category);
        $page = $request->page;

        $url = 'http://search.honari.com/api/v1/management/search/search/?apiToken=21bb3b6e-0f96-4718-8d6c-8f03a538927e&query=' . $query . '&page=' . $page . '&size=12&sort=has_stock';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'HONARI USER');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        $result = curl_exec($ch);
        $result = json_decode($result);
        echo json_encode(array('status' => 'done', 'result' => $result));
    }
}
