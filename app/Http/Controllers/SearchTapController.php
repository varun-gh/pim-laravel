<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FlatCatalog;
use App\Models\ProductCategory;
use App\Models\CategoryData;
use App\Models\ProductGallery;
use Illuminate\Support\Facades\DB;

class SearchTapController extends Controller
{
    //
    const SUCCESS_MSG = 'Search result';
    const RECORD_FOUND = 'Record Found';
    const RECORD_NOT = 'Record Not Found';
    const URL_PREFIX = "https://ketch.greenhonchos.com/";
    const URL_PREFIX_ADMIN = "https://ketchadmin.greenhonchos.com/";
    const SEARCH_TAP_API="https://manage.searchtap.net/v2/collections/";
    const BRAND= "ketch";
    /**
     * search_tap
     *
     * @param  mixed $request
     * @return void
     */
    public function search_tap(Request $request)
    {
        //$response = array();
        try {
            set_time_limit(300);
            $records = [];
            $records = FlatCatalog::where([
                ['visibility', 'show'],
                ['url_key', '!=', ''],
                ['status', 'active'],
                ['size', '!=', ''],
                ['stock_status', 'in-stock'],
                ['store', '1']
            ])->get();

            $i = 0;
            foreach ($records->chunk(10) as $list) {

                $list->source_product_id;
                // die;
                // $sqlm = "SELECT pim_categories_data.name FROM pim_product_categories LEFT JOIN pim_categories_data on pim_product_categories.id_catetory=pim_categories_data.id_category where pim_categories_data.store=1 and pim_categories_data.parent_id>0 and pim_categories_data.status='active' and pim_categories_data.include_in_menu='yes' and pim_product_categories.id_product=".$list['source_product_id']."";
                // echo $sqlm;
                // die;


            }
        } catch (\Exception $e) {
        }
    }
    /**
     * delete
     *
     * @param  mixed $request
     * @return void
     */
    public function delete(Request $request)
    {
        $response = array();
        try {

            $records=FlatCatalog::where('visibility','show')
            ->where(function($q){
                $q->where('status','inactive')->orWhere('stock_status','out-of-stock');
            })->get();
            
            if(count($records)>0){

                foreach ($records as $value) {
                    
                    $response['result'][self::BRAND][]=$value['source_product_id'];
                }

                $result=$this->hitCurlUrlForDelete($response);
                return response()->json($result);

            }else{
                $response['response']['success'] = 0;
                $response['response']['success_message'] = '';
                $response['response']['error'] = 1;
                $response['response']['error_message'] = self::RECORD_NOT;

                return response()->json($response);

            }



        } catch (\Exception $e) {
            $response['response']['success'] = 0;
            $response['response']['success_message'] = '';
            $response['response']['error'] = 1;
            $response['response']['error_message'] = json_encode($e);

            return response()->json($response);

        }
    }
    public function hitCurlUrlForDelete($response){

        if(is_array($response['result'])) {
            foreach ($response['result'] as $key => $valuenew) {
        
                $collection_id='F5BYFZYBTK4IUYP6B6IXSG9N';
                $write_token='GSB5DQI27RXDE77ICWHBF7QA';
                //echo json_encode($value);
        
                $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL =>self::SEARCH_TAP_API.$collection_id."/records",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_POSTFIELDS => "[".implode(",",$valuenew)."]",
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                    "Authorization: Bearer ".$write_token
                ),
                ));
            
                //echo json_encode($value);
            
                $response = curl_exec($curl);
            
                curl_close($curl);
                //print_r($response);
                //die();
                echo $response;
            }
        }
    }
    /**
     * category_sync
     *
     * @param  mixed $request
     * @return void
     */
    public function category_sync(Request $request)
    {
        $response = array();
        try {
            
            $datas=CategoryData::where('status','active')->where('include_in_menu','yes')->orderBy('position','ASC')->get();
            $i=0;
            //$url_prefix='https://ketch.greenhonchos.com/';
            if(count($datas)>0){
                foreach($datas as $value){
                    // echo $value['position'];
                    // die;
                    $response['result'][$i]['_position']=(int)$value['position']; 
                    $response['result'][$i]['created_at']= strtotime($value['created_at']); 
                    $response['result'][$i]['description']=$value['description']==null ? "" : $value['description'];  
                    $response['result'][$i]['id']=(int)$value['id_category']; 
                    $response['result'][$i]['include_in_menu']= $value['include_in_menu']=="yes" ? 1 : 0;
                    $response['result'][$i]['isLastLevel']= 0;
                    $response['result'][$i]['is_active']= 1;
                    $response['result'][$i]['last_pushed_to_searchtap']= Date("Y-m-d")." 14:20:41";
                    $response['result'][$i]['level']= 3;
                    $response['result'][$i]['meta_description']=$value['meta_description']==null ? "" : $value['meta_description'];
                    $response['result'][$i]['meta_keywords']= (array)$value['meta_keyword']; 
                    $response['result'][$i]['meta_title']=$value['meta_title']==null ? "" : $value['meta_title'];
                    $response['result'][$i]['name']=$value['name']; 
                    $response['result'][$i]['parent_id']=(int)($value['parent_id']); 
                    $response['result'][$i]['path']= $value['url_key']; 
                    $id_category=$value['id_category'];
                    $productCountResult=FlatCatalog::withAndWhereHas('product_category',function($q) use($id_category){
                        $q->where('id_catetory',$id_category);
                    })->where('visibility','show')
                    ->where('url_key','!=','')
                    ->where('status','active')
                    ->count();

                
            
                //     $productCountResult = $conn->query( $productCountsql );
                    $response['result'][$i]['product_count']= $productCountResult; 
                    $response['result'][$i]['st_popularity']= $productCountResult; 
                    $response['result'][$i]['url']=self::URL_PREFIX."/category/".$value['url_key'].".html"; 
                
                    
                    $i++;

                }

                $this->hitCurlUrl($response);

               // return response()->json($response);
            }else{
                $response['response']['success'] = 0;
                $response['response']['success_message'] = '';
                $response['response']['error'] = 1;
                $response['response']['error_message'] = self::RECORD_NOT;
            }
        } catch (\Exception $e) {
            $response['response']['success'] = 0;
            $response['response']['success_message'] = '';
            $response['response']['error'] = 1;
            $response['response']['error_message'] = json_encode($e);

        }
        return response()->json($response);

    }    
    /**
     * hitCurlUrl
     *
     * @param  mixed $response
     * @return void
     */
    public function hitCurlUrl($response){

        $collection_id='ZDSHF85MTGUHWP1SB7PFELNB';
        $write_token='GSB5DQI27RXDE77ICWHBF7QA';
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => self::SEARCH_TAP_API.$collection_id."/records",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($response['result']),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Authorization: Bearer ".$write_token
        ),
        ));

        //echo json_encode($value);

        $response = curl_exec($curl);

        curl_close($curl);
        // print_r($response);
        // die();
        // $resp = [$value,json_decode($response)];
        // echo json_encode($resp);  exit;
        return $response;




    }
    /**
     * full_sync
     *
     * @param  mixed $request
     * @return void
     */
    public function full_sync(Request $request)
    {
        $response = array();
        try {
            //ini_set('memory_limit', 5000);
            $records = FlatCatalog::where([
                ['visibility', 'show'],
                ['url_key', '!=', ''],
                ['status', 'active'],
                ['size', '!=', ''],
                ['stock_status', 'in-stock']

            ])->groupBY('group_id')->limit(50)->get();
            $i = 0;
            if (count($records) > 0) {
                foreach ($records as  $value) {

                    if ($value->group_id != NULL) {
                        //   print_r($value);
                        $category = [];
                        $store = $value['store'];
                        $source_product = $value->source_product_id;

                        $records1 = ProductCategory::select('pim_categories_data.name')->join('pim_categories_data', 'pim_product_categories.id_catetory', '=', 'pim_categories_data.id_category')
                            ->where('pim_categories_data.store', $value['store'])
                            ->where('pim_categories_data.parent_id', '>', 0)
                            ->where('pim_categories_data.status', 'active')
                            ->where('pim_categories_data.include_in_menu', 'yes')
                            ->where('pim_categories_data.include_in_breadcrumb', 'yes')
                            ->where('pim_product_categories.id_product', $value['source_product_id'])
                            ->first();
                        if ($records1) {
                            $category = $records1->name;
                        }

                        $stSize = array();
                        //$stSize2= explode(",",str_replace(']','',str_replace('[','Size ',$value['size'])));
                        $stSize2 = array();
                        $brand1=$value['brand'];
                        $brand=self::BRAND;



                        if ($value['sku']) {
                            $data_lists = FlatCatalog::where([
                                ['visibility', 'show'],
                                ['url_key', '!=', ''],
                                ['status', 'active'],
                                ['size', '!=', ''],
                                ['stock_status', 'in-stock'],
                                ['group_id', $value['group_id']]
                            ])->get();


                            foreach ($data_lists as  $getProductGroupWise) {
                                $stSize[] =  $getProductGroupWise['size'];
                                $stSize2[] =  $getProductGroupWise['size'];
                            }

                            $gallery_array = ProductGallery::select('position', 'image')->where('id_product', $value['source_product_id'])->orderBy('position', 'ASC')->get();

                            $galleryList = array();
                            foreach ($gallery_array as $gkey => $gall_array) {
                                $galleryList[$gkey]['position'] = $gall_array['position'];
                                $galleryList[$gkey]['image'] = self::URL_PREFIX_ADMIN. "product/" . $value['sku'] . "/300/" . $gall_array['image'];
                                $galleryList[$gkey]['vedio'] = '';
                            }

                            
                            $response['result'][$brand][$i]['_size'] = $stSize;
                            $response['result'][$brand][$i]['_size_search'] = $stSize2;
                            $response['result'][$brand][$i]['_category'] = $category;
                            $response['result'][$brand][$i]['in_stock'] = 1;
                            $response['result'][$brand][$i]['status'] = 1;
                            $response['result'][$brand][$i]['gallery'] = $galleryList;
                            $response['result'][$brand][$i]['url_key'] = $value['url_key'];
                            $response['result'][$brand][$i]['stock_qty'] = $value['quantity'];
                            $response['result'][$brand][$i]['image'] = self::URL_PREFIX_ADMIN . "product/" . $value['sku'] . "/300/" . $value['image'];
                            $response['result'][$brand][$i]['id'] = (int)$value['source_product_id'];
                            $response['result'][$brand][$i]['name'] = $value['name'];
                            $response['result'][$brand][$i]['sku'] = $value['sku'];
                            $response['result'][$brand][$i]['style'] = $value['group_id'];
                            $response['result'][$brand][$i]['brand'] = $brand1;
                            $response['result'][$brand][$i]['discount_percentage'] = (int)$value['discount'];
                            $response['result'][$brand][$i]['discount_filter'] = $value['discount'] . '%';
                            $response['result'][$brand][$i]['discounted_price'] = (int)$value['selling_price'];
                            $response['result'][$brand][$i]['price'] = (int)$value['price'];
                            $response['result'][$brand][$i]['product_position'] = (int)$value['product_position'];
                            $response['result'][$brand][$i]['url'] = self::URL_PREFIX. $value['url_key'] . '.html';
                            $response['result'][$brand][$i]['gender'] = $value['gender'];
                            $response['result'][$brand][$i]['color_family'] = $value['color_family'];
                            $response['result'][$brand][$i]['top_type'] = $value['top_type'];
                            $response['result'][$brand][$i]['bottom_type'] = $value['bottom_type'];
                            $response['result'][$brand][$i]['top_fabric'] = $value['top_fabric'];
                            $response['result'][$brand][$i]['top_length'] = $value['top_length'];
                            $response['result'][$brand][$i]['fabric'] = $value['fabric'];
                            $response['result'][$brand][$i]['type_of_pleat'] = $value['type_of_pleat'];
                            $response['result'][$brand][$i]['shape'] = $value['shape'];
                            $response['result'][$brand][$i]['pattern'] = $value['pattern'];
                            $response['result'][$brand][$i]['fit'] = $value['fit'];
                            $response['result'][$brand][$i]['waist_rise'] = $value['waist_rise'];
                            $response['result'][$brand][$i]['sleeve_length'] = $value['sleeve_length'];
                            $response['result'][$brand][$i]['bottom_fabric'] = $value['bottom_fabric'];
                            $response['result'][$brand][$i]['hemline'] = $value['hemline'];
                            $response['result'][$brand][$i]['cuff'] = $value['cuff'];
                            $response['result'][$brand][$i]['print_or_pattern_type'] = $value['print_or_pattern_type'];
                            $response['result'][$brand][$i]['type'] = $value['type'];
                            $response['result'][$brand][$i]['closure'] = $value['closure'];
                            $response['result'][$brand][$i]['shade'] = $value['shade'];
                            $response['result'][$brand][$i]['sub_category'] = $value['sub_category'];
                            $response['result'][$brand][$i]['hood'] = $value['hood'];
                            $response['result'][$brand][$i]['ply'] = $value['ply'];
                            $response['result'][$brand][$i]['reusable'] = $value['reusable'];
                            $response['result'][$brand][$i]['filtration'] = $value['filtration'];
                            $response['result'][$brand][$i]['fastening'] = $value['fastening'];

                            $response['result'][$brand][$i]['design_styling'] = $value['design_styling'];
                            $response['result'][$brand][$i]['occasion'] = $value['occasion'];
                            $response['result'][$brand][$i]['surface_styling'] = $value['surface_styling'];
                            $response['result'][$brand][$i]['main_trend'] = $value['main_trend'];
                            $response['result'][$brand][$i]['collection_story'] = $value['collection_story'];
                            $response['result'][$brand][$i]['design_story'] = $value['design_story'];
                            $i++;
                        }
                    }
                }
                        return response()->json($response);
            } else {

            $response['response']['success'] = 0;
            $response['response']['success_message'] = '';
            $response['response']['error'] = 1;
            $response['response']['error_message'] = self::RECORD_NOT;

            }
        } catch (\Exception $e) {

            $response['response']['success'] = 0;
            $response['response']['success_message'] = '';
            $response['response']['error'] = 1;
            $response['response']['error_message'] = json_encode($e);
        }
        return response()->json($response);

    }
}
