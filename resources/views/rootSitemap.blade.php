<?php
        header('Content-type: application/xml');
        echo '<?xml version="1.0" encoding="UTF-8" ?>';
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $allProducts = DB::select("SELECT id, `url`, prodDate FROM products WHERE url <> '' AND `url` IS NOT NULL ORDER BY id DESC");

        $numberOfFiles = ceil(count($allProducts) / 500);
        $i = 1;
        
        for($i=0; $i< $numberOfFiles; $i++){
            echo '<sitemap>';
            echo '<loc>https://honari.com/sa/sitemap/product' . ($i + 1) . '.xml</loc>';
            echo '<lastmod>' . date('Y-m-d', $allProducts[$i * 500]->prodDate) . '</lastmod>';
            echo '</sitemap>';
        }
        echo '</sitemapindex>';
?>