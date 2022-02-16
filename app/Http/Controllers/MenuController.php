<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Classes\DiscountCalculator;
use stdClass;

class MenuController extends Controller
{
    //@route: /api/menu <--> @middleware: -----
    public function menuItemsInformation(Request $request){
        $parents = DB::select("SELECT id, name, url, parent_id, num FROM menu WHERE visiable = 1 AND level = 1 AND is_mobile = 0 ORDER BY num ASC");
        if(count($parents) == 0){
            echo json_encode(array('status' => 'done', 'found' => false, 'parents' => '[]', 'children' => '[]', 'message' => 'there is not any menu item available'));    
            exit();
        }
        $responseArray = array();
        foreach ($parents as $parent){
            $info = new stdClass;
            $info->parentName = $parent->name;
            $info->parentUrl = $parent->url;
            $info->parentId = $parent->id;  
            $info->children = array();
            $children = DB::select("SELECT name, url, parent_id, num FROM menu WHERE parent_id = $parent->id AND visiable = 1 AND level = 2 AND is_mobile = 0 ORDER BY num ASC");
            if(count($children) !== 0){
                foreach($children as $child){
                    array_push($info->children, array('name' => $child->name, 'url' => $child->url));
                }
            }
            array_push($responseArray, $info);
        }
        echo json_encode(array('status' => 'done', 'found' => true, 'menu' => $responseArray, 'message' => 'menu items successfully found'));
    }
}