<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoryData;
use App\Models\CmsPage;
use App\Models\BannerSlider;
use App\Models\ProductCategory;
use App\Models\FlatCatalog;
use App\Models\ProductGallery;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ShopTheLook;
use App\Models\CheckOutPinCode;
use App\Models\ShipRocketToken;
use App\Models\StoreLocator;
use App\Models\Product;
use App\Models\PimInventory;
use App\Models\ColorsMap;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiController extends BaseController
{
    //
    const SUCCESS_MSG = 'success';
    const RECORD_FOUND = 'Record found';
    const RECORD_NOT = 'Record not found';
    const SOMETHING_WRONG = 'Something Went Wrong';
    const ADMIN_BASE_URL = "https://getketchadmin.getketch.com/";
    const SHIP_ROCKET_AUTH_URL = "https://apiv2.shiprocket.in/v1/external/auth/login";
    const SHIP_ROCKET_SERVICEABLE = "https://apiv2.shiprocket.in/v1/external/courier/serviceability/?";
    const SHIP_ROCKET_POSTCODE_DETAIL = 'https://apiv2.shiprocket.in/v1/external/open/postcode/details?';
    const SHIP_ROCKET_CREDENTIALS = '{"email": "info@brandstudiolifestyle.com","password": "Bslpl@2021"}';
    const EXTRA_DELIVERY_CHARGE_LOCALITY = array('Delhi', 'Mizoram', 'Assam', 'Nagaland', 'Manipur', 'Meghalaya', 'Arunachal Pradesh', 'Tripura', 'Jammu & Kashmir', 'Sikkim');
    const PIM_URL = "https://ketchpim.greenhonchos.com/pim/pimresponse.php/";
    const Breadcrumb_BaseUrl = 'https://www.getketch.com/';
    const PIN_CODE_REQUIRED = '<span class="error">Pincode require</span>';
    const VALID_PIN_CODE = '<span class="error">Please input a 6 digit valid pincode.</span>';
    const COD_AVAILABLE = '<span class="span3">COD available</span>';
    const SERVICE_NOT_AVAILABLE = '<span class="error">Delivery pincode not serviceable</span>';
    const PICKUP_NOT_SERVICEABLE = '<span class="error">Pickup pincode is not serviceable</span>';
    const SOMETHING_WRONG_PIN_NOT = '<span class="error">SomeThing Went Wrong, Pincode Details Not Found, Try another pincode</span>';
    const PREPAID_NOT_ALLOWED = '<span class="error">Prepaid Not allowed</span>';
    const DELIVERY_NOT_SERVICEABLE = '<span class="error">Delivery pincode not serviceable test</span>';
    const EASY_RETURN_EXCHANGE = '<span class="span4">Easy 10 days returns and exchanges</span>';
    const ITEM_REQUIRED = 'Please input the items.';

    /**
     * get_menus
     *
     * @param  mixed $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_result(Request $request): \Illuminate\Http\JsonResponse{
        // try{
        $validator = Validator::make($request->all(), [
            'store' => 'required',
            'service' => 'required'
        ]);
        //if condition work when validation failed
        if ($validator->fails()) {


            return $this->sendError('Validation Error.', $validator->errors());
        }

        $response = array();
        $response['response']['success'] = "0";
        $response['response']['success_message'] = '';
        $response['response']['error'] = "0";
        $response['response']['error_message'] = '';
        $response['query'] = '';
        $response['result'] = '';
        // $queryResult=array();
        // if ( isset( $_GET ) ) {
        //     foreach ( $_GET as $getKey => $getValue ) {
        //         $queryResult[$getKey] = $getValue;
        //     }
        // }
        // $response['query']=$queryResult;


        $queryResult = array();
        $queryResultNew = "";
        $notSavedFilters = array("sku", "count", "page", "no_filter", "url_key", "store", "service", "sort_by", "sort_dir", " ");
        if (isset($_GET)) {
            $firstCount = 1;
            foreach ($_GET as $getKey => $getValue) {
                $queryResult[$getKey] = $getValue;
                if (!in_array($getKey, $notSavedFilters) && !empty($getValue)) {
                    if ($getKey == "filter") {
                        $filterArray = explode("|", str_replace("+", " ", $getValue));
                        foreach ($filterArray as $value) {
                            $valueArray = explode("~", $value);
                            $queryResultNew .=  "('" . $valueArray[0] . "','" . $valueArray[1] . "','" . $firstCount . "'),";
                        }
                    } else {
                        $queryResultNew .=  "('" . $getKey . "','" . $getValue . "','" . $firstCount . "'),";
                    }
                }
            }
            if (!empty($queryResultNew)) {
                $queryResultNew = rtrim($queryResultNew, ", ");
                //  $this->frequentFilter($queryResultNew);
            }
        }
        $response['query'] = $queryResult;

        switch ($request->service) {
            case 'menu':
                return $this->get_menus($request->all(), $response);
                break;
            case 'banner_slider':
                return $this->get_banners($request->all(), $response);
                break;
            case 'cms_page':
                return $this->get_cms_page($request->all(), $response);
                break;
            case 'category':
                return $this->get_category_data($_GET, $response);
                break;
            case 'categoryApp':
                return $this->serveCategoryDataApp( $_GET, $response );
                // file_put_contents($file, json_encode($response));
                break;
            case 'product':
                return $this->serveProductData($_GET, $response);
                break;
            case 'shopthelook':
                return $this->shopTheLook($_GET, $response);
                //file_put_contents($file, json_encode($response));
                break;
            case 'sizechart':
                return $this->serveProductSizeChartData($_GET, $response);
                //file_put_contents($file, json_encode($response));
                break;
            case 'product_variation':
                return $this->productVariation($_GET, $response);
                //file_put_contents($file, json_encode($response));
                break;
            case 'recent_views':
                return $this->serveRecentViews($_GET, $response);
                //file_put_contents($file, json_encode($response));
                break;
            case 'pincheck':
                return $this->servePincheckData($_GET, $response);
                //file_put_contents($file, json_encode($response));
                break;
            case 'pincode':
                return $this->servePincodeData($_GET, $response);
                //file_put_contents($file, json_encode($response));
                break;
            case 'storelocator':
                return $this->serveStoreLocatorData($_GET, $response);
                break;
            case 'recent_product':
                return $this->recentProduct($_GET, $response);
                break;
            case 'cart':
                return $this->serveCartProductData($_GET, $response);
                break;
            case 'stock':
                return $this->serveStockData($_GET, $response);
                break;
            case 'stockdelta':
                return $this->serveStockDeltaData($_GET, $response);
                break;
            case 'skudata':
                return $this->serveSkuData($_GET, $response);
                break;
            case 'breadcrumsurl':
                return $this->breadcrumbUrl($_GET, $response);
                break;
            case 'wishlist':
                return $this->serveWishlistData($_GET, $response);
                break;
            
            case 'filter_image_name':
                return $this->filterImageName($_GET, $response);
                break;
            case '404':
                echo '404';
                break;
        }


        // }catch(\Exception $e){
        //     return $this->sendError( self::SOMETHING_WRONG);
        // }

    }
    /**
     * get_menus
     *
     * @param  mixed $request
     *  @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_menus($request, $response): \Illuminate\Http\JsonResponse{
        try{
            $cats = CategoryData::select('id_category', 'source_category_id', 'parent_id', 'name', 'url_key AS menu_url_key', 'position')->where([['status', 'active'],['include_in_menu', 'yes'],['store', $request['store']]])->orderBy('position', 'ASC')->get();
            if (!empty($cats)) {
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;
                $childs = array();
                foreach ($cats as &$item)
                    $childs[$item['parent_id']][] = &$item;
                unset($item);
                foreach ($cats as &$item)
                    if (isset($childs[$item['source_category_id']]))
                        $item['childs'] = $childs[$item['source_category_id']];
                unset($item);
                array_multisort(array_column($childs[0], 'position'), SORT_ASC, $childs[0]);
                $tree = $childs[0];
                $response['result'] = $tree;
                return response()->json($response);
            }
            $response['response']['success'] = 1;
            $response['response']['success_message'] = self::RECORD_NOT;
            return response()->json($response);
        
        } catch (\Exception $e) {
            $response['response']['success'] = 0;
            $response['response']['success_message'] = self::SOMETHING_WRONG;
            return response()->json($response);
        }
    }
    /**
     * get_cms_page
     *
     * @param  mixed $request
     *  @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_cms_page($request, $response): \Illuminate\Http\JsonResponse{
        try{
            $query = CmsPage::where('status', 'active');
            if (isset($request['url_key'])) {
                $query->where('url_key', $request['url_key']);
            } 
            $cms_pages=$query->where('store', $request['store'])->orderBy('position', 'ASC')->get();
            if (!empty($cms_pages)) {
                $response['count'] = count($cms_pages);
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;
                $response['result'] = $cms_pages;
                return response()->json($response);
            }
            $response['response']['success'] = 1;
            $response['response']['success_message'] = self::RECORD_NOT;
            return response()->json($response);
        } catch (\Exception $e) {
            $response['response']['success'] = 0;
            $response['response']['success_message'] = self::SOMETHING_WRONG;
            return response()->json($response);
        }

    }
    /**
     * get_banners
     *
     * @param  mixed $request
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_banners($request, $response): \Illuminate\Http\JsonResponse{
        try {
            $banners = BannerSlider::where('status', 'enable')->where('store', $request['store'])->orderBy('position', 'ASC')->get();
            if (!empty($banners)) {
                $response['count'] = count($banners);
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;
                $response['result'] = $banners;
                return response()->json($response);
            }
            $response['response']['success'] = 1;
            $response['response']['success_message'] = self::RECORD_NOT;
            return response()->json($response);
        } catch (\Exception $e) {
            $response['response']['success'] = 0;
            $response['response']['success_message'] = self::SOMETHING_WRONG;
            return response()->json($response);
        }
    }

    /**
     * get_category_data
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_category_data($queryData, $response): \Illuminate\Http\JsonResponse{
        try{
            $filter_query = '';
            $newFilterArray = [];
            if (isset($queryData['filter'])  && !empty($queryData['filter'])) {

                $filterArray = explode("|", str_replace("+", " ", $queryData['filter']));
                foreach ($filterArray as $value) {
                    $valueArray = explode("~", $value);
                    $newFilterArray[reset($valueArray)][] = end($valueArray);
                
                }

                foreach ($newFilterArray as $key => $value) {
                    $queryArray = array();
                    switch ($key) {
                        case 'selling_price':

                            // $filter_query .=" AND (selling_price >= ".min($value)." AND selling_price <= ".max($value).")";
                            foreach ($value as $newValue) {
                                $newValueArray = explode("to", str_replace(" ", "", str_replace("Rs.", "", $newValue)));
                                $queryArray[]  = "( selling_price >= " . min($newValueArray) . " AND selling_price <= " . max($newValueArray) . ")";
                            }
                            $filter_query .= " AND& (" . implode(' OR ', $queryArray) . ")";
                            break;
                        case 'size':
                            foreach ($value as $newValue) {
                                //$queryArray[]  = $key." LIKE "."'%[$newValue]%'";
                                $queryArray[]  = $key . " LIKE " . "'%$newValue%'";
                            }
                            $filter_query .= " AND& ( " . implode(' OR ', $queryArray) . ")";
                            break;
                        case 'discount':
                            foreach ($value as $newValue) {
                                //$queryArray[]  = $key." LIKE "."'%[$newValue]%'";
                                $queryArray[]  = $key . " >= " . "'$newValue'";
                            }
                            $filter_query .= " AND& ( " . implode(' OR ', $queryArray) . ")";
                            break;
                        default:
                            foreach ($value as $newValue) {
                                $queryArray[]  = $key . " = " . "'$newValue'";
                            }
                            $filter_query .= " AND& ( " . implode(' OR ', $queryArray) . ")";
                            break;
                    }
                }
            }
            // echo "<pre>";
            if ($queryData['filter']) {

                $new_filter_query = explode("AND&", $filter_query);
                //print_r($new_filter_query);
                $t2 = array_pop($new_filter_query);
                $t2key = explode(" ", $t2);
                $final_key = $t2key[2];
                //die;
                $final_filter_query = implode(' AND ', $new_filter_query);

                $filter_query = str_replace("AND&", "AND", $filter_query);
                if (!$queryData['sort_by']) {
                    $queryData['sort_by'] = 'product_position';
                }
                if (!$queryData['sort_dir']) {
                    $queryData['sort_dir'] = 'DESC';
                }

                $filter_attributes_array = array();
                // $response['result']['count'] = 0;
            } else {
                // echo 'no filter';
                // die;
                $final_key = '';
                $final_filter_query = '';
            }
            // echo "$final_filter_query";
            // echo "<br>";
            // echo "$filter_query";
            // die();

            $query = CategoryData::where('status', 'active');
            if(isset($queryData['id_category'])) {
                $query->where('id_category', $queryData['id_category']);
            }else {
                $query->where('url_key', $queryData['url_key']);
            }
            $records=$query->first();
            $response['result'] = $records->toArray();
            $response['result']['display_category'] = [];
            if ($records->parent_id> 0) {
                $parent_product = CategoryData::select('name', 'url_key')->where('status', 'active')->where('source_category_id', $records->parent_id)->first();

                $display_category = CategoryData::select('name', 'url_key')->where('status', 'active')->where('parent_id', $records->parent_id)->where('id_category', '!=', $records->id_category)->orderBy('position', 'ASC')->get();

                $response['result']['display_category'] = $display_category;
            }

            if ($records) {
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;
                // //\DB::connection('mysql')->enableQueryLog();
                $id_category = $response['result']['id_category'];
                $full_array = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                    $q->where('id_catetory', $id_category);
                })->where('url_key', '!=', $filter_query)
                    ->where('stock_status', 'in-stock')
                    ->groupBy('group_id')
                    ->orderBy('percentile_availability', 'DESC')
                    ->orderBy('newness', 'DESC')->get();

                $sqlj_new = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                    $q->where('id_catetory', $id_category);
                })->where('url_key', '!=', $filter_query)
                    ->where('stock_status', 'in-stock')
                    ->get();

                $sqlj2_new = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                    $q->where('id_catetory', $id_category);
                })->where('url_key', '!=', $final_filter_query)
                    ->where('stock_status', 'in-stock')
                    ->get();

                $full_array_cnt = count($full_array);

                $newness_array = array_slice(array($full_array), 0, round($full_array_cnt * 1 / 4, 0, PHP_ROUND_HALF_DOWN));
                $newness = implode("','", array_column($newness_array, 'group_id'));
                $restArray = array_diff_assoc(array($full_array), $newness_array);
                $newRestArray = array_column($restArray, 'percentile_availability');
                // echo 'ok';
                if (count($sqlj_new) > 0) {
                    //    echo $queryData['page'];
                    //    die;
                    $queryData['no_filter'] = (isset($queryData['no_filter'])) ? $queryData['no_filter'] : 'false';
                    if ($queryData['page'] == 1  && $queryData['no_filter'] != 'true') {
                        // echo 'ok';
                        // die;

                        $response['result']['count'] = count($full_array);
                        $new_filter = $this->filterGenrate($sqlj_new, $newFilterArray);
                        $new_filter2 = $this->filterGenrate($sqlj2_new, $newFilterArray);
                        //$new_filter2 =array();
                        foreach ($new_filter2 as $key => $value) {
                            //$merge_key=$key;
                            foreach ($value['options'] as $key2 => $value2) {
                                // echo $key2;
                                // print_r($value2['code']);
                                if ($value2['code'] === $final_key) {
                                    $merge_key = $key;
                                    $merge_lable = $value['filter_lable'];
                                    $merge_value = $value['options'];
                                    continue;
                                }
                            }
                        }
                        //  print_r($merge_value);die();
                        if ($queryData['filter']) {
                            foreach ($new_filter as $key => $value) {
                                if ($value['filter_lable'] == $merge_lable) {
                                    $new_filter[$key]['options'] = $merge_value;
                                }
                                // else{
                                //     $new_filter[$merge_key]['filter_lable']=$merge_lable;
                                //     $new_filter[$merge_key]['options']=$merge_value;
                                // }

                            }
                        }
                    } else {
                        // echo 'ok2';
                        // die;
                        $response['result']['count'] = count($full_array);
                        $new_filter = array();
                    }

                    $sorting_attributes = array(
                        0 =>
                        array(
                            'code' => 'discount',
                            'label' => 'Discount',
                        ),
                        1 =>
                        array(
                            'code' => 'price',
                            'label' => 'Price',
                        ),
                        2 =>
                        array(
                            'code' => 'product_position',
                            'label' => 'Popularity',
                        ),
                        3 =>
                        array(
                            'code' => 'newness',
                            'label' => 'Newest',
                        ),
                    );
                    // echo $filter_query;
                    // die;
                    $response['count-test'] = count(array_keys($newRestArray, 100)) . '-' . $response['result']['count'];
                    if ($queryData['sort_by'] == 'product_position' && $response['result']['count'] > 16 && count(array_keys($newRestArray, 100)) > 10) {
                        // echo "string";die();
                        // echo $id_category;

                        $query = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                            $q->where('id_catetory', $id_category);
                        });

                        $query->where('url_key', '!=', $filter_query);
                        $query->where('stock_status', 'in-stock')
                            ->whereNotIn('group_id', $newness)
                            ->groupBy('group_id')
                            ->orderBy('percentile_availability', 'DESC')
                            ->orderBy($queryData['sort_by'], $queryData['sort_dir'])
                            ->orderBy('product_position', 'DESC')
                            ->orderBy('sku', 'DESC');
                        $product_array1 = $query->get();

                        $query2 = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                            $q->where('id_catetory', $id_category);
                        });

                        $query2->where('url_key', '!=', $filter_query);
                        $query2->where('stock_status', 'in-stock');
                        $query2->whereIn('group_id', $newness);
                        $query2->groupBy('group_id');
                        $product_array2 = $query2->get();


                        if (count($product_array1) > 0 && count($product_array2) > 0) {
                            $offset = 3;
                            foreach ($product_array2 as $value) {
                                array_splice($product_array1, $offset, 0, 'more');
                                $product_array1[$offset] = $value;
                                $offset = $offset + 4;
                            }
                            $product_array = $product_array1;
                        } else {

                            $product_array = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                                $q->where('id_catetory', $id_category);
                            });

                            $query->where('url_key', '!=', $filter_query);

                            $query->where('stock_status', 'in-stock');
                            $query->groupBy('group_id');
                            $query->orderBy('percentile_availability', 'DESC');

                            if ($queryData['sort_by']) {
                                $query->orderBy($queryData['sort_by'], $queryData['sort_dir']);
                            }
                            $query->orderBy('product_position', 'DESC');
                            $query->orderBy('sku', 'DESC');
                            $product_array = $query->paginate(16);
                        }
                    } elseif ($queryData['sort_by'] == 'product_position') {

                        // echo $id_category;
                        // die;

                        $query = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                            $q->where('id_catetory', $id_category);
                        });

                        $query->where('url_key', '!=', $filter_query);
                        $query->where('stock_status', 'in-stock')
                            ->groupBy('group_id')
                            ->orderBy('percentile_availability', 'DESC');
                        if ($queryData['sort_by']) {
                            $query->orderBy($queryData['sort_by'], $queryData['sort_dir']);
                        }
                        $query->orderBy('product_position', 'DESC')
                            ->orderBy('sku', 'DESC');
                        $product_array = $query->paginate(16);
                        // count($product_array);
                        // die;
                        // return response()->json($product_array);

                    } else {
                        // echo "string1";die();

                        $query = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                            $q->where('id_catetory', $id_category);
                        });
                        if ($filter_query) {
                            $query->where('url_key', '!=', $filter_query);
                        }
                        $query->where('stock_status', 'in-stock');
                        $query->groupBy('group_id');
                        $query->orderBy('percentile_availability', 'DESC');
                        if ($queryData['sort_by']) {
                            $query->orderBy($queryData['sort_by'], $queryData['sort_dir']);
                        }

                        $query->orderBy('product_position', 'DESC');
                        $query->orderBy('sku', 'DESC');
                        $product_array = $query->paginate(16);

                    
                    }

                    // echo 'ok';
                    // die;
                    $j = 0;
                    // echo count($product_array);
                    // die;
                    //return response()->json($product_array);
                    foreach ($product_array as $value) {
                        $product_array[$j]['category'] = $this->serveCategoryname($value['source_product_id']);

                        $product_array[$j]['image'] = self::ADMIN_BASE_URL . "product/" . str_replace(' ', '', $value['sku']) . "/300/" . $value['image'];

                        $gallery_array = ProductGallery::select('position', 'image')->where('id_product', $value['id_product'])->orderBy('position', 'ASC')->get()->toArray();
                        $galleryList = array();
                        foreach ($gallery_array as $gkey => $gall_array) {
                            $galleryList[$gkey]['position'] = $gall_array['position'];
                            $galleryList[$gkey]['image'] = self::ADMIN_BASE_URL . "product/" . str_replace(' ', '', $value['sku']) . "/300/" . $gall_array['image'];
                            $galleryList[$gkey]['vedio'] = '';
                        }
                        $product_array[$j]['gallery'] = $galleryList;

                        //return response()->json( $galleryList);

                        $product_child_array = FlatCatalog::where('group_id', $value['group_id'])->where('stock_status', 'in-stock')->get();
                        $array_val = $product_child_array->toArray();
                        $keys = array_column($array_val, 'size');
                        array_multisort($keys, SORT_ASC, $array_val);

                        //return response()->json($product_child_array);
                        $product_array[$j]['variation']=$this->get_product_variation($array_val);
                        

                        if ($product_array[$j]['price'] == '' || $product_array[$j]['price'] == 0) {
                            $product_array[$j]['price'] = $array_val[0]['price'];
                            $product_array[$j]['selling_price'] = $array_val[0]['selling_price'];
                        }
                        $j++;
                    }
                    if ($response['result']['meta_keyword'] == '') {
                        $response['result']['meta_keyword'] = 'Online Shopping for Women,  Aurelia';
                        if ($queryData['store'] == 1) {
                            $response['result']['meta_keyword'] = 'Online Shopping,  Ketch';
                        }
                    }
                    $response['result']['parent_name'] = ( $parent_product->name ?  $parent_product->name : '');
                    $response['result']['parent_url_key'] = ( $parent_product->url_key ? $parent_product->url_key : '');
                    //$response['result']['products'] =
                    $response['result']['products'] = $product_array->getCollection();
                    $response['result']['filters'] =  $new_filter;
                    $response['result']['sort'] = $sorting_attributes;
                } else {

                    $response['response']['success'] = 0;
                    $response['response']['success_message'] = '';
                    $response['response']['error'] = 1;
                    $response['response']['error_message'] = self::RECORD_NOT;
                }
            } else {
                $response['response']['success'] = 0;
                $response['response']['success_message'] = '';
                $response['response']['error'] = 1;
                $response['response']['error_message'] = self::RECORD_NOT;
            }

            return  response()->json($response);
        } catch (\Exception $e) {
            $response['response']['success'] = 0;
            $response['response']['success_message'] = self::SOMETHING_WRONG;
            return response()->json($response);
        }
    }
    
    /**
     * serveCategoryDataApp
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function serveCategoryDataApp($queryData, $response): \Illuminate\Http\JsonResponse{
        try{
            $filter_query = '';
            $newFilterArray = [];
            if (isset($queryData['filter'])  && !empty($queryData['filter'])) {

                $filterArray = explode("|", str_replace("+", " ", $queryData['filter']));
                foreach ($filterArray as $value) {
                    $valueArray = explode("~", $value);
                    $newFilterArray[reset($valueArray)][] = end($valueArray);
                
                }

                foreach ($newFilterArray as $key => $value) {
                    $queryArray = array();
                    switch ($key) {
                        case 'selling_price':

                            // $filter_query .=" AND (selling_price >= ".min($value)." AND selling_price <= ".max($value).")";
                            foreach ($value as $newValue) {
                                $newValueArray = explode("to", str_replace(" ", "", str_replace("Rs.", "", $newValue)));
                                $queryArray[]  = "( selling_price >= " . min($newValueArray) . " AND selling_price <= " . max($newValueArray) . ")";
                            }
                            $filter_query .= " AND& (" . implode(' OR ', $queryArray) . ")";
                            break;
                        case 'size':
                            foreach ($value as $newValue) {
                                //$queryArray[]  = $key." LIKE "."'%[$newValue]%'";
                                $queryArray[]  = $key . " LIKE " . "'%$newValue%'";
                            }
                            $filter_query .= " AND& ( " . implode(' OR ', $queryArray) . ")";
                            break;
                        case 'discount':
                            foreach ($value as $newValue) {
                                //$queryArray[]  = $key." LIKE "."'%[$newValue]%'";
                                $queryArray[]  = $key . " >= " . "'$newValue'";
                            }
                            $filter_query .= " AND& ( " . implode(' OR ', $queryArray) . ")";
                            break;
                        default:
                            foreach ($value as $newValue) {
                                $queryArray[]  = $key . " = " . "'$newValue'";
                            }
                            $filter_query .= " AND& ( " . implode(' OR ', $queryArray) . ")";
                            break;
                    }
                }
            }
            // echo "<pre>";
            if ($queryData['filter']) {

                $new_filter_query = explode("AND&", $filter_query);
                //print_r($new_filter_query);
                $t2 = array_pop($new_filter_query);
                $t2key = explode(" ", $t2);
                $final_key = $t2key[2];
                //die;
                $final_filter_query = implode(' AND ', $new_filter_query);

                $filter_query = str_replace("AND&", "AND", $filter_query);
                if (!$queryData['sort_by']) {
                    $queryData['sort_by'] = 'product_position';
                }
                if (!$queryData['sort_dir']) {
                    $queryData['sort_dir'] = 'DESC';
                }

                $filter_attributes_array = array();
                // $response['result']['count'] = 0;
            } else {
                // echo 'no filter';
                // die;
                $final_key = '';
                $final_filter_query = '';
            }
            // echo "$final_filter_query";
            // echo "<br>";
            // echo "$filter_query";
            // die();
            $query = CategoryData::where('status', 'active');
            if(isset($queryData['id_category'])) {
                $query->where('id_category', $queryData['id_category']);
            }else {
                $query->where('url_key', $queryData['url_key']);
            }
            $records=$query->first();
            

            $response['result'] = $records->toArray();

            // print_r($records->toArray());
            // die;
            $response['result']['display_category'] = [];
            if ($records->parent_id > 0) {
                $parent_product = CategoryData::select('name', 'url_key')->where('status', 'active')->where('source_category_id', $records->parent_id)->first();

                $display_category = CategoryData::select('name', 'url_key')->where('status', 'active')->where('parent_id', $records->parent_id)->where('id_category', '!=', $records->id_category)->orderBy('position', 'ASC')->get();


                $response['result']['display_category'] = $display_category;
            }

            if ($records) {
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;

            
                $id_category = $response['result']['id_category'];
                $full_array = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                    $q->where('id_catetory', $id_category);
                })->where('url_key', '!=', $filter_query)
                    ->where('stock_status', 'in-stock')
                    ->groupBy('group_id')
                    ->orderBy('percentile_availability', 'DESC')
                    ->orderBy('newness', 'DESC')->get();

                $sqlj_new = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                    $q->where('id_catetory', $id_category);
                })->where('url_key', '!=', $filter_query)
                    ->where('stock_status', 'in-stock')
                    ->get();

                $sqlj2_new = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                    $q->where('id_catetory', $id_category);
                })->where('url_key', '!=', $final_filter_query)
                    ->where('stock_status', 'in-stock')
                    ->get();

                $full_array_cnt = count($full_array);

                $newness_array = array_slice(array($full_array), 0, round($full_array_cnt * 1 / 4, 0, PHP_ROUND_HALF_DOWN));
                $newness = implode("','", array_column($newness_array, 'group_id'));
                $restArray = array_diff_assoc(array($full_array), $newness_array);
                $newRestArray = array_column($restArray, 'percentile_availability');
                // echo 'ok';
                if (count($sqlj_new) > 0) {
                    //    echo $queryData['page'];
                    //    die;
                    $queryData['no_filter'] = (isset($queryData['no_filter'])) ? $queryData['no_filter'] : 'false';
                    if ($queryData['page'] == 1  && $queryData['no_filter'] != 'true') {
                        // echo 'ok';
                        // die;

                        $response['result']['count'] = count($full_array);
                        $new_filter = $this->filter_Generate_new($sqlj_new, $newFilterArray);
                        $new_filter2 = $this->filter_Generate_new($sqlj2_new, $newFilterArray);
                        //$new_filter2 =array();
                        foreach ($new_filter2 as $key => $value) {
                            //$merge_key=$key;
                            foreach ($value['options'] as $key2 => $value2) {
                                // echo $key2;
                                // print_r($value2['code']);
                                if ($value2['code'] === $final_key) {
                                    $merge_key = $key;
                                    $merge_lable = $value['filter_lable'];
                                    $merge_value = $value['options'];
                                    continue;
                                }
                            }
                        }
                        //  print_r($merge_value);die();
                        if ($queryData['filter']) {
                            foreach ($new_filter as $key => $value) {
                                if ($value['filter_lable'] == $merge_lable) {
                                    $new_filter[$key]['options'] = $merge_value;
                                }
                                // else{
                                //     $new_filter[$merge_key]['filter_lable']=$merge_lable;
                                //     $new_filter[$merge_key]['options']=$merge_value;
                                // }

                            }
                        }
                    } else {
                        // echo 'ok2';
                        // die;
                        $response['result']['count'] = count($full_array);
                        $new_filter = array();
                    }

                    $sorting_attributes = array(
                        0 =>
                        array(
                            'code' => 'discount',
                            'label' => 'Discount',
                        ),
                        1 =>
                        array(
                            'code' => 'price',
                            'label' => 'Price',
                        ),
                        2 =>
                        array(
                            'code' => 'product_position',
                            'label' => 'Popularity',
                        ),
                        3 =>
                        array(
                            'code' => 'newness',
                            'label' => 'Newest',
                        ),
                    );
                    // echo $filter_query;
                    // die;
                    $response['count-test'] = count(array_keys($newRestArray, 100)) . '-' . $response['result']['count'];
                    if ($queryData['sort_by'] == 'product_position' && $response['result']['count'] > 16 && count(array_keys($newRestArray, 100)) > 10) {
                        // echo "string";die();
                        // echo $id_category;

                        $query = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                            $q->where('id_catetory', $id_category);
                        });

                        $query->where('url_key', '!=', $filter_query);
                        $query->where('stock_status', 'in-stock')
                            ->whereNotIn('group_id', $newness)
                            ->groupBy('group_id')
                            ->orderBy('percentile_availability', 'DESC')
                            ->orderBy($queryData['sort_by'], $queryData['sort_dir'])
                            ->orderBy('product_position', 'DESC')
                            ->orderBy('sku', 'DESC');
                        $product_array1 = $query->get();

                        $query2 = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                            $q->where('id_catetory', $id_category);
                        });

                        $query2->where('url_key', '!=', $filter_query);
                        $query2->where('stock_status', 'in-stock');
                        $query2->whereIn('group_id', $newness);
                        $query2->groupBy('group_id');
                        $product_array2 = $query2->get();


                        if (count($product_array1) > 0 && count($product_array2) > 0) {
                            $offset = 3;
                            foreach ($product_array2 as $value) {
                                array_splice($product_array1, $offset, 0, 'more');
                                $product_array1[$offset] = $value;
                                $offset = $offset + 4;
                            }
                            $product_array = $product_array1;
                        } else {

                            $product_array = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                                $q->where('id_catetory', $id_category);
                            });

                            $query->where('url_key', '!=', $filter_query);

                            $query->where('stock_status', 'in-stock');
                            $query->groupBy('group_id');
                            $query->orderBy('percentile_availability', 'DESC');

                            if ($queryData['sort_by']) {
                                $query->orderBy($queryData['sort_by'], $queryData['sort_dir']);
                            }
                            $query->orderBy('product_position', 'DESC');
                            $query->orderBy('sku', 'DESC');
                            $product_array = $query->paginate(16);
                        }
                    } elseif ($queryData['sort_by'] == 'product_position') {

                        // echo $id_category;
                        // die;

                        $query = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                            $q->where('id_catetory', $id_category);
                        });

                        $query->where('url_key', '!=', $filter_query);
                        $query->where('stock_status', 'in-stock')
                            ->groupBy('group_id')
                            ->orderBy('percentile_availability', 'DESC');
                        if ($queryData['sort_by']) {
                            $query->orderBy($queryData['sort_by'], $queryData['sort_dir']);
                        }
                        $query->orderBy('product_position', 'DESC')
                            ->orderBy('sku', 'DESC');
                        $product_array = $query->paginate(16);
                        // count($product_array);
                        // die;
                        // return response()->json($product_array);

                    } else {
                        // echo "string1";die();

                        $query = FlatCatalog::whereHas('product_category', function ($q) use ($id_category) {
                            $q->where('id_catetory', $id_category);
                        });
                        if ($filter_query) {
                            $query->where('url_key', '!=', $filter_query);
                        }
                        $query->where('stock_status', 'in-stock');
                        $query->groupBy('group_id');
                        $query->orderBy('percentile_availability', 'DESC');
                        if ($queryData['sort_by']) {
                            $query->orderBy($queryData['sort_by'], $queryData['sort_dir']);
                        }

                        $query->orderBy('product_position', 'DESC');
                        $query->orderBy('sku', 'DESC');
                        $product_array = $query->paginate(16);

                    
                    }

                    // echo 'ok';
                    // die;
                    $j = 0;
                    // echo count($product_array);
                    // die;
                    //return response()->json($product_array);
                    foreach ($product_array as $value) {
                        $product_array[$j]['category'] = $this->serveCategoryname($value['source_product_id']);

                        $product_array[$j]['image'] = self::ADMIN_BASE_URL . "product/" . str_replace(' ', '', $value['sku']) . "/300/" . $value['image'];

                        $gallery_array = ProductGallery::select('position', 'image')->where('id_product', $value['id_product'])->orderBy('position', 'ASC')->get()->toArray();
                        $galleryList = array();
                        foreach ($gallery_array as $gkey => $gall_array) {
                            $galleryList[$gkey]['position'] = $gall_array['position'];
                            $galleryList[$gkey]['image'] = self::ADMIN_BASE_URL . "product/" . str_replace(' ', '', $value['sku']) . "/300/" . $gall_array['image'];
                            $galleryList[$gkey]['vedio'] = '';
                        }
                        $product_array[$j]['gallery'] = $galleryList;

                        //return response()->json( $galleryList);

                        $product_child_array = FlatCatalog::select('id_product', 'sku', 'configrable_atribute_value', 'price', 'selling_price', 'quantity', 'stock_status', 'image', 'size', 'store')->where('group_id', $value['group_id'])->where('stock_status', 'in-stock')->get();
                        $array_val = $product_child_array->toArray();
                        $keys = array_column($array_val, 'size');
                        array_multisort($keys, SORT_ASC, $array_val);

                        //return response()->json($product_child_array);
                        $product_array[$j]['variation']=$this->get_product_variation($array_val);
                        

                        if ($product_array[$j]['price'] == '' || $product_array[$j]['price'] == 0) {
                            $product_array[$j]['price'] = $array_val[0]['price'];
                            $product_array[$j]['selling_price'] = $array_val[0]['selling_price'];
                        }
                        $j++;
                    }
                    if ($response['result']['meta_keyword'] == '') {
                        $response['result']['meta_keyword'] = 'Online Shopping for Women,  Aurelia';
                        if ($queryData['store'] == 1) {
                            $response['result']['meta_keyword'] = 'Online Shopping,  Ketch';
                        }
                    }
                    $response['result']['parent_name'] = ($parent_product->name ?  $parent_product->name : '');
                    $response['result']['parent_url_key'] = ($parent_product->url_key ? $parent_product->url_key : '');
                    //$response['result']['products'] =
                    $response['result']['products'] = $product_array->getCollection();
                    $response['result']['filters'] =  $new_filter;
                    $response['result']['sort'] = $sorting_attributes;
                } else {

                    $response['response']['success'] = 0;
                    $response['response']['success_message'] = '';
                    $response['response']['error'] = 1;
                    $response['response']['error_message'] = self::RECORD_NOT;
                }
            } else {
                $response['response']['success'] = 0;
                $response['response']['success_message'] = '';
                $response['response']['error'] = 1;
                $response['response']['error_message'] = self::RECORD_NOT;
            }

            return  response()->json($response);
        } catch (\Exception $e) {
            $response['response']['success'] = 0;
            $response['response']['success_message'] = self::SOMETHING_WRONG;
            return response()->json($response);
        }

    }
    /**
     * serveProductData
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function serveProductData($queryData, $response): \Illuminate\Http\JsonResponse{

        try {

            $query = FlatCatalog::where('status', 'active')->where('visibility', 'show');
            if (isset($queryData['id_product'])) {
                $query->where('id_product', $queryData['id_product']);
            } else {
                $query->where('url_key', $queryData['url_key']);
            }
            $product_array = $query->where('store', $queryData['store'])->get();
            $response['result'] = $product_array[0];

            if (count($product_array) > 0) {

                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;

                $product_child_array = FlatCatalog::select('id_product', 'sku', 'configrable_atribute_value', 'price', 'selling_price', 'quantity', 'stock_status', 'image', 'size')->where('group_id', $response['result']['group_id'])->where('store', $queryData['store'])->get();
                $response['result']['category'] = $this->serveBreadCrumDeatils($response['result']['source_product_id'], $queryData['store']);
                //return response()->json($product_child_array);
                // echo 'ok';
                // die;
                $response['result']['image'] = self::ADMIN_BASE_URL . "product/" . str_replace(' ', '', $response['result']['sku']) . "/660/" . $response['result']['image'];
                //$fdgdr = json_decode(, true);
                $response['result']['breadcrumb'] = json_decode($product_array[0]['breadcrumb']);
                //print_r($product_child_array);die();
                $similar_keys = array('gender', 'sub_category', 'type', 'sleeve_length', 'pattern', 'shape', 'brand', 'color_family', 'fit');
                $team_up_list = array('gender', 'fit', 'brand', 'color_family');
                foreach ($similar_keys as  $svalue) {
                    if (isset($response['result'][$svalue])) {
                        $similar_attribute[$svalue] = $response['result'][$svalue];
                    }
                }

                foreach ($team_up_list as  $tulvalue) {
                    if (isset($response['result'][$tulvalue])) {
                        $team_up_attribute[$tulvalue] = $response['result'][$tulvalue];
                    }
                }
                // $response['result']['similar_product_list']=$this->serveCategoryProducts($response['result']['source_product_id'],$response['result']['group_id'],$similar_attribute);
                // echo $response['result']['id_product'];
                // die;
                $gallery_array = ProductGallery::select('position', 'image')->where('id_product', $response['result']['id_product'])->orderBY('position', 'ASC')->get();
                $galleryList = array();
                foreach ($gallery_array as $gkey => $gall_array) {
                    $galleryList[$gkey]['position'] = $gall_array['position'];
                    $galleryList[$gkey]['image'] = self::ADMIN_BASE_URL . "product/" . str_replace(' ', '', $response['result']['sku']) . "/660/" . $gall_array['image'];
                    $galleryList[$gkey]['vedio'] = '';
                }
                $response['result']['gallery'] = $galleryList;
            
                $response['result']['variation']=$this->get_product_variation($product_child_array);
                

            
                $attributes=ProductAttributeValue::withAndWhereHas('get_attribute',function($q){
                    $q->where('is_visible','yes');
                })->select('value','id_product_attribute')->where('id_product',$response['result']['id_product'])->where('value', '!=', '')->where('value', '!=', 'No')->get()->toArray();
                
                $visible_attributes=[];
                foreach($attributes as $list){
                    $data['code']=$list['get_attribute']['code'];
                    $data['label']=$list['get_attribute']['label'];
                    $data['value']=$list['value'];
                    $visible_attributes[]=$data;

                }
            
                $oth_attributes=ProductAttributeValue::withAndWhereHas('get_attribute',function($q){
                    $q->where('is_visible','yes')->where('is_in_filter', 'no')->where('is_in_search', 'no')->where('is_in_sort', 'no');
        
                })->select('value','id_product_attribute')->where('id_product',$response['result']['id_product'])->where('value', '!=', '')->where('value', '!=', 'No')->get()->toArray();
                                

                    $other_attributes=[];
                    foreach($oth_attributes as $list){
                        $data1['code']=$list['get_attribute']['code'];
                        $data1['label']=$list['get_attribute']['label'];
                        $data1['value']=$list['value'];
                        $other_attributes[]=$data1;

                    }

                

                    $other_attributes = json_decode(json_encode($other_attributes), true);

                foreach ($other_attributes as $value) {

                    if ($value['code'] == 'size_chart') {
                        $response['result']['size_chart']['title'] = $value['value'];
                        $response['result']['size_chart']['key'] = $value['value']; //strtolower(preg_replace("/[^0-9a-zA-Z]/", "_", $value['value']));
                    }
                    // echo 'ok';
                    // die;
                    if ($value['code'] == 'team_up') {
                        if ($value['value'] != '') {
                            $response['result']['team_up_products'] = $this->serveProductGrid($value['value'], $queryData['store']);
                        }
                    }
                    if ($value['code'] == 'you_may_also_like') {
                        if ($value['value'] != '') {
                            $response['result']['you_may_also_like'] = $this->serveProductGrid($value['value'], $queryData['store']);
                        }
                    }
                    // echo 'ok';
                    // die;
                    if ($value['code'] == 'top_sellers') {
                        if ($value['value'] != '') {
                            $response['result']['top_sellers'] = $this->serveProductGrid($value['value'], $queryData['store']);
                        }
                    }
                    if ($value['code'] == 'similar_products') {
                        if ($value['value'] != '') {
                            $response['result']['similar_products'] = $this->serveProductGrid($value['value'], $queryData['store']);
                        }
                    }
                }




                if ($response['result']['price'] == '' || $response['result']['price'] == 0) {
                    $response['result']['price'] = $response['result']['variation'][0]['price'];
                    $response['result']['selling_price'] = $response['result']['variation'][0]['selling_price'];
                }


                $response['result']['name'] = $response['result']['description'];
                $response['result']['description'] = $response['result']['product_details'];
                $response['result']['visible_attributes'] = $visible_attributes;
            } else {
                $response['response']['success'] = 0;
                $response['response']['success_message'] = '';
                $response['response']['error'] = 1;
                $response['response']['error_message'] = self::RECORD_NOT;
            }
        } catch (\Exception $ex) {
            $response['response']['success'] = 0;
            $response['response']['success_message'] = '';
            $response['response']['error'] = 1;
            $response['response']['error_message'] = json_encode($ex);
        }
        return response()->json($response);
    }

    /**
     * shopTheLook
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function shopTheLook($queryData, $response): \Illuminate\Http\JsonResponse{
        try {
            if (isset($queryData['brand']) && $queryData['brand'] != '') {
                $product_arrayShopTheLook = ShopTheLook::where('brand_name', $queryData['brand'])->first();
                $skuList = [];
                if ($product_arrayShopTheLook) {
                    $skuList = $product_arrayShopTheLook->sku;
                }
                $product_array = FlatCatalog::whereIn('sku', explode(',', $skuList))->where([['status', 'active'], ['visibility', 'show'], ['store', $queryData['store']]])->get();

                if (count($product_array) > 0) {
                    $response['response']['success'] = 1;
                    $response['response']['success_message'] = self::SUCCESS_MSG;
                    foreach ($product_array as $skuKey => $skuvalue) {
                        $product_child_array = FlatCatalog::where([['group_id', $skuvalue['group_id']], ['store', $skuvalue['store']]])->get()->toArray();

                        array_multisort(array_column($product_child_array, 'configrable_atribute_value'), SORT_ASC, $product_child_array);

                        $product_array[$skuKey]['category'] = $this->serveCategoryname($skuvalue['id_product']);
                        $product_array[$skuKey]['image'] = self::ADMIN_BASE_URL . "product/" . str_replace(' ', '', $skuvalue['sku']) . "/660/" . $skuvalue['image'];
                        //print_r($product_child_array);die();
                        $similar_keys = array('gender', 'sub_category', 'type', 'sleeve_length', 'pattern', 'shape', 'brand', 'color_family', 'fit');
                        $team_up_list = array('gender', 'fit', 'brand', 'color_family');
                        foreach ($similar_keys as  $svalue) {
                            if (isset($skuvalue[$svalue])) {
                                $similar_attribute[$svalue] = $skuvalue[$svalue];
                            }
                        }

                        foreach ($team_up_list as  $tulvalue) {
                            if (isset($skuvalue[$tulvalue])) {
                                $team_up_attribute[$tulvalue] = $skuvalue[$tulvalue];
                            }
                        }
                        $product_array[$skuKey]['variation']=$this->get_product_variation($product_child_array);
                    
                        
                        $gallery_array = ProductGallery::select('position', 'image')->where('id_product', $skuvalue['id_product'])->orderBy('position', 'ASC')->get();

                        $galleryList = array();
                        foreach ($gallery_array as $gkey => $gall_array) {
                            $galleryList[$gkey]['position'] = $gall_array['position'];
                            $galleryList[$gkey]['image'] = self::ADMIN_BASE_URL . "product/" . str_replace(' ', '', $skuvalue['sku']) . "/660/" . $gall_array['image'];
                            $galleryList[$gkey]['vedio'] = '';
                        }

                        $product_array[$skuKey]['gallery'] = $galleryList;
                    }

                    $response['result'] = $product_array;
                }
            }
        } catch (\Exception $ex) {
            $response['response']['success'] = 0;
            $response['response']['success_message'] = '';
            $response['response']['error'] = 1;
            $response['response']['error_message'] = json_encode($ex);
        }
        return response()->json($response);
    }    
    /**
     * serveProductSizeChartData
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function serveProductSizeChartData($queryData, $response): \Illuminate\Http\JsonResponse{
        try {
            $product_array = array();
            $admin_base_url = self::ADMIN_BASE_URL;

            $query = FlatCatalog::select(
                'id_product',
                'source_product_id',
                'parent_id',
                'sku',
                'name',
                'price',
                'selling_price',
                'discount',
                'stock_status',
                'quantity',
                'status',
                'visibility',
                'description',
                'visibility',
                'description',
                DB::raw("CONCAT('$admin_base_url','product/',sku,'/300/','image') as image"),
                'configrable_atribute_code',
                'configrable_atribute_value',
                'has_child',
                'url_key',
                'meta_title',
                'meta_description',
                'meta_keyword',
                'fit',
                'product_position',
                'product_type',
                'voucher_type',
                'for_complaints',
                'manufactured_packed_for',
                'marked_by',
                'name_of_manufacturer',
                'place_of_manufacturer',
                'registered_office_address',
                'quantity_show',
                'team_up',
                'size_chart',
                'drm_bestseller',
                'google_product_category',
                'app_visibility',
                'is_new',
                'group_id',
                'brand',
                'size',
                'color_family',
                'sub_category',
                'store',
                'created_at',
                'updated_at'
            );
            $query->where([['status', 'active'], ['visibility', 'show']]);
            if (isset($queryData['id_product']) && $queryData['id_product'] != '') {
                $query->where('id_product', $queryData['id_product']);
            } else {
                $query->where('url_key', $queryData['url_key']);
            }
            $product_array = $query->first();

            // print_r($product_array);
            // die;

            if ($product_array) {
                // echo 'ok';
                // die;
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;

                //$product_array = $product_array[0];

                $product_child_array = FlatCatalog::select('id_product', 'sku', 'configrable_atribute_value', 'price', 'selling_price', 'quantity', 'stock_status', 'image', 'size')->where([['group_id', $product_array->group_id], ['store', $product_array->store]])->get()->toArray();
                // print_r($product_child_array);
                // die;
                if (count($product_child_array) >= 1) {

                    $newProductList = array();
                    foreach ($product_child_array as $key => $value) {
                        $product_child_array[$key]['configrable_atribute_value'] = $value['size'];
                        $product_child_array[$key]['image'] = self::ADMIN_BASE_URL . "product/" . str_replace(' ', '', $value['sku']) . "/660/" . $value['image'];

                        $chartArray = ProductAttribute::select('id_product_attribute')->where('is_in_sizechart', 'yes')->get()->toArray();

                        $product_child_chart_array = ProductAttributeValue::whereHas('get_attribute', function ($q) {
                            $q->where('is_in_sizechart', 'yes');
                        })->where('id_product', $value['id_product'])->get();;

                        $newProductList[$this->getSizeNumber($value['size'])] = $value;
                        $newProductList[$this->getSizeNumber($value['size'])]['size_chart'] = $product_child_chart_array;
                    }

                    $updateProductList = array();
                    $cpIndex = 0;
                    ksort($newProductList);
                    foreach ($newProductList as $newProductLis) {

                        $updateProductList[$cpIndex] = $newProductLis;
                        $cpIndex++;
                    }
                }
                $response['result'] = $updateProductList;
            } else {
                $response['response']['error'] = 1;
                $response['response']['error'] = self::RECORD_NOT;
            }
        } catch (\Exception $ex) {
            $response['response']['success'] = 0;
            $response['response']['success_message'] = '';
            $response['response']['error'] = 1;
            $response['response']['error_message'] = json_encode($ex);
        }
        return response()->json($response);
    }    
    /**
     * getSizeNumber
     *
     * @param  mixed $sizeLists
     * @return void
     */
    public function getSizeNumber($sizeLists){
        $dt = 0;
        //print_r($sizeLists); exit;
        switch ($sizeLists) {
            case "FS":
                $dt = 0;
                break;
            case "XS":
                $dt = 1;
                break;
            case "S":
                $dt = 3;
                break;
            case "M":
                $dt = 4;
                break;
            case "L":
                $dt = 5;
                break;
            case "XL":
                $dt = 6;
                break;
            case "XXL":
                $dt = 7;
                break;
            case "6":
                $dt = 8;
                break;
            case "8":
                $dt = 9;
                break;
            case "10":
                $dt = 10;
                break;
            case "12":
                $dt = 11;
                break;
            case "14":
                $dt = 12;
                break;
            case "16":
                $dt = 13;
                break;
            case "18":
                $dt = 14;
                break;
            case "WP":
                $dt = 15;
                break;
            case "WS":
                $dt = 16;
                break;
            case "WM":
                $dt = 17;
                break;
            case "WL":
                $dt = 18;
                break;
            case "WG":
                $dt = 19;
                break;
            case "WVG":
                $dt = 20;
                break;
            default:
                $dt = $sizeLists;
        }

        return $dt;
    }
    /**
     * servePincheckData
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function servePincheckData($queryData, $response): \Illuminate\Http\JsonResponse{
        try {

            if ($queryData['pincode']) {

                $pincodeCheckResult = CheckOutPinCode::select('prepaid', 'cod')->where('pincode', $queryData['pincode'])->get()->toArray();
                $resultToken = ShipRocketToken::orderBy('id', 'DESC')->first();

                if ($resultToken) {
                    $currentDate = Date('Y-m-d H:i:s');
                    $savedDate = $resultToken->updated_at;
                    $hourdiff = round((strtotime($currentDate) - strtotime($savedDate)) / 3600, 1);

                    if ($hourdiff < 3) {
                        $pincodeToken = $resultToken->token;
                    } else {
                        $tokenShipRocket = $this->fetchToken();
                        $tokenShipRocketArray = json_decode($tokenShipRocket);
                        if (isset($tokenShipRocketArray->token)) {
                            $pincodeToken = $tokenShipRocketArray->token;
                            ShipRocketToken::where('id', $resultToken->id)->update(["token" => $pincodeToken]);
                        } else {
                            $response['response']['error'] = "1";
                            $response['response']['error_message'] = '<span class="error">Token Error, Invalid Login</span>';
                            return response()->json($response);
                        }
                    }
                } else {
                    $response['response']['error'] = "1";
                    $response['response']['error_message'] = self::RECORD_NOT;
                    return response()->json($response);
                }
                //pickup service
                $shiprocketServiceablePincode = $this->serviceablePincode($pincodeToken, $queryData['pincode'], 0);
                if ($this->isJson($shiprocketServiceablePincode)) {
                    $shiprocketServiceablePincodeArray = json_decode($shiprocketServiceablePincode);
                    if (isset($shiprocketServiceablePincodeArray->status) && $shiprocketServiceablePincodeArray->status == 200 && $shiprocketServiceablePincodeArray->data->available_courier_companies[0]) {
                        $tatNew = $shiprocketServiceablePincodeArray->data->available_courier_companies[0]->estimated_delivery_days;
                        $tatNew = ($tatNew + 1) . " To " . ($tatNew + 3) . " Days";
                    } else {
                        $response['response']['error'] = "1";
                        $response['response']['error_message'] = self::DELIVERY_NOT_SERVICEABLE;
                        return response()->json($response);
                    }
                } else {
                    $response['response']['error'] = "1";
                    $response['response']['error_message'] = self::SOMETHING_WRONG_PIN_NOT;
                    return response()->json($response);
                }

                $response['response']['return_service'] = false;
                //check for return pickup service availability
                $shiprocketReturnServiceablePincode = $this->serviceablePincode($pincodeToken, $queryData['pincode'], 1);
                if ($this->isJson($shiprocketReturnServiceablePincode)) {
                    $shiprocketReturnServiceablePincodeArray = json_decode($shiprocketReturnServiceablePincode);
                    if (isset($shiprocketReturnServiceablePincodeArray->status) && $shiprocketReturnServiceablePincodeArray->status == 200 &&    $shiprocketReturnServiceablePincodeArray->data->available_courier_companies[0]) {
                        $returnTatNew = $shiprocketReturnServiceablePincodeArray->data->available_courier_companies[0]->estimated_delivery_days;

                        $response['response']['return_service'] = true;
                        if (count($pincodeCheckResult) > 0) {

                            if ($pincodeCheckResult[0]['cod'] != 'Yes' && $pincodeCheckResult[0]['prepaid'] != 'Yes') {
                                $responseError['response']['error'] = "1";
                                $responseError['response']['error_message'] = self::DELIVERY_NOT_SERVICEABLE;
                                return response()->json($responseError);
                                // return $responseError; 
                            } else if ($pincodeCheckResult[0]['cod'] != 'Yes') {
                                $isCod = "No";
                            } else if ($pincodeCheckResult[0]['prepaid'] != 'Yes') {
                                $responseError['response']['error'] = "1";
                                $responseError['response']['error_message'] = self::PREPAID_NOT_ALLOWED;
                                return response()->json($responseError);
                            }
                        }
                    } else {
                        $response['response']['error'] = "1";
                        $response['response']['error_message'] = self::PICKUP_NOT_SERVICEABLE;
                        return response()->json($response);
                        //return $response;
                    }
                } else {
                    $response['response']['error'] = "1";
                    $response['response']['error_message'] = self::SOMETHING_WRONG_PIN_NOT;
                    return response()->json($response);
                }

                $shiprocketFetchLocality = $this->fetchLocality($queryData['pincode']);
                if ($this->isJson($shiprocketFetchLocality)) {

                    $shiprocketFetchLocalityArray = json_decode($shiprocketFetchLocality);

                    if (isset($shiprocketFetchLocalityArray->success) && $shiprocketFetchLocalityArray->success) {

                        $response['response']['success'] = "1";
                        $response['response']['success_message'] =
                            '<span class="span2">Your order will be delivered in ' . $tatNew . '. Please add to cart</span>
                        <span class="span1">100% Originals</span>
                        <span class="span2">Free Delivery on order above RS. 799</span>';
                        if (!isset($isCod)) {
                            // echo $isCod;
                            // die;
                            $response['response']['success_message'] .= self::COD_AVAILABLE;
                        }
                        $states = self::EXTRA_DELIVERY_CHARGE_LOCALITY;
                        $order_state = $shiprocketFetchLocalityArray->postcode_details->state;
                        if (in_array($order_state, $states)) {
                            $response['response']['result']["shipping"] = "75";
                        } else {
                            $response['response']['result']["shipping"] = "50";
                        }
                        // print_r($states);
                        // die;

                        $response['response']['success_message'] = self::EASY_RETURN_EXCHANGE;
                        $response['response']['success_message_sort'] = $tatNew;
                        $response['response']['result']["pincode"] = $queryData['pincode'];
                        $response['response']['result']["cod"] = isset($isCod) ? "No" : "Yes";
                        // echo 'ok1';
                        // die;
                        $response['response']['result']['city'][$shiprocketFetchLocalityArray->postcode_details->city] = $shiprocketFetchLocalityArray->postcode_details->city;
                        $response['response']['result']['area'] = array_combine($shiprocketFetchLocalityArray->postcode_details->locality, $shiprocketFetchLocalityArray->postcode_details->locality);
                        // print_r($response['result']['area']);
                        // die;
                        $response['response']['result']["state"] = $shiprocketFetchLocalityArray->postcode_details->state;
                        $response['response']['result']["state_code"] = $shiprocketFetchLocalityArray->postcode_details->state_code;
                        $response['response']['result']["delivery"] = $tatNew;
                        $response['response']['result']["type"] = null;


                        return response()->json($response);
                    } else {
                        $response['response']['error'] = "1";
                        $response['response']['error_message'] = self::SERVICE_NOT_AVAILABLE;
                        return response()->json($response);
                    }
                } else {
                    $response['response']['error'] = "1";
                    $response['response']['error_message'] = self::SOMETHING_WRONG_PIN_NOT;
                    return response()->json($response);
                }
            } else {
                $response['response']['error'] = "1";
                $response['response']['error_message'] = self::VALID_PIN_CODE;
            }
        } catch (\Exception $ex) {
            $response['response']['error'] = "1";
            $response['response']['error_message'] = json_encode($ex);
        }
        return response()->json($response);
    }    
    /**
     * servePincodeData
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function servePincodeData($queryData, $response): \Illuminate\Http\JsonResponse{
        try {
            if ($queryData['pincode']) {

                $result = CheckOutPinCode::where('pincode', $queryData['pincode'])->first();

                if ($result) {

                    $result = $result->toArray();
                    $response['response']['success'] = "1";
                    $response['response']['result'] = $result;
                } else {
                    $response['response']['error'] = "1";
                    $response['response']['error_message'] = self::VALID_PIN_CODE;
                }
            } else {
                $response['response']['error'] = "1";
                $response['response']['error_message'] = self::PIN_CODE_REQUIRED;
            }
        } catch (\Exception $ex) {
            $response['response']['error'] = "1";
            $response['response']['error_message'] = json_encode($ex);
        }
        return response()->json($response);
    }
    /**
     * serveStoreLocatorData
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function serveStoreLocatorData($queryData, $response): \Illuminate\Http\JsonResponse{
        //echo "string";die();
        try {

            $result = StoreLocator::where([['is_active', 1], ['stores_type', 'web'], ['category_id', $queryData['store']]])->get();

            if (count($result) > 0) {
                $result = $result->toArray();
                $response['count'] = count($result);

                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;
                $response['result'] = $result;
            } else {
                $response['response']['error'] = 1;
                $response['response']['error'] = self::RECORD_NOT;
            }
        } catch (\Exception $ex) {
            $response['response']['error'] = 1;
            $response['response']['error_message'] = json_encode($ex);
        }

        return response()->json($response);
    }
    /**
     * serveCartProductData
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function serveCartProductData($queryData, $response): \Illuminate\Http\JsonResponse{
        try {
            $query = FlatCatalog::select('source_product_id', 'id_product', 'sku', 'name', 'price', 'selling_price', 'quantity', 'image', 'url_key', 'product_type', 'voucher_type', 'group_id', 'brand', 'sub_category as category');
            $query->where('store', $queryData['store']);
            if ($queryData['sku']) {
                $query->whereIn('sku', explode(',', $queryData['sku']));
            } elseif ($queryData['id_product']) {
                $query->whereIn('id_product', explode(',', $queryData['id_product']));
            }
            $result = $query->get();
            if (count($result) > 0) {
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;
                $result =  $result->toArray();
                $response['result'] = $result;
            } else {
                $response['response']['error'] = 1;
                $response['response']['error_message'] = self::RECORD_NOT;
            }
        } catch (\Exception $ex) {
            $response['response']['error'] = 1;
            $response['response']['error_message'] = json_encode($ex);
        }


        return response()->json($response);
    }
    /**
     * serveStockData
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function serveStockData($queryData, $response): \Illuminate\Http\JsonResponse{
        $referer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : 'NA';
        file_put_contents('cache/log/stocklog_' . date("j.n.Y") . '.log', "\r\n" . json_encode($_SERVER), FILE_APPEND);
        file_put_contents('cache/log/stocklog_' . date("j.n.Y") . '.log', "\r\n" . $referer . '-' . $queryData['items'] . '-' . date("h:i:sa"), FILE_APPEND);
        try {
            $resultfinal = [];
            if (isset($queryData['items'])) {

                $arrayItems = array_unique(explode(",", $queryData['items']));

                foreach ($arrayItems as $value) {

                    $arrayValue = explode("_", $value);
                    $sku = $arrayValue[0];
                    $qty = $arrayValue[1];
                    $result = Product::select('parent_id', 'quantity')->where([['sku', $sku], ['store', $queryData['store']], ['parent_id', '!=', 0]])->get();
                    if (count($result) > 0) {
                        $result = $result->toArray();
                        $old_qty = $result[0]['quantity'];
                        $new_qty = $result[0]['quantity'] - $qty;
                        $parent_id = $result[0]['parent_id'];
                        $stock_status = 'out-of-stock';
                        if ($new_qty > 0) {
                            $stock_status = 'in-stock';
                        }
                        Product::where([['sku', $sku], ['store', $queryData['store']]])->update(["stock_status" => $stock_status, "quantity" => $new_qty]);

                        FlatCatalog::where([['sku', $sku], ['store', $queryData['store']]])->update(["stock_status" => $stock_status, "quantity" => $new_qty]);

                        $resultk = Product::where([['parent_id', $parent_id], ['store', $queryData['store']]])->get();

                        if (count($resultk) > 0) {
                            $resultk = $resultk->toArray();
                            $parent_status = 'out-of-stock';
                            if (array_search('in-stock', array_column($resultk, 'stock_status')) > -1) {
                                $parent_status = 'in-stock';
                            }
                        }

                        $sql3 = Product::where([['source_product_id', $parent_id], ['store', $queryData['store']]])->update(["stock_status" => $parent_status]);
                        FlatCatalog::where([['source_product_id', $parent_id], ['store', $queryData['store']]])->update(["stock_status" => $parent_status]);

                        // $conn->query($sql4);
                        if ($sql3) {

                            $resultfinal[] = $sku;
                            $list = PimInventory::createOrUpdate(["sku" => $sku], ['qty' => $new_qty]);
                        }

                        $this->refreshProductsCache($parent_id);
                    }
                }
                //return response()->json($resultfinal);
                $response['response']['success'] = "1";
                $response['response']['success_message'] = self::SUCCESS_MSG;
                $response['result'] = $resultfinal;
            } else {
                $response['response']['error'] = "1";
                $response['response']['error_message'] = self::ITEM_REQUIRED;
            }
        } catch (\Exception $ex) {
            $response['response']['error'] = "1";
            $response['response']['error_message'] = json_encode($ex);
        }
        return response()->json($response);
    }

    /**
     * serveStockDeltaData
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function serveStockDeltaData($queryData, $response): \Illuminate\Http\JsonResponse{
        try {
            $resultfinal = [];
            if (isset($queryData['items'])) {
                $arrayValue = explode("-", $queryData['items']);
                $source_product_id = $arrayValue[0];
                $qty = $arrayValue[1];
                $result = Product::select('parent_id', 'quantity', 'sku')->where([['source_product_id', $source_product_id], ['parent_id', '!=', 0]])->get();

                if (count($result) > 0) {
                    $result =  $result->toArray();
                    $new_qty = $qty;
                    $parent_id = $result[0]['parent_id'];
                    $sku = $result[0]['sku'];
                    $stock_status = 'out-of-stock';
                    if ($new_qty > 0) {
                        $stock_status = 'in-stock';
                    }

                    Product::where('sku', $sku)->update(['stock_status' => $stock_status, 'quantity' => $new_qty]);
                    FlatCatalog::where('sku', $sku)->update(['stock_status' => $stock_status, 'quantity' => $new_qty]);
                    $sqlResultk = Product::select('stock_status')->where('parent_id', $parent_id)->get();
                    if (count($sqlResultk) > 0) {
                        $resultk = $sqlResultk->toArray();
                        $parent_status = 'out-of-stock';
                        if (array_search('in-stock', array_column($resultk, 'stock_status')) > -1) {
                            $parent_status = 'in-stock';
                        }
                        $sql3 = Product::where('source_product_id', $parent_id)->update(['stock_status' => $parent_status]);
                        FlatCatalog::where('source_product_id', $parent_id)->update(['stock_status' => $parent_status]);

                        if ($sql3) {
                            $resultfinal[] = $sku;
                        }
                        $this->refreshProductsCache($parent_id);
                    }
                }
                if (!empty($resultfinal)) {
                    $response['response']['success'] = "1";
                    $response['response']['success_message'] = self::SUCCESS_MSG;
                    $response['result'] = $resultfinal;
                }
            } else {
                $response['response']['error'] = "1";
                $response['response']['error_message'] = self::ITEM_REQUIRED;
            }
        } catch (\Exception $ex) {
            $response['response']['error'] = "1";
            $response['response']['error_message'] = json_encode($ex);
        }
        return response()->josn($response);
    }    
    /**
     * refreshProductsCache
     *
     * @param  mixed $source_product_id
     * @return void
     */
    public function refreshProductsCache($source_product_id){
        try {
            if ($source_product_id) {

                $result = Product::where([['source_product_id', $source_product_id], ['parent_id', 0], ['status', 'active'], ['visibility', '!=', 'hide']])->get();

                if (count($result) > 0) {
                    $result = $result->toArray();
                }

                if ($result[0]['url_key'] != '') {
                    $url = self::PIM_URL . '?service=product&store=' . $result[0]['store'] . '&url_key=' . $result[0]['url_key'] . '&nocache';
                    $this->hitCurl($url);
                }
                // echo "<br>";
                if ($result[0]['id_product'] != '') {
                    $url = self::PIM_URL . '?service=product&store=' . $result[0]['store'] . '&id_product=' . $result[0]['id_product'] . '&nocache';
                    $this->hitCurl($url);
                }

                //die();

            }
        } catch (\Exception $ex) {
        }
    }    
    /**
     * hitCurl
     *
     * @param  mixed $url
     * @return void
     */
    public function hitCurl($url){
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CAINFO => "",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            ));

            $results = curl_exec($curl);
            $responseHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            if ($curlError) $this->logger->info($curlError);
            if ($responseHttpCode != 200 && $responseHttpCode != 201) {
                $this->logger->info('API is not responding : HTTP Code = ' . $responseHttpCode);
                return 0;
            }
        } catch (\Exception $ex) {
            // $this->logger->info($error);
        }
    }
    /**
     * fetchToken
     *
     * @return void
     */
    public function fetchToken(){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::SHIP_ROCKET_AUTH_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => self::SHIP_ROCKET_CREDENTIALS,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));


        $response = curl_exec($curl);

        curl_close($curl);


        return $response;
    }

    /**
     * serviceablePincode
     *
     * @param  mixed $token
     * @param  mixed $pincode
     * @param  mixed $isNew
     * @return void
     */
    public function serviceablePincode($token, $pincode, $isNew){

        $baseUrl = self::SHIP_ROCKET_SERVICEABLE;
        $url = $baseUrl . 'pickup_postcode=562106&delivery_postcode=' . $pincode . '&cod=1&weight=1';
        if ($isNew) {
            $url = $baseUrl . 'pickup_postcode=' . $pincode . '&delivery_postcode=562106&cod=0&weight=1&is_return=1'; //return check
        }
        //print_r($token); exit;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    /**
     * isJson
     *
     * @param  mixed $string
     * @return void
     */
    public function isJson($string){
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * fetchLocality
     *
     * @param  mixed $pincode
     * @return void
     */
    public function fetchLocality($pincode){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::SHIP_ROCKET_POSTCODE_DETAIL . 'postcode=' . $pincode,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    /**
     * recentProduct
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentProduct($queryData, $response): \Illuminate\Http\JsonResponse{
        try{
            $product_array = array();
            //echo $queryData['sku'];
            $admin_base_url = self::ADMIN_BASE_URL;
            $sku = explode(',', $queryData['sku']);
            // print_r($sku);
            // die;
        
            $product_array = FlatCatalog::select(
                'id_product',
                'source_product_id',
                'parent_id',
                'sku',
                'name',
                'price',
                'selling_price',
                'discount',
                'stock_status',
                'quantity',
                'status',
                'visibility',
                'description',
                'visibility',
                'description',
                DB::raw("CONCAT('$admin_base_url','product/',sku,'/300/','image') as image"),
                'configrable_atribute_code',
                'configrable_atribute_value',
                'has_child',
                'url_key',
                'meta_title',
                'meta_description',
                'meta_keyword',
                'fit',
                'product_position',
                'product_type',
                'voucher_type',
                'for_complaints',
                'manufactured_packed_for',
                'marked_by',
                'name_of_manufacturer',
                'place_of_manufacturer',
                'registered_office_address',
                'quantity_show',
                'team_up',
                'size_chart',
                'drm_bestseller',
                'google_product_category',
                'app_visibility',
                'is_new',
                'group_id',
                'brand',
                'size',
                'color_family',
                'sub_category',
                'store',
                'created_at',
                'updated_at'
            )
                ->whereIn('sku', $sku)
                ->where('visibility', 'show')
                ->where('status', 'active')
                ->where('url_key', '!=', '')
                ->get();

            if (count($product_array) > 0) {
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;
                $j = 0;
                foreach ($product_array as $value) {
                    //$product_array[$j]['category'] = serveCategoryname($value['source_product_id']);

                    $product_child_array = FlatCatalog::select('id_product', 'sku', 'configrable_atribute_value', 'price', 'selling_price', 'quantity', 'stock_status', 'image', 'size')
                        ->where('group_id', $value['group_id'])
                        ->where('store', $queryData['store'])->get()->toArray();


                    // $sql4 = "SELECT id_product,sku,price,selling_price,quantity,stock_status,image,url_key,configrable_atribute_value,size FROM pim_flat_catalog WHERE group_id = '" . $value['group_id'] . "'   AND pim_flat_catalog.status='active' AND store = '" . $queryData['store'] . "'";
                    // $sqlResult4 = $conn->query( $sql4 );

                    if (count($product_child_array) > 1) {

                        // array_multisort( array_column($product_child_array, 'size' ), SORT_ASC, $product_child_array );

                        $dk = 0;
                        $variationData = [];
                        $keys = array_column($product_child_array, 'size');
                        array_multisort($keys, SORT_ASC, $product_child_array);
                        //return response()->json($product_child_array);

                        $product_array[$j]['variation']=$this->get_product_variation($product_child_array);
                        
                        //$product_array[$j]['variation'] = $product_child_array;
                        if ($product_array[$j]['price'] == '' || $product_array[$j]['price'] == 0) {
                            $product_array[$j]['price'] = $product_array[$j]['variation'][0]['price'];
                            $product_array[$j]['selling_price'] = $product_array[$j]['variation'][0]['selling_price'];
                        }
                    }
                    $j++;
                }
                //$response['result']=$product_array;
                //sorting array using  anonymous function @usort 
                $arraySkus = explode(",", $queryData['sku']);
                foreach ($product_array as $product) {
                    foreach ($arraySkus as $key => $arraySku) {
                        if ($arraySku == $product['sku']) {
                            $sorting[] =  array(
                                "view_id" => $key,
                                "id_product" => $product['id_product'],
                                'source_product_id' => $product['source_product_id'],
                                "parent_id" => $product['parent_id'],
                                "sku" => $product['sku'],
                                "name" => $product['name'],
                                "price" => $product['price'],
                                "selling_price" => $product['selling_price'],
                                "discount" => $product['discount'],
                                "stock_status" => $product['stock_status'],
                                "quantity" => $product['quantity'],
                                "status" => $product['status'],
                                "visibility" => $product['visibility'],
                                "description" => $product['description'],
                                "image" => $product['image'],
                                "configrable_atribute_code" => $product['configrable_atribute_code'],
                                "configrable_atribute_value" => $product['configrable_atribute_value'],
                                "has_child" => $product['has_child'],
                                "url_key" => $product['url_key'],
                                "variation" => $product['variation']
                            );
                        }
                    }
                }
                usort($sorting, function ($a, $b) {
                    return $a['view_id'] - $b['view_id'];
                });
                $response['result'] = $sorting;
            } else {
                $response['response']['error'] = 1;
                $response['response']['error'] = self::RECORD_NOT;
            }
            return response()->json($response);
        } catch (\Exception $e) {
            $response['response']['success'] = 0;
            $response['response']['success_message'] = self::SOMETHING_WRONG;
            return response()->json($response);
        }
    }


    /**
     * serveSkuData
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function serveSkuData($queryData, $response): \Illuminate\Http\JsonResponse{
        try {
            $base_url = self::Breadcrumb_BaseUrl;
            $result = FlatCatalog::select('sku', 'name', 'selling_price', 'image', DB::raw("CONCAT('$base_url',url_key,'.html') as url"), 'brand', 'size')
                ->whereIn('sku', explode(',', $queryData['sku']))
                ->where('store', $queryData['store'])->get();

            if (count($result) > 0) {
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;
                // $result = $result;
                $response['result'] = $result->toArray();
                //print_r($response);die();

            } else {
                $response['response']['error'] = 1;
                $response['response']['error'] = self::RECORD_NOT;
            }
        } catch (\Exception $ex) {
            $response['response']['error'] = 1;
            $response['response']['error_message'] = json_encode($ex);
        }
        return response()->json($response);
    }

    /**
     * breadcrumbUrl
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function breadcrumbUrl($queryData, $response): \Illuminate\Http\JsonResponse{
        try {
            $base_url = self::Breadcrumb_BaseUrl;
            $result = FlatCatalog::select('sku', 'name', 'selling_price', 'breadcrumb', 'image', DB::raw("CONCAT('$base_url',url_key,'.html') as url"), 'brand', 'size')->where([['sku', $queryData['sku']], ['store', $queryData['store']]])->get();

            if (count($result) > 0) {
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;
                $result = $result->toArray();


                if ($result[0]['breadcrumb'] != null) {
                    $urlData = json_decode($result[0]['breadcrumb']);
                    $response['response']['error'] = 0;
                    $response['response']['url'] = $result[0]['url'];
                    if (count($urlData) > 0) {
                        $response['response']['url'] = $base_url . $urlData[count($urlData) - 1]->url_key . ".html";
                    }
                } else {
                    $response['response']['error'] = 0;
                    $response['response']['url'] = $result[0]['url'];
                }
            } else {
                $response['response']['error'] = 1;
                $response['response']['error_message'] = self::RECORD_NOT;
            }
        } catch (\Exception $ex) {
            $response['response']['error'] = 1;
            $response['response']['error_message'] = json_encode($ex);
        }
        return response()->json($response);
    }

    /**
     * serveWishlistData
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function serveWishlistData($queryData, $response): \Illuminate\Http\JsonResponse{
        try {
            $product_array = array();
            $admin_base_url = self::ADMIN_BASE_URL;
            $sku_list = explode(',', $queryData['sku']);
            $sqlResultm = FlatCatalog::select(
                'id_product',
                'source_product_id',
                'parent_id',
                'sku',
                'name',
                'price',
                'selling_price',
                'discount',
                'stock_status',
                'quantity',
                'status',
                'visibility',
                'description',
                'visibility',
                'description',
                DB::raw("CONCAT('$admin_base_url','product/',sku,'/300/','image') as image"),
                'configrable_atribute_code',
                'configrable_atribute_value',
                'has_child',
                'url_key',
                'meta_title',
                'meta_description',
                'meta_keyword',
                'fit',
                'product_position',
                'product_type',
                'voucher_type',
                'for_complaints',
                'manufactured_packed_for',
                'marked_by',
                'name_of_manufacturer',
                'place_of_manufacturer',
                'registered_office_address',
                'quantity_show',
                'team_up',
                'size_chart',
                'drm_bestseller',
                'google_product_category',
                'app_visibility',
                'is_new',
                'group_id',
                'brand',
                'size',
                'color_family',
                'sub_category',
                'store',
                'created_at',
                'updated_at'
            )->whereIn('sku', $sku_list)->where([['visibility', 'show'], ['status', 'active'], ['url_key', '!=', '']])->get();


            // return response()->json($sqlResultm);
            // $sqlResultm = $conn->query( $sqlm );

            if (count($sqlResultm) > 0) {
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;
                $product_array = $sqlResultm->toArray();
                //print_r($product_array); exit;
                $j = 0;
                //$j = 0;
                foreach ($product_array as $value) {
                    //$product_array[$j]['category'] = serveCategoryname($value['source_product_id']);
                    $sqlResult4 = FlatCatalog::where([['group_id', $value['group_id']], ['status', 'active'], ['store', $queryData['store']]])->get();
                    // $sql4 = "SELECT id_product,sku,price,selling_price,quantity,stock_status,image,url_key,configrable_atribute_value,size FROM pim_flat_catalog WHERE group_id = '" . $value['group_id'] . "'   AND pim_flat_catalog.status='active' AND store = '" . $queryData['store'] . "'";
                    // $sqlResult4 = $conn->query( $sql4 );


                    if (count($sqlResult4) > 1) {
                        $product_child_array = $sqlResult4->toArray();
                        //array_multisort( array_column( $product_child_array, 'configrable_atribute_value' ), SORT_ASC, $product_child_array );
                       // $variationData = [];
                        
                        $product_array[$j]['variation']=$this->get_product_variation($product_child_array);
                    
                        if ($product_array[$j]['price'] == '' || $product_array[$j]['price'] == 0) {
                            $product_array[$j]['price'] = $product_array[$j]['variation'][0]['price'];
                            $product_array[$j]['selling_price'] = $product_array[$j]['variation'][0]['selling_price'];
                        }
                    }
                    $j++;
                }
                $response['result'] = $product_array;
            } else {

                $response['response']['error'] = 1;
                $response['response']['error'] = self::RECORD_NOT;
            }
        } catch (\Exception $ex) {
            $response['response']['error'] = 1;
            $response['response']['error_message'] = json_encode($ex);
        }
        return response()->json($response);
    }
    /**
     * filterImageName
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterImageName($queryData, $response): \Illuminate\Http\JsonResponse{
        try {
            $gallery = ProductGallery::all();
            if (count($gallery) > 0) {
                $gallery_result = $gallery->toArray();
                foreach ($gallery_result as $key => $$j_resul) {

                    $imageName = $gallery_result[$key]['image'];
                    $urlArray = explode("/", $imageName);
                    $id_product  = $gallery_result[$key]['id_product_gallery'];
                    $update = ProductGallery::where('id_product_gallery', $id_product)->update(['image' => $urlArray[count($urlArray) - 1]]);
                }
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;
                //print_r("DONE"); exit;
            } else {
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::RECORD_NOT;
            }
        } catch (\Exception $ex) {
            $response['response']['error'] = 1;
            $response['response']['error_message'] = json_encode($ex);
        }
        return response()->json($response);
    }    
    /**
     * productVariation
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function productVariation($queryData, $response): \Illuminate\Http\JsonResponse{
        try {

            $query = FlatCatalog::where('status', 'active')->where('visibility', 'show');
            if ($queryData['id_product']) {
                $query->where('id_product', $queryData['id_product']);
            } else {
                $query->where('url_key', $queryData['url_key']);
            }
            $product_array = $query->where('store', $queryData['store'])->get();

            $response['result'] = $product_array[0];
            if (count($product_array) > 0) {
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;

                $product_child_array = FlatCatalog::where('parent_id', $response['result']['source_product_id'])->where('store', $queryData['store'])->get();
                $new_array = $product_child_array->toArray();

                array_multisort(array_column($new_array, 'size'), SORT_ASC, $new_array);
                // echo "<pre>";
                // print_r($product_child_array);die();
                $response['result']['category'] = $this->serveCategoryname($response['result']['source_product_id']);
                $response['result']['variation']=$this->get_product_variation($product_child_array);
                    
                $gallery_array = ProductGallery::select('position', 'image')->where('id_product', $response['result']['id_product'])->orderBy('position', 'ASC')->get();

                $response['result']['gallery'] = $gallery_array;
            
                $attributes=ProductAttributeValue::withAndWhereHas('get_attribute',function($q){
                    $q->where('is_visible','yes');
                })->select('value','id_product_attribute')->where('id_product',$response['result']['id_product'])->where('value', '!=', '')->where('value', '!=', 'No')->get()->toArray();
                
                $visible_attributes=[];
                foreach($attributes as $list){
                    $data['code']=$list['get_attribute']['code'];
                    $data['label']=$list['get_attribute']['label'];
                    $data['value']=$list['value'];
                    $visible_attributes[]=$data;

                }
            
                $oth_attributes=ProductAttributeValue::withAndWhereHas('get_attribute',function($q){
                    $q->where('is_visible','yes')->where('is_in_filter', 'no')->where('is_in_search', 'no')->where('is_in_sort', 'no');
        
                })->select('value','id_product_attribute')->where('id_product',$response['result']['id_product'])->where('value', '!=', '')->where('value', '!=', 'No')->get()->toArray();
                                

                $other_attributes=[];
                foreach($oth_attributes as $list){
                    $data1['code']=$list['get_attribute']['code'];
                    $data1['label']=$list['get_attribute']['label'];
                    $data1['value']=$list['value'];
                    $other_attributes[]=$data1;

                }

                

                $other_attributes = json_decode(json_encode($other_attributes), true);

                foreach ($other_attributes as $value) {

                    if ($value['code'] == 'size_chart') {
                        $response['result']['size_chart']['title'] = $value['value'];
                        $response['result']['size_chart']['key'] = strtolower(preg_replace("/[^0-9a-zA-Z]/", "_", $value['value']));
                    }

                    if ($value['code'] == 'team_up') {
                        if ($value['value'] != '') {
                            $response['result']['team_up_products'] = $this->serveProductGrid($value['value'], $queryData['store']);
                        }
                    }
                    if ($value['code'] == 'you_may_also_like') {
                        if ($value['value'] != '') {
                            $response['result']['you_may_also_like'] = $this->serveProductGrid($value['value'], $queryData['store']);
                        }
                    }
                    if ($value['code'] == 'top_sellers') {
                        if ($value['value'] != '') {
                            $response['result']['top_sellers'] = $this->serveProductGrid($value['value'], $queryData['store']);
                        }
                    }
                    if ($value['code'] == 'similar_products') {
                        if ($value['value'] != '') {
                            $response['result']['similar_products'] = $this->serveProductGrid($value['value'], $queryData['store']);
                        }
                    }
                }

                if ($response['result']['price'] == '' || $response['result']['price'] == 0) {
                    $response['result']['price'] = $response['result']['variation'][0]['price'];
                    $response['result']['selling_price'] = $response['result']['variation'][0]['selling_price'];
                }

                $response['result']['meta_title'] = 'Buy ' . $response['result']['name'] . ' Online for Women for only INR ' . $response['result']['selling_price'] . ' – Aurelia';
                $response['result']['meta_description'] = 'Buy ' . $response['result']['name'] . ' online at Aurelia. Get Up to 50% Off on a huge collection.';
                $response['result']['meta_keyword'] = 'Aurelia ' . $response['result']['name'] . ', Buy Aurelia Women ' . $response['result']['name'] . ' Online';

                if ($queryData['store'] == 1) {
                    $response['result']['meta_title'] = 'Buy ' . $response['result']['name'] . ' Online for Women for only INR ' . $response['result']['selling_price'] . ' – W For Woman';
                    $response['result']['meta_description'] = 'Buy ' . $response['result']['name'] . ' online at W For Woman. Get Up to 50% Off on a huge collection.';
                    $response['result']['meta_keyword'] = 'W For Woman ' . $response['result']['name'] . ', Buy W For Woman Women ' . $response['result']['name'] . ' Online';
                }
            } else {
                $response['response']['success'] = 0;
                $response['response']['success_message'] = '';
                $response['response']['error'] = 1;
                $response['response']['error_message'] = self::RECORD_NOT;
            }
        } catch (\Exception $ex) {
            $response['response']['error'] = 1;
            $response['response']['error_message'] = json_encode($ex);
        }
        return response()->json($response);
    }
    /**
     * serveRecentViews
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return \Illuminate\Http\JsonResponse
     */
    function serveRecentViews($queryData, $response): \Illuminate\Http\JsonResponse{
        $response['response']['success'] = 1;
        $response['response']['success_message'] = self::SUCCESS_MSG;
        if (isset($queryData['recent_views_products'])) {
            $response['result'] = $this->serveProductGrid($queryData['recent_views_products'], $queryData['store']);
        }
        return $response;
    }    
    /**
     * filterGenrate
     *
     * @param  mixed $result_query
     * @param  mixed $newFilterArray
     * @return void
     */
    public function filterGenrate($result_query, $newFilterArray){
        $product_array = array();
        $j_result = $result_query->toArray();
        $new_filter = '';
        $array = implode(', ', array_column($j_result, 'id_product'));
        $new_array = explode(',', $array);
        $attributes=ProductAttributeValue::withAndWhereHas('get_attribute',function($q){
            $q->where('is_in_filter','yes');

        })->select('value','id_product_attribute')
        ->whereRaw("id_product in ('" . $array . "')")
        //->whereIn('id_product',$new_array)
        ->groupBy(['value', 'id_product_attribute'])->get()->toArray();


        // $sqlc = "SELECT code,label,value FROM pim_product_attribute JOIN pim_product_attribute_value ON pim_product_attribute_value.id_product_attribute=pim_product_attribute.id_product_attribute WHERE is_in_filter='yes' AND id_product IN (" . $array . ") GROUP BY pim_product_attribute_value.value ,pim_product_attribute_value.id_product_attribute";
    

        $filter_attributes = json_decode(json_encode($attributes), true);

        $c = 0;
        $sizeArray = array();
        foreach ($filter_attributes as $value) {
            //$value= get_object_vars($val);
            $value['code']=$value['get_attribute']['code'];
            $value['label']=$value['get_attribute']['label'];

            if ($value['code'] == 'size') {
                $newArray = explode(",", $value['value']);
                foreach ($newArray as $NewValue) {
                    if (!in_array($NewValue, $sizeArray)) {
                        $filter_attributes_array[$value['label']][preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue)]['code'] = $value['code'];
                        $filter_attributes_array[$value['label']][preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue)]['value'] = preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue);
                        $filter_attributes_array[$value['label']][preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue)]['value_key'] = preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue);
                        $sizeArray[] = $NewValue;
                        $c++;
                    }
                }
            } else {
                if ($value['value'] != 'No' && $value['value'] != '' && $value['value'] != '0.00%' && $value['value'] != '0%' && $value['value'] != '0') {
                    $filter_attributes_array[$value['label']][$c]['code'] = $value['code'];
                    $filter_attributes_array[$value['label']][$c]['value'] = $value['value'];
                    $filter_attributes_array[$value['label']][$c]['value_key'] = preg_replace('/[^A-Za-z0-9\. -]/', '_', str_replace(' ', '-', strtolower($value['value'])));
                    $c++;
                }
            }
        }

        // for price filter

        $min_price = min(array_column($j_result, 'selling_price'));
        $max_price = max(array_column($j_result, 'selling_price'));
        $add_price = 500;

        if ($add_price > $min_price) {
            $start_price = 0;
        } else {
            $start_price = ((int)($min_price / $add_price)) * $add_price;
        }
        $end_price = ((int)(($max_price / $add_price) + 1)) * $add_price;
        $xk = $start_price;
        $price_range = array();
        $t = 0;
        while ($xk <= $end_price) {
            if ($t <= 1) {
                $price_range[$t]['code'] = 'selling_price';
                $price_range[$t]['value'] = 'Rs.' . $xk . ' to Rs.' . ($xk + $add_price);
                $price_range[$t]['value_key'] = $xk . ',' . ($xk + $add_price);
                $xk = $xk + $add_price;
            } else {
                $price_range[$t]['code'] = 'selling_price';
                $price_range[$t]['value'] = 'Rs.' . $xk . ' to Rs.' . ($xk + 1000);
                $price_range[$t]['value_key'] = $xk . ',' . ($xk + 1000);
                $xk = $xk + 1000;
            }
            $t++;
        }
        unset($price_range[$t - 1]);
        $d = 0;

        $discount_filter_array = array_unique(array_column($j_result, 'discount'));
        sort($discount_filter_array);
        $df = 0;
        $discount_send_array = array();
        foreach ($discount_filter_array as  $value) {
            if ($value > 0) {
                $discount_value = intdiv($value, 10) * 10;
                if ($discount_value) {
                    if (!in_array($discount_value, $discount_send_array)) {
                        $discount_send_array[] = $discount_value;
                        $discount_filter[$df]['code'] = 'discount';
                        $discount_filter[$df]['value'] =  $discount_value . '% and Above';
                        $discount_filter[$df]['value_key'] = preg_replace('/[^A-Za-z0-9\. -]/', '_', $discount_value);
                        $df++;
                    }
                }
            }
        }
        if (count($discount_filter) > 0) {
            $new_filter1[count($filter_attributes_array)]['filter_lable'] = 'Discount';
            $new_filter1[count($filter_attributes_array)]['options'] = $discount_filter;
        }

        $filter_attributes_array['Discount'] = $new_filter1[count($filter_attributes_array)]['options'];

        $price_filter[count($filter_attributes_array)]['filter_lable'] = 'Price';
        $price_filter[count($filter_attributes_array)]['options'] = $price_range;
        $filter_attributes_array['Price'] = $price_filter[count($filter_attributes_array)]['options'];
        //$sizeKey = 1;
        $new_filter = array();
        foreach ($filter_attributes_array as $key => $value) {
            switch ($key) {
                case "Gender":
                    $d = 0;
                    break;
                case "Category":
                    $d = 1;
                    break;
                case "Size":
                    $d = 2;
                    break;
                case "Brand":
                    $d = 3;
                    break;
                case "Price":
                    $d = 4;
                    break;
                case "Colour":
                    $d = 5;
                    break;
                case "Discount":
                    $d = 6;
                    break;
                default:
                    $d = 7 + $d;
            }

            if (count($value) > 1 || array_key_exists(array_values($value)[0]['code'], $newFilterArray)) {
                $new_filter[$d]['filter_lable'] = $key;
                $new_filter[$d]['options'] = $value;

                // if($key=="Gender") {
                //     $sizeKey = 2;
                // }
                if ($key == "Size") {
                    $sizeLists = $value;
                }
                $d++;
            }
        }
        $lastindex = count($new_filter);

        ksort($new_filter);
        $new_filter = array_values($new_filter);

        if (isset($sizeLists)) {
            ksort($sizeLists);
            $size_options = $this->sizeSort($sizeLists);

            foreach ($new_filter as $nsKey => $new_filt) {
                if ($new_filt['filter_lable'] == "Size") {
                    if ($size_options != "") {
                        $new_filter[$nsKey]['options'] = $size_options;
                    }
                }
            }
        }
        return $new_filter;
    }
        
    /**
     * filterGenrate_new
     *
     * @param  mixed $result_query
     * @param  mixed $newFilterArray
     */
    public function filter_Generate_new($result_query, $newFilterArray){
        $product_array = array();
        $j_result = $result_query->toArray();
    
        $new_filter = '';
        $array = implode(', ', array_column($j_result, 'id_product'));
    
        $new_array = explode(',', $array);
    
            
            $attributes=ProductAttributeValue::withAndWhereHas('get_attribute',function($q){
                $q->where('is_in_filter','yes');

            })->select('value','id_product_attribute')
            //->whereIn("id_product" ,$new_array)
            ->whereRaw("id_product in ('" . $array . "')")
            ->groupBy(['value', 'id_product_attribute'])->get()->toArray();
            // print_r(DB::connection()->getQueryLog());
            // die;
            // print_r($attributes);
            // die;

        // $sqlc = "SELECT code,label,value FROM pim_product_attribute JOIN pim_product_attribute_value ON pim_product_attribute_value.id_product_attribute=pim_product_attribute.id_product_attribute WHERE is_in_filter='yes' AND id_product IN (" . $array . ") GROUP BY pim_product_attribute_value.value ,pim_product_attribute_value.id_product_attribute";
        // // echo $sqlc;
        // // die;
    
    
        $filter_attributes = json_decode(json_encode($attributes), true);

        $c = 0;
        $sizeArray = array();
        foreach ($filter_attributes as $value) {
            //$value= get_object_vars($val);
        
            $value['code']=$value['get_attribute']['code'];
            $value['label']=$value['get_attribute']['label'];

            if ($value['code'] == 'size') {
                $newArray = explode(",", $value['value']);
                foreach ($newArray as $NewValue) {
                    if (!in_array($NewValue, $sizeArray)) {
                        $filter_attributes_array[$value['label']][preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue)]['code'] = $value['code'];
                        $filter_attributes_array[$value['label']][preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue)]['value'] = preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue);
                        $filter_attributes_array[$value['label']][preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue)]['value_key'] = preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue);
                        $sizeArray[] = $NewValue;
                        $c++;
                    }
                }
            } elseif($value['code']=='color_family'){
                $sqlResultColor =ColorsMap::where('color',$value['value'])->first();
            
               // $filter_Color_attributes = $sqlResultColor->toArray();
            
                $filter_attributes_array[$value['label']][$c]['code'] = $value['code'];
                $filter_attributes_array[$value['label']][$c]['value'] = $sqlResultColor->color;
                $filter_attributes_array[$value['label']][$c]['color_code'] = $sqlResultColor->color_code;
                $filter_attributes_array[$value['label']][$c]['value_key'] = preg_replace('/[^A-Za-z0-9\. -]/', '_', str_replace(' ', '-', strtolower($sqlResultColor->color)));
                $c++;


            } else {
                if ($value['value'] != 'No' && $value['value'] != '' && $value['value'] != '0.00%' && $value['value'] != '0%' && $value['value'] != '0') {
                    $filter_attributes_array[$value['label']][$c]['code'] = $value['code'];
                    $filter_attributes_array[$value['label']][$c]['value'] = $value['value'];
                    $filter_attributes_array[$value['label']][$c]['value_key'] = preg_replace('/[^A-Za-z0-9\. -]/', '_', str_replace(' ', '-', strtolower($value['value'])));
                    $c++;
                }
            }
        }

        // for price filter

        $min_price = min(array_column($j_result, 'selling_price'));
        $max_price = max(array_column($j_result, 'selling_price'));
        $add_price = 500;

        if ($add_price > $min_price) {
            $start_price = 0;
        } else {
            $start_price = ((int)($min_price / $add_price)) * $add_price;
        }
        $end_price = ((int)(($max_price / $add_price) + 1)) * $add_price;
        $xk = $start_price;
        $price_range = array();
        $t = 0;
        while ($xk <= $end_price) {
            if ($t <= 1) {
                $price_range[$t]['code'] = 'selling_price';
                $price_range[$t]['value'] = 'Rs.' . $xk . ' to Rs.' . ($xk + $add_price);
                $price_range[$t]['value_key'] = $xk . ',' . ($xk + $add_price);
                $xk = $xk + $add_price;
            } else {
                $price_range[$t]['code'] = 'selling_price';
                $price_range[$t]['value'] = 'Rs.' . $xk . ' to Rs.' . ($xk + 1000);
                $price_range[$t]['value_key'] = $xk . ',' . ($xk + 1000);
                $xk = $xk + 1000;
            }
            $t++;
        }
        unset($price_range[$t - 1]);
        $d = 0;

        $discount_filter_array = array_unique(array_column($j_result, 'discount'));
        sort($discount_filter_array);
        $df = 0;
        $discount_send_array = array();
        foreach ($discount_filter_array as  $value) {
            if ($value > 0) {
                $discount_value = intdiv($value, 10) * 10;
                if ($discount_value) {
                    if (!in_array($discount_value, $discount_send_array)) {
                        $discount_send_array[] = $discount_value;
                        $discount_filter[$df]['code'] = 'discount';
                        $discount_filter[$df]['value'] =  $discount_value . '% and Above';
                        $discount_filter[$df]['value_key'] = preg_replace('/[^A-Za-z0-9\. -]/', '_', $discount_value);
                        $df++;
                    }
                }
            }
        }
        if (count($discount_filter) > 0) {
            $new_filter1[count($filter_attributes_array)]['filter_lable'] = 'Discount';
            $new_filter1[count($filter_attributes_array)]['options'] = $discount_filter;
        }

        $filter_attributes_array['Discount'] = $new_filter1[count($filter_attributes_array)]['options'];

        $price_filter[count($filter_attributes_array)]['filter_lable'] = 'Price';
        $price_filter[count($filter_attributes_array)]['options'] = $price_range;
        $filter_attributes_array['Price'] = $price_filter[count($filter_attributes_array)]['options'];
        //$sizeKey = 1;
        $new_filter = array();
        foreach ($filter_attributes_array as $key => $value) {
            switch ($key) {
                case "Gender":
                    $d = 0;
                    break;
                case "Category":
                    $d = 1;
                    break;
                case "Size":
                    $d = 2;
                    break;
                case "Brand":
                    $d = 3;
                    break;
                case "Price":
                    $d = 4;
                    break;
                case "Colour":
                    $d = 5;
                    break;
                case "Discount":
                    $d = 6;
                    break;
                default:
                    $d = 7 + $d;
            }

            if (count($value) > 1 || array_key_exists(array_values($value)[0]['code'], $newFilterArray)) {
                $new_filter[$d]['filter_lable'] = $key;
                $new_filter[$d]['options'] = $value;

                // if($key=="Gender") {
                //     $sizeKey = 2;
                // }
                if ($key == "Size") {
                    $sizeLists = $value;
                }
                $d++;
            }
        }
        $lastindex = count($new_filter);

        ksort($new_filter);
        $new_filter = array_values($new_filter);

        if (isset($sizeLists)) {
            ksort($sizeLists);
            $size_options = $this->sizeSort($sizeLists);

            foreach ($new_filter as $nsKey => $new_filt) {
                if ($new_filt['filter_lable'] == "Size") {
                    if ($size_options != "") {
                        $new_filter[$nsKey]['options'] = $size_options;
                    }
                }
            }
        }
        return $new_filter;

    }
    /**
     * sizeSort
     *
     * @param  mixed $sizeLists
     * @return void
     */
    public function sizeSort($sizeLists){
        $dt = 0;
        foreach ($sizeLists as $skey => $sizeList) {
            // echo $value['configrable_atribute_value'];
            switch ($sizeList['value']) {
                case "FS":
                    $dt = 0;
                    break;
                case "XS":
                    $dt = 1;
                    break;
                case "S":
                    $dt = 3;
                    break;
                case "M":
                    $dt = 4;
                    break;
                case "L":
                    $dt = 5;
                    break;
                case "XL":
                    $dt = 6;
                    break;
                case "XXL":
                    $dt = 7;
                    break;
                case "6":
                    $dt = 8;
                    break;
                case "8":
                    $dt = 9;
                    break;
                case "10":
                    $dt = 10;
                    break;
                case "12":
                    $dt = 11;
                    break;
                case "14":
                    $dt = 12;
                    break;
                case "16":
                    $dt = 13;
                    break;
                case "18":
                    $dt = 14;
                    break;
                case "WP":
                    $dt = 15;
                    break;
                case "WS":
                    $dt = 16;
                    break;
                case "WM":
                    $dt = 17;
                    break;
                case "WL":
                    $dt = 18;
                    break;
                case "WG":
                    $dt = 19;
                    break;
                case "WVG":
                    $dt = 20;
                    break;
                default:
                    $dt = 20 + $dt;
            }
            $size_options[$dt] = $sizeList;
        }
        ksort($size_options);
        return $size_options;
    }
    
    /**
     * serveCategoryname
     *
     * @param  mixed $productId
     * @return void
     */
    public function serveCategoryname($productId){
        $cat_info = ProductCategory::with('get_category')->where('id_product', $productId)->first();
        if ($cat_info) {
            return  $cat_info->name;
        }

        return '';
    }
    
    /**
     * serveBreadCrumDeatils
     *
     * @param  mixed $productId
     * @param  mixed $store
     * @return void
     */
    public function serveBreadCrumDeatils($productId, $store){
        // $sql ="SELECT pim_categories_data.name,CONCAT('category/',pim_categories_data.url_key, '.html') as url_key  FROM pim_categories_data INNER JOIN pim_product_categories ON pim_categories_data.id_category=pim_product_categories.id_catetory Where id_product=".$productId." LIMIT 3";
        // echo $sql;
        // die;
        $result = CategoryData::whereHas('get_product_category', function ($q) use ($productId) {
            $q->where('id_product', $productId);
        })->select('name', DB::raw("CONCAT('category/',pim_categories_data.url_key,'.html') as url_key"))->take(3)->get()->toArray();
        // print_r($result);
        // die;
        //print_r($sql); exit;
        // $sqlResult = $conn->query( $sql );
        // $newresult = $sqlResult->fetch_all( MYSQLI_ASSOC );
        return $result;
    }    
    /**
     * serveCategoryProducts
     *
     * @param  mixed $productId
     * @param  mixed $group_id
     * @param  mixed $similar_attribute
     * @return void
     */
    public function serveCategoryProducts($productId, $group_id, $similar_attribute){
        if (!empty($similar_attribute)) {
            foreach ($similar_attribute as $key => $value) {
                $filter_query[] = " AND `" . $key . "`='" . $value . "'";
                $group_ids = explode(',', $group_id);
                $sqlResultm = FlatCatalog::whereNotIn('group_id', $group_ids)->where('visibility', 'show')->where('stock_status', 'in-stock')->where('status', 'active')->where('url_key', '!=', implode(" ", $filter_query))->groupBy('group_id')->orderBy('percentile_availability', 'DESC')->orderBy('sku', 'DESC')->take(20)->get();

                // $sqlm = "SELECT * FROM pim_flat_catalog WHERE `group_id` NOT IN  ('".$group_id."') AND pim_flat_catalog.visibility='show' AND stock_status='in-stock'   AND pim_flat_catalog.status='active' AND url_key!='' ".implode(" ",$filter_query) ." GROUP BY pim_flat_catalog.group_id ORDER BY percentile_availability DESC,product_position DESC,  sku DESC LIMIT 20";
                // $sqlResultm = $conn->query( $sqlm );
                if (count($sqlResultm) < 10) {
                    array_pop($filter_query);
                }
            }

            $sqlResultm = FlatCatalog::whereNotIn('group_id', $group_ids)->where('visibility', 'show')->where('stock_status', 'in-stock')->where('status', 'active')->where('url_key', '!=', implode(" ", $filter_query))->groupBy('group_id')->orderBy('percentile_availability', 'DESC')->orderBy('sku', 'DESC')->take(20)->get();
            //$sqlm = "SELECT * FROM pim_flat_catalog WHERE `group_id` NOT IN  ('".$group_id."') AND pim_flat_catalog.visibility='show' AND stock_status='in-stock'   AND pim_flat_catalog.status='active' AND url_key!='' ".implode(" ",$filter_query) ." GROUP BY pim_flat_catalog.group_id ORDER BY percentile_availability DESC, product_position DESC,  sku DESC LIMIT 20";
            //print_r($sqlm); exit;
            //$sqlResultm = $conn->query( $sqlm );
            if (count($sqlResultm) > 0) {
                // echo 'ok';
                // die;
                $product_array = $sqlResultm->toArray();
                // print_r($product_array);
                // die;
                // die;
                $j = 0;
                $dk = 0;
                foreach ($product_array as $value) {
                    // echo 'ok';
                    // die;
                    $product_child_array = FlatCatalog::select('id_product', 'sku', 'configrable_atribute_value', 'price', 'selling_price', 'quantity', 'stock_status', 'image', 'size')->where('group_id', $value['group_id'])->where('stock_status', 'in-stock')->where('status', 'active')->where('store', $value['store'])->get();
                    
                    // array_multisort( array_column( (array)$product_child_array, 'configrable_atribute_value' ), SORT_ASC, (array)$product_child_array );
                    $product_array[$j]['image'] = self::ADMIN_BASE_URL . "product/" . str_replace(' ', '', $value['sku']) . "/300/" . $value['image'];
                    //die; 
                    $product_array[$j]['configrable_atribute_value'] = $product_array[$j]['size'];
                    $product_array[$j]['variation']=$this->get_product_variation($product_child_array);
            
                    //$product_array[$j]['variation'] = $value;
                    $j++;
                }
            }
        }
        // print_r($product_array);
        // die;
        return $product_array;
    }
    
    /**
     * serveProductGrid
     *
     * @param  mixed $values
     * @param  mixed $store
     * @return void
     */
    public function serveProductGrid($values, $store){
        $product_array = FlatCatalog::whereIn('sku', explode(',', $values))
            ->where([['store', $store], ['visibility', 'show'], ['stock_status', 'in-stock'], ['status', 'active'], ['url_key', '!=', '']])->get()->toArray();

        if (count($product_array) > 0) {

            $j = 0;
            foreach ($product_array as $value) {
                $product_child_array = FlatCatalog::where([['parent_id', $value['source_product_id']], ['stock_status', 'in-stock'], ['status', 'active'], ['store', $store]])->get()->toArray();

                array_multisort(array_column($product_child_array, 'configrable_atribute_value'), SORT_ASC, $product_child_array);
                $product_array[$j]['variation'] = $product_child_array;
                $j++;
            }
        }
        return $product_array;
    }    
    /**
     * get_product_variation
     *
     * @param  mixed $array_val
     * @return void
     */
    public function get_product_variation($array_val){

        $dk = 0;
        $variationData = [];
        foreach ($array_val as $key => $value) {
            $value['image'] = self::ADMIN_BASE_URL . "product/" . str_replace(' ', '', $value['sku']) . "/300/" . $value['image'];
            switch ($value['size']) {
                case "FS":
                    $dk = 0;
                    break;
                case "XS":
                    $dk = 1;
                    break;
                case "S":
                    $dk = 3;
                    break;
                case "M":
                    $dk = 4;
                    break;
                case "L":
                    $dk = 5;
                    break;
                case "XL":
                    $dk = 6;
                    break;
                case "XXL":
                    $dk = 7;
                    break;
                case "6":
                    $dk = 8;
                    break;
                case "8":
                    $dk = 9;
                    break;
                case "10":
                    $dk = 10;
                    break;
                case "12":
                    $dk = 11;
                    break;
                case "14":
                    $dk = 12;
                    break;
                case "16":
                    $dk = 13;
                    break;
                case "18":
                    $dk = 14;
                    break;
                case "WP":
                    $dk = 15;
                    break;
                case "WS":
                    $dk = 16;
                    break;
                case "WM":
                    $dk = 17;
                    break;
                case "WL":
                    $dk = 18;
                    break;
                case "WG":
                    $dk = 19;
                    break;
                case "WVG":
                    $dk = 20;
                    break;
                default:
                    $dk = $value['size'];
            }
            //echo $dk;

            $variationData[$dk] = $value;
        }
        return $variationData;
    }


}
