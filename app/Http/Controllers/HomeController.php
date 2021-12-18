<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        if(isset($request->route)){
            $route = substr($request->route, 22); 
            $r = $request->route;

            $product = DB::select(
                "SELECT * FROM products WHERE url = '$r' LIMIT 1"
            );
            if(count($product) !== 0){
                $product = $product[0];
                echo json_encode(array('status' => 'done', 'found' => true, 'type' => 'product', 'id' => $product->id, 'prodID' => $product->prodID, 'name' => $product->prodName_fa, 'description' => $product->prodDscb));
            }else{
                $category = DB::select(
                    "SELECT * FROM category WHERE url = '$route' LIMIT 1"
                );
                if(count($category) !== 0){
                    $category = $category[0];
                    echo json_encode(array('status' => 'done', 'found' => true, 'type' => 'category', 'id' => $category->id, 'name' => $category->name, 'level' => $category->level, 'featureGroupId', $category->feature_group_id));
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
                    if(count($category) === 0){
                        echo json_encode(array('status' => 'failed', 'found' => false, 'url' => $r, 'source' => 'c', 'message' => 'url not found', 'umessage' => 'آدرس اشتباه است'));
                        exit();
                    }
                    $category = $category[0];
                    echo json_encode(array('status' => 'done', 'found' => true, 'type' => 'category', 'id' => $category->id, 'name' => $category->name, 'level' => $category->level, 'featureGroupId', $category->feature_group_id));
                }
            }
        }else{
            echo json_encode(array('status' => 'failed', 'message' => 'not enough information'));
        }
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
