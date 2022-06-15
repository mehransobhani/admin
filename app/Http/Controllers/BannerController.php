<?php

namespace App\Http\Controllers;

use App\Models\Art;
use App\Models\Banner;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    public function categoryBanners(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'مقادیر ورودی صحیح نیست'));
            exit();
        }

        if(!isset($request->id)){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'not enough parameter', 'umessage' => 'ورودی کافی نیست'));
            exit();
        }
        $categoryId = $request->id;
        $category = DB::select("SELECT * FROM category WHERE id = $categoryId LIMIT 1");
        if(count($category) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'category not found', 'umessage' => 'درسته بندی یافت نشد'));
            exit();
        }
        $category = $category[0];
        $time = time();
        $banners = DB::select(
            "SELECT img AS image, anchor AS url, description AS title
            FROM banners 
            WHERE cat_id = $categoryId AND (start_date = 0 OR start_date <= $time) AND (end_date = 0 OR end_date >= $time) AND isActive = 1 AND isBanner = 6 
            ORDER BY _order ASC "
        );
        echo json_encode(array('status' => 'done', 'found' => true, 'message' => 'banners successfully found', 'banners' => $banners));
        /*if(isset($request->id)){
            $category = Category::where('id', $request->id);
            if($category->count() !== 0){
                $category = $category->first();
                $isBanner = 6;
                $refrenceColumn = 'cat_id';
                $refrenceId = $request->id;
                if($category->level === 1 && $category->parentID === 0){
                    $art = Art::where('catID', $request->id);
                    if($art->count() !== 0){
                        $art = $art->first();
                        $refrenceId = $art->id;
                    }else{
                        $refrenceId = 0;
                    }
                    $isBanner = 6;
                    $refrenceColumn = 'artID';
                }
                $banners = Banner::where($refrenceColumn, $refrenceId)->where('isActive', 1)->where('isBanner', $isBanner)->where(function($query){
                    return $query->where(function($query){
                        return $query->where('start_date', 0)->where('end_date', 0);
                    })->orWhere(function($query){
                        $time = time();
                        return $query->where('start_date', '<=', $time)->where('end_date', '>=', $time);
                    });
                })->orderBy('_order', 'ASC');
                if($banners->count() !== 0){
                    $banners = $banners->get();
                    $response = [];
                    foreach($banners as $banner){
                        array_push($response, array('id' => $banner->id, 'title' => $banner->description, 'image' => $banner->img, 'url' => $banner->anchor));
                    }
                    echo json_encode(array('status' => 'done', 'found' => true, 'banners' => $response));
                }else{
                    echo json_encode(array('status' => 'done', 'found' => false));
                }
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'category not found'));
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough parameter'));
        }*/
    }

    public function topSixBestsellerSimilarProducts(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if($validator->fails()){
            echo json_encode(array('status' => 'failed', 'source' => 'v', 'message' => 'argument validation failed', 'umessage' => 'مقادیر ورودی صحیح نیست'));
            exit();
        }
        if(isset($request->id)){
            $category = Category::where('id', $request->id);
            if($category->count() !== 0){
                $category = $category->first();
                
            }else{
                echo json_encode(array('status' => 'failed', 'message' => 'product not found'));
            }
        }else{
            echo json_encode(array('sttatus' => 'failed', 'message'=> 'not enough parameter'));
        }
    }

    public function topThreeHomeBanners(Request $request){
	set_time_limit(4);
        $time = time();
        $firstBanner = DB::select(
            "SELECT img, anchor
            FROM banners
            WHERE isActive = 1 AND isBanner = 3 AND _order = 1 AND ((start_date = 0 AND end_date = 0) OR (start_date <= $time AND end_date >= $time))
            ORDER BY date DESC 
            LIMIT 1"
        );
        $secondBanner = DB::select(
            "SELECT img, anchor
            FROM banners
            WHERE isActive = 1 AND isBanner = 3 AND _order = 2 AND ((start_date = 0 AND end_date = 0) OR (start_date <= $time AND end_date >= $time))
            ORDER BY date DESC 
            LIMIT 1"
        );
        $thirdBanner = DB::select(
            "SELECT img, anchor
            FROM banners
            WHERE isActive = 1 AND isBanner = 3 AND _order = 5 AND ((start_date = 0 AND end_date = 0) OR (start_date <= $time AND end_date >= $time))
            ORDER BY date DESC 
            LIMIT 1"
        );
        if(count($firstBanner) === 0 || count($secondBanner) === 0 || count($thirdBanner) === 0){
            echo json_encode(array('status' => 'failed', 'source' => 'c', 'message' => 'banner not found', 'umessage' => 'خطا در بارگزاری بنرها'));
            exit();
        }
        $firstBanner = $firstBanner[0];
        $secondBanner = $secondBanner[0];
        $thirdBanner = $thirdBanner[0];
        $banners = [$firstBanner, $secondBanner, $thirdBanner];
        echo json_encode(array('status' => 'done', 'message' => 'banners successfully found', 'banners' => $banners));
    }

    public function topSixLikedCategores(Request $request){
        $time = time();
        $banners = DB::select(
            "SELECT id, img AS image, anchor AS url 
            FROM banners 
            WHERE isBanner = 7 AND isActive = 1 AND (start_date = 0 OR start_date <= $time) AND (end_date = 0 OR end_date >= $time) 
            ORDER BY _order ASC, `date` DESC 
            LIMIT 6"
        );
        if(count($banners) === 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'banners' => [], 'message' => 'could not find liked banners', 'دسته بنده‌های پرطرفدار یافت نشد'));
            exit();
        }
        echo json_encode(array('status' => 'done', 'found' => true, 'banners'=> $banners, 'message' => 'banners successfully are found'));
    }
}
