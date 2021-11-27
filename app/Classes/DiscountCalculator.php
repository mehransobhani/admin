<?php
namespace App\Classes;
use Illuminate\Support\Facades\DB;
use stdClass;

class DiscountCalculator{ 

    public static function calculateProductDiscount($product){
        $time = time();
        $discounts = DB::select("SELECT * FROM discounts D WHERE D.status = 1 AND D.type IN ('category', 'product') AND D.code IS NULL AND D.neworder = 0 
            AND (D.numbers_left IS NULL OR D.numbers_left > 0) AND D.start_date IS NULL AND D.finish_date IS NULL AND (D.expiration_date IS NULL OR D.expiration_date >= $time)");
        $reducedPrice = 0;
        foreach($discounts as $discount){
            $discountUsers = DB::select("SELECT DD.dependency_id AS user_id FROM discount_dependencies DD WHERE DD.discount_id = $discount->id AND DD.dependency_type = 'user'");
            $discountProvinces = DB::select("SELECT DD.dependency_id AS province_id FROM discount_dependencies DD WHERE DD.discount_id = $discount->id AND DD.dependency_type = 'province'");
            $discountProducts = DB::select("SELECT DD.dependency_id AS product_id FROM discount_dependencies DD WHERE DD.discount_id = $discount->id AND DD.dependency_type = 'product'");
            $discountCategories =  DB::select("SELECT DD.dependency_id AS category_id FROM discount_dependencies DD WHERE DD.discount_id = $discount->id AND DD.dependency_type = 'category'");
            if(count($discountUsers) === 0 && count($discountProvinces) === 0){
                if($discount->min_price === null || $discount->min_price <= $product->productPrice){
                    $inputProductObject = new stdClass();
                    $inputCategoryObject = new stdClass();
                    $inputProductObject->product_id = $product->productId;
                    $inputCategoryObject->category_id = $product->categoryId;
                    
                    if(($discount->type === 'product' && (count($discountProducts) === 0 || in_array($inputProductObject, $discountProducts))) ||
                        ($discount->type === 'category' && (count($discountCategories) === 0 || in_array($inputCategoryObject, $discountCategories)))){
                        $rp = 0;
                        if($discount->price !== null){
                            $rp += $discount->price;
                        }
                        if($discount->percent !== null){
                            $rp = ($discount->percent / 100) * $product->productPrice;
                        }
                        if($discount->max_price !== null && $rp > $discount->max_price){
                            $rp = $discount->max_price;
                        }
                        $reducedPrice += $rp;
                        $product->rp = $reducedPrice;
                    }
                }
            }
        }
        if($reducedPrice !== 0){
            if($reducedPrice < $product->productPrice){
                $product->discountedPrice = $product->productPrice - (100 * (integer)(($reducedPrice) / 100));
                $product->discountPercent = (integer)(($reducedPrice/$product->productPrice) * 100);
            }else{
                $product->discountedPrice = 0;
                $product->discountPercent = 100;
            }
        }else{
            $product->discountedPrice = $product->productPrice;
            $product->discountPercent = 0;
        }
        return $product;
    }

    public static function calculateProductsDiscount($products){
        $time = time();
        $discounts = DB::select("SELECT * FROM discounts D WHERE D.status = 1 AND D.type IN ('product', 'category') AND D.code IS NULL AND D.neworder = 0 
            AND (D.numbers_left IS NULL OR D.numbers_left > 0) AND D.start_date IS NULL AND D.finish_date IS NULL AND D.reusable = 1
            AND (D.expiration_date IS NULL OR D.expiration_date >= $time)");
        if(count($discounts) !== 0){
            $rps = array();
            foreach($products as $product){
                array_push($rps, 0);
            }
            foreach($discounts as $discount){
                $discountUsers = DB::select("SELECT DD.dependency_id AS user_id FROM discount_dependencies DD WHERE DD.discount_id = $discount->id AND DD.dependency_type = 'user'");
                $discountProvinces = DB::select("SELECT DD.dependency_id AS province_id FROM discount_dependencies DD WHERE DD.discount_id = $discount->id AND DD.dependency_type = 'province'");
                $discountProducts = DB::select("SELECT DD.dependency_id AS product_id FROM discount_dependencies DD WHERE DD.discount_id = $discount->id AND DD.dependency_type = 'product'");
                $discountCategories =  DB::select("SELECT DD.dependency_id AS category_id FROM discount_dependencies DD WHERE DD.discount_id = $discount->id AND DD.dependency_type = 'category'");
                if(count($discountUsers) === 0 && count($discountProvinces) === 0){
                    $i = 0;
                    foreach($products as $product){
                        if($discount->min_price === null || $discount->min_price <= $product->productPrice){
                            $inputProductObject1 = new stdClass();
                            $inputCategoryObject1 = new stdClass();
                            $inputProductObject1->product_id = $product->productId;
                            $inputCategoryObject1->category_id = $product->categoryId;
                            if(($discount->type === 'product' && (count($discountProducts) === 0 || in_array($inputProductObject1, $discountProducts))) ||
                                ($discount->type === 'category' && (count($discountCategories) === 0 || in_array($inputCategoryObject1, $discountCategories)))){
                                $rp = 0;
                                if($discount->price !== null){
                                    $rp += $discount->price;
                                }
                                if($discount->percent !== null){
                                    $rp = ($discount->percent / 100) * $product->productPrice;
                                }
                                if($discount->max_price !== null && $rp > $discount->max_price){
                                    $rp = $discount->max_price;
                                }
                                $rps[$i] += $rp;
                            }
                        }
                        $i++;
                    }
                }
            }
            $i = 0;
            foreach($products as $product){
                if($rps[$i] !== 0){
                    if($rps[$i] < $products[$i]->productPrice){
                        $products[$i]->discountedPrice = $products[$i]->productPrice - (100 * (integer)(($rps[$i]) / 100));
                        $products[$i]->discountPercent = (integer)(($rps[$i]/$products[$i]->productPrice) * 100);
                    }else{
                        $products[$i]->discountedPrice = 0;
                        $products[$i]->discountPercent = 100;
                    }
                }else{
                    $products[$i]->discountedPrice = $products[$i]->productPrice;
                    $products[$i]->discountPercent = 0;
                }
                $i++;
            }
            return $products;
        }else{
            foreach($products as $product){
                $product->discountedPrice = $product->productPrice;
                $product->discountPercent = 0;
            }
            return $products;
        }
    }
    
    public static function calculateDeliveryDiscount($userId, $provinceId, $orderPrice, $productsInformation, $deliveryOptions){
        $now = time();
        $discountPercent = 0;
        $discountPrice = 0;
        foreach($deliveryOptions as $deliveryOption){
            $deliveryOption->discountedPrice = $deliveryOption->price;
            $deliveryOption->discountPercent = 0;
        }
        $discounts = DB::select(
                            "SELECT * 
                            FROM discounts 
                            WHERE type = 'shipping' AND status = 1 AND code IS NULL AND 
                            ((start_date IS NULL AND finish_date IS NULL) OR (start_date <= $now AND $now <= finish_date)) AND 
                            ((expiration_date IS NULL) OR ($now <= expiration_date))
                            ORDER BY date DESC, id DESC"
                        );
        if(count($discounts) === 0){
            return $deliveryOptions;
        }
        foreach($discounts as $discount){
            if($discount->numbers_left !== NULL && $discount->numbers_left === 0){
                continue;
            }
            if($discount->min_price != NULL && $orderPrice < $discount->min_price ){
                continue;
            }
            $discountUserDependencies = DB::select("SELECT dependency_id FROM discount_dependencies WHERE discount_id = $discount->id AND dependency_type = 'user' ");
            $discountProvinceDependencies = DB::select("SELECT dependency_id FROM discount_dependencies WHERE discount_id = $discount->id AND dependency_type = 'province' ");
            $discountProductDependencies = DB::select("SELECT dependency_id FROM discount_dependencies WHERE discount_id = $discount->id AND dependency_type = 'product' ");
            $discountCategoryDependencies = DB::select("SELECT dependency_id FROM discount_dependencies WHERE discount_id = $discount->id AND dependency_type = 'category' ");
            if(count($discountUserDependencies) !== 0){
                $approved = false;
                foreach($discountUserDependencies as $item){
                    if($item->dependency_id == $userId){
                        $approved = true;
                        break;
                    }
                }
                if(!$approved){
                    continue;
                }
            }
            if(count($discountProvinceDependencies) !== 0){
                $approved = false;
                foreach($discountProvinceDependencies as $item){
                    if($item->dependency_id == $provinceId){
                        $approved = true;
                        break;
                    }
                }
                if(!$approved){
                   continue;
                }
            }
            if(count($discountProductDependencies) !== 0){
                $approved = false;
                foreach($discountProductDependencies as $item){
                    $dependencyId = $item->dependency_id;
                    foreach($productsInformation as $pi){
                        if($pi->productId === $dependencyId){
                            $approved = true;
                            break;
                        }
                    }
                    if($approved){
                        break;
                    }
                }
                if(!$approved){
                    continue;
                }
            }
            if(count($discountCategoryDependencies) !== 0){
                $approved = false;
                foreach($discountCategoryDependencies as $item){
                    $dependencyId = $item->dependency_id;
                    foreach($productsInformation as $pi){
                        if($pi->categoryId === $dependencyId){
                            $approved = true;
                            break;
                        }
                    }
                    if($approved){
                        break;
                    }
                }
                if(!$approved){
                    continue;
                }
            }
            if($discount->neworder !== 0){
                $userPreviousOrders = DB::select("SELECT id FROM orders WHERE user_id = $userId");
                if(count($userPreviousOrders) !== 0){
                    continue;
                }
            }
            if($discount->reusable === 0){
                $userPreviousUsedDiscounts = DB::select("SELECT id FROM discount_logs WHERE discount_id = $discount->id");
                if(count($userPreviousUsedDiscounts) !== 0){
                    continue;
                }
            }
            if($discount->percent !== NULL){
                $discountPercent += $discount->percent;
            }else if($discount->price !== NULL){
                $discountPrice += $discount->price;
            }
        }
        foreach($deliveryOptions as $deliveryOption){
            if($discountPercent !== 0){
                $deliveryOption->discountedPrice -= $deliveryOption->price * ((float)$discountPercent / 100);
            }
            if($discountPrice !== 0){
                $deliveryOption->discountedPrice -= $discountPrice;
            }
            if($deliveryOption->discountedPrice < 0){
                $deliveryOption->discountedPrice = 0;
            }
        }
        return $deliveryOptions;
    }
}