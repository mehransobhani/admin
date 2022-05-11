<?php

namespace App\Http\Controllers;

use App\Classes\DiscountCalculator;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use stdClass;

class SitemapController extends Controller
{
    public function rootSitemap(Request $request){
        
        
        $sitemap =  '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $allProducts = DB::select("SELECT id, `url`, prodDate FROM products WHERE url <> '' AND `url` IS NOT NULL AND prodStatus = 1 ORDER BY id DESC");

        $numberOfFiles = ceil(count($allProducts) / 500);
        $i = 1;

        $sitemap =  $sitemap . '<sitemap>';
        $sitemap =  $sitemap . '<loc>https://honari.com/sa/sitemap/primary.xml</loc>';
        $sitemap =  $sitemap . '<lastmod>' . date('Y-m-d') . '</lastmod>';
        $sitemap =  $sitemap . '</sitemap>';
        
        for($i=0; $i< $numberOfFiles; $i++){
            $sitemap =  $sitemap . '<sitemap>';
            $sitemap =  $sitemap . '<loc>https://honari.com/sa/sitemap/product' . ($i + 1) . '.xml</loc>';
            $sitemap =  $sitemap . '<lastmod>' . date('Y-m-d', $allProducts[$i * 500]->prodDate) . '</lastmod>';
            $sitemap =  $sitemap . '</sitemap>';
        }
        $sitemap =  $sitemap . '<sitemap>';
        $sitemap =  $sitemap . '<loc>https://honari.com/sa/sitemap/classes.xml</loc>';
        $sitemap =  $sitemap . '<lastmod>' . date('Y-m-d') . '</lastmod>';
        $sitemap =  $sitemap . '</sitemap>';
        $sitemap =  $sitemap . '<sitemap>';
        $sitemap =  $sitemap . '<loc>https://honari.com/sa/sitemap/arts.xml</loc>';
        $sitemap =  $sitemap . '<lastmod>' . date('Y-m-d') . '</lastmod>';
        $sitemap =  $sitemap . '</sitemap>';
        $sitemap =  $sitemap . '</sitemapindex>';

        return response($sitemap)->withHeaders(['Content-Type' => 'text/xml']);
    }

    public function classSitemap(Request $request){
        $url = 'https://academy.honari.com/sitemap/class.xml';
        $sitemap = file_get_contents($url);
        return response($sitemap)->withHeaders(['Content-Type' => 'text/xml']);
    }

    public function artSitemap(Request $request){
        $url = 'https://academy.honari.com/sitemap/arts.xml';
        $sitemap =  file_get_contents($url);
        return response($sitemap)->withHeaders(['Content-Type' => 'text/xml']);
    }

    public function artsStepByStep(Request $request){
        $arts = DB::select("SELECT urlKey FROM arts WHERE catID <> 0 ORDER BY id DESC");
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach($arts as $art){
            $sitemap = $sitemap . '<url>';
            $sitemap = $sitemap . '<loc>https://honari.com/stepbysteps/arts/' . $art->urlKey . '</loc>';
            $sitemap = $sitemap . '<lastmod>2017-04-13</lastmod>';
            $sitemap = $sitemap . '<changefreq>weekly</changefreq>';
            $sitemap = $sitemap . '<priority>0.75</priority>';
            $sitemap = $sitemap . '</url>';
        }
        $sitemap = $sitemap . '</urlset>';
        return response($sitemap)->withHeaders(['Content-Type' => 'text/xml']);
    }

    public function stepByStep(Request $request){ 
        $steps = DB::select( 
            "SELECT S.urlKey, S.name, S.time  
            FROM stepBySteps S  
            LEFT JOIN course_class CC ON CC.id = S.class_id 
            WHERE S.status = 1 AND S.class_id > 0 AND (CC.price = 0 OR CC.off = CC.price ) 
            ORDER BY S.id DESC " 
        );

        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach($steps as $step){
            $sitemap = $sitemap . '<url>';
            $sitemap = $sitemap . '<loc>https://honari.com/stepbysteps/' . $step->urlKey . '/' . $step->name . '</loc>';
            $sitemap = $sitemap . '<lastmod>' . date('Y-m-d', $step->time) . '</lastmod>';
            $sitemap = $sitemap . '<changefreq>monthly</changefreq>';
            $sitemap = $sitemap . '<priority>0.60</priority>';
            $sitemap = $sitemap . '</url>';
        }
        $sitemap = $sitemap . '</urlset>';
        return response($sitemap)->withHeaders(['Content-Type' => 'text/xml']);
    }

    public function productSiteMap(Request $request){
        $step = $request->route('id');

        $limit = 500;
        $offset = ($step - 1) * $limit;

        $products = DB::select("SELECT url, prodDate FROM products WHERE url <> '' AND url IS NOT NULL ORDER BY id DESC LIMIT $limit OFFSET $offset ");

        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach($products as $product){
            $sitemap = $sitemap . '<url>';
            $sitemap = $sitemap . '<loc>https://honari.com/' . urlencode($product->url) . '</loc>';
            $sitemap = $sitemap . '<lastmod>' . date('Y-m-d', $product->prodDate) . '</lastmod>';
            $sitemap = $sitemap . '<changefreq>monthly</changefreq>';
            $sitemap = $sitemap . '<priority>0.60</priority>';
            $sitemap = $sitemap . '</url>';
        }
        $sitemap = $sitemap . '</urlset>';

        return response($sitemap)->withHeaders(['Content-Type' => 'text/xml']);
    }

    public function categories(Request $request){
        $categories = DB::select(
            "SELECT C.id, C.url, C.parentID,(SELECT P.prodDate 
                                    FROM products P 
                                    INNER JOIN product_category PC ON P.id = PC.product_id 
                                    WHERE PC.category = C.id AND P.prodStatus = 1 AND P.prodDate IS NOT NULL 
                                    ORDER BY P.prodDate DESC LIMIT 1
                                ) AS `date` 
            FROM category C WHERE C.hide = 0 ORDER BY C.id ASC "
        );

        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach($categories as $category){
            $sitemap = $sitemap . '<url>';
            $sitemap = $sitemap . '<loc>https://honari.com/shop/product/category' . urlencode($category->url) . '</loc>';
            $sitemap = $sitemap . '<lastmod>' . date('Y-m-d', $category->date) . '</lastmod>';
            if($category->parentID == 0){
                $sitemap = $sitemap . '<priority>0.80</priority>';
            }else{
                $sitemap = $sitemap . '<priority>0.70</priority>';
            }
            $sitemap = $sitemap . '</url>';
        }

        $sitemap = $sitemap . '</urlset>';

        return response($sitemap)->withHeaders(['Content-Type' => 'text/xml']);
        
    }

    public function primary(Request $request){
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        //$sitemap = '<url><loc>https://honari.com</loc><lastmod>' . date('Y-m-d') . '</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>';
        $sitemap = $sitemap . '<url><loc>https://honari.com</loc><lastmod>' . date('Y-m-d') . '</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>';
        $artPages = DB::select("SELECT * FROM art_page ORDER BY id ASC ");

        foreach($artPages as $artPage){
            $date = DB::select("SELECT P.prodDate FROM arts A 
                INNER JOIN product_category PC ON PC.category = A.catID 
                INNER JOIN products P ON P.id = PC.product_id 
                WHERE A.id = $artPage->art_id AND P.prodStatus = 1 
                ORDER BY P.prodDate DESC 
                LIMIT 1 ");
            $sitemap = $sitemap . '<url>';
            $sitemap = $sitemap . '<loc>https://honari.com/' . urldecode($artPage->url_fa) . '</loc>';
            if(count($date) !== 0){
                $date = $date[0];
                $sitemap = $sitemap . '<lastmod>' . date('Y-m-d', $date->prodDate). '</lastmod>';
            }
            $sitemap = $sitemap . '<changefreq>weekly</changefreq>';
            $sitemap = $sitemap . '<priority>0.90</priority>';
            $sitemap = $sitemap . '</url>';
        }

        $sitemap = $sitemap . '</urlset>';

        return response($sitemap)->withHeaders(['Content-Type' => 'text/xml']);
    }
}
