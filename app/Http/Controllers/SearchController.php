<?php

namespace App\Http\Controllers;

use App\Models\Art;
use App\Models\Banner;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use stdClass;

class SearchController extends Controller
{

    private static $BASE_URL = 'http://search.honari.com';
    private static $AUTO_COMPLETE_URL = '/api/v1/management/search/autocomplete';
    private static $PRODUCT_API_TOKEN = '21bb3b6e-0f96-4718-8d6c-8f03a538927e';

    public function getAutocomplete(Request $request){
        $query = $request->input;
        $autoCompleteUrl = self::$BASE_URL . self::$AUTO_COMPLETE_URL . '/?apiToken=' . self::$PRODUCT_API_TOKEN . '&query=' . urlencode($query);
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
        if(!isset($request->category) || !isset($request->facets) || !isset($request->page)){
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter', 'umessage' => 'ورودی ها کافی نیست'));
            exit();
        }
        $category = $request->category;
        $facets = $request->facets;
        $page = $request->page;

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
        if(!isset($request->category) || !isset($request->page)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }

        //$query = urlencode($request->category->get('query'));
        
        $query = urlencode($request->category);
        $page = $request->page;
        
        //$query = urlencode($query);

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
