<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

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
            $r = 'shop/product/category/' . $request->route;
            $product = Product::where('url', $r);
            if($product->count() !== 0){
                $product = $product->first();
                echo json_encode(array('status' => 'done', 'found' => true, 'type' => 'product', 'id' => $product->id, 'prodID' => $product->prodID, 'name' => $product->prodName_fa));
            }else{
                $category = Category::where('url', $request->route);
                if($category->count() !== 0){
                    $category = $category->first();
                    echo json_encode(array('status' => 'done', 'found' => true, 'type' => 'category', 'id' => $category->id, 'name' => $category->name, 'level' => $category->level, 'featureGroupId', $category->feature_group_id));
                }else{
                    echo json_encode(array('status' => 'failed', 'found' => false, 'message' => 'url did not recognized'));
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
