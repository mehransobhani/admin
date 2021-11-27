<?php

namespace App\Http\Controllers;

use App\Models\Art;
use App\Models\Banner;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class BannerController extends Controller
{
    public function categoryBanners(Request $request){
        if(isset($request->id)){
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
                    $isBanner = 5;
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
        }
    }

    public function topSixBestsellerSimilarProducts(Request $request){
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
}
