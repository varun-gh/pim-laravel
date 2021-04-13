<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoryData;
use App\Models\CmsPage;
use App\Models\BannerSlider;
use App\Models\ProductCategory;
use App\Models\FlatCatelog;
use App\Models\ProductGallery;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Support\Facades\Validator;

class ApiController extends BaseController
{
    //
    const RECORD_FOUND = 'Record found';
    const RECORD_NOT = 'Record not found';
    const SOMETHING_WRONG='Something Went Wrong';
    const ADMIN_BASE_URL = "https://getketchadmin.getketch.com/";
    




    /**
     * get_menus
     *
     * @param  mixed $request
      * @return \Illuminate\Http\JsonResponse
     */
    public function get_result(Request $request): \Illuminate\Http\JsonResponse{
        // try{
            $validator = Validator::make( $request->all(), [
                'store' => 'required',
                'service'=>'required'
                ] );
            //if condition work when validation failed
            if ( $validator->fails() ) {
                return $this->sendError( 'Validation Error.', $validator->errors() );
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


            $queryResult=array();
            $queryResultNew="";
            $notSavedFilters=array("sku","count","page","no_filter","url_key","store","service","sort_by","sort_dir"," ");
            if ( isset( $_GET ) ) {
                $firstCount = 1;
                foreach ( $_GET as $getKey => $getValue ) {
                    $queryResult[$getKey] = $getValue;
                    if(!in_array($getKey,$notSavedFilters) && !empty($getValue)) {
                        if($getKey == "filter"){
                            $filterArray=explode ("|", str_replace("+"," ",$getValue));
                            foreach ($filterArray as $value) {
                                $valueArray=explode ("~", $value);
                                $queryResultNew .=  "('".$valueArray[0]."','".$valueArray[1]."','".$firstCount."'),";
                            }
                        } else {
                            $queryResultNew .=  "('".$getKey."','".$getValue."','".$firstCount."'),";
                        }
                    }
                }
                if(!empty($queryResultNew)) {
                    $queryResultNew = rtrim($queryResultNew, ", ");
                  //  $this->frequentFilter($queryResultNew);
                }
                
            }
            $response['query']=$queryResult;

            switch ($request->service) {
                case 'menu':
                    return $this->get_menus($request->all(),$response);
                    break;
                case 'banner_slider':
                    return $this->get_banners($request->all(),$response);
                    break;
                case 'cms_page':
                    return $this->get_cms_page($request->all(),$response);
                    break;         
                case 'category':
                    return $this->get_category_data($_GET,$response);
                    break;
                case 'collections':    
                    echo 'get collections';
                    break;
                case 'product':
                    echo 'get products';
                    break;
                case 'shopthelook':
                    echo 'get shop the look';
                    //file_put_contents($file, json_encode($response));
                    break;    
                case 'sizechart':
                    echo 'get size chart';
                    //file_put_contents($file, json_encode($response));
                    break;
                case 'product_variation':
                    echo 'product variation';
                    //file_put_contents($file, json_encode($response));
                    break;    
                case 'recent_views':
                    echo 'recent views';
                    //file_put_contents($file, json_encode($response));
                    break;  
                case 'pincheck':
                    echo 'pin check';
                    //file_put_contents($file, json_encode($response));
                    break; 
                case 'pincode':
                   // $response = servePincodeData( $_GET, $response );
                    //file_put_contents($file, json_encode($response));
                    break; 
                case 'storelocator':
                    echo 'store locator';   
                    break;   
                case 'cart':
                    echo 'cart';
                    break;  
                case 'stock':
                    echo 'stock';    
                    break;
                case 'stockdelta':
                    echo 'stock delta';
                    break;    
                case 'personality_questions': 
                    echo 'personality questions';      
                    break; 
                case 'personality_score': 
                    echo 'personality score';          
                    break;  
                case 'personality_wardrobe': 
                    echo 'personality wardrobe';        
                    break;    
                case 'skudata':
                    echo 'sku data';   
                    break;
                case 'breadcrumsurl':
                    echo 'breadcrums url';   
                    break;    
                case 'wishlist':
                    echo 'wishlist';   
                    // file_put_contents($file, json_encode($response));
                    break;  
                case 'recent_product':
                    echo 'recent products';     
                    break;     
                case 'filter_image_name': 
                    echo 'filter image';     
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
     * @return void
     */
    public function get_menus($request,$response){
        $cats=CategoryData::select('id_category','source_category_id','parent_id','name','url_key AS menu_url_key','position')->where('status','active')->where('include_in_menu','yes')->where('store',$request['store'])->orderBy('position','ASC')->get();
        if(!empty($cats)){
            $response['response']['success'] = 1;
            $response['response']['success_message'] = 'success';
            $childs = array();
            foreach ($cats as &$item )
                $childs[$item['parent_id']][] =& $item;
            unset( $item );
            foreach ( $cats as &$item )
                if ( isset( $childs[$item['source_category_id']] ) )
                    $item['childs'] = $childs[$item['source_category_id']];
            unset( $item );
            array_multisort( array_column( $childs[0], 'position' ), SORT_ASC, $childs[0] );
            $tree = $childs[0];
            $response['result'] = $tree;
            
            return $this->sendResponse($response, self::RECORD_FOUND );
        }
        return $this->sendError(self::RECORD_NOT );

    }
    /**
     * get_cms_page
     *
     * @param  mixed $request
     *  @param  mixed $response
      * @return \Illuminate\Http\JsonResponse
     */
    public function get_cms_page($request,$response){
            if(isset($request['url_key'])){
                $cms_pages=CmsPage::where('status','active')->where('store',$request['store'])->where('url_key',$request['url_key'])->orderBy('position','ASC')->get();
            }else{
            
                $cms_pages=CmsPage::where('status','active')->where('store',$request['store'])->orderBy('position','ASC')->get();
            }
        
            if(!empty($cms_pages)){
                $response['count'] = count( $cms_pages );    

                $response['response']['success'] = 1;
                $response['response']['success_message'] = 'success';
                $response['result'] = $cms_pages;

                return $this->sendResponse($response, self::RECORD_FOUND );
            }
            return $this->sendError(self::RECORD_NOT );
    
    }
    /**
     * get_banners
     *
     * @param  mixed $request
     * @param  mixed $response
      * @return \Illuminate\Http\JsonResponse
     */
    public function get_banners($request,$response): \Illuminate\Http\JsonResponse{
        try{
            
            $banners=BannerSlider::where('status','enable')->where('store',$request['store'])->orderBy('position','ASC')->get();
        
            if(!empty($banners)){
                $response['count'] = count( $banners );    

                $response['response']['success'] = 1;
                $response['response']['success_message'] = 'success';
                $response['result'] = $banners;

                return $this->sendResponse($response, self::RECORD_FOUND );
            }
            return $this->sendError(self::RECORD_NOT );
        }catch(\Exception $e){
            return $this->sendError( self::SOMETHING_WRONG);
        }

    }    
    
    /**
     * get_category_data
     *
     * @param  mixed $queryData
     * @param  mixed $response
     * @return void
     */
    public function get_category_data($queryData,$response): \Illuminate\Http\JsonResponse{
        $filter_query='';
        if (isset($queryData['filter'])  && !empty($queryData['filter'])){
            $filterArray=explode ("|", str_replace("+"," ",$queryData['filter']));
            foreach ($filterArray as $value) {
                $valueArray=explode ("~", $value);
                $newFilterArray[reset($valueArray)][]=end($valueArray);
            }
            foreach ($newFilterArray as $key => $value) {
                $queryArray= array();
                switch ($key) {
                case 'selling_price':
                    foreach ($value as $newValue) {
                    $newValueArray= explode ("to", str_replace(" ","",str_replace("Rs.","",$newValue)));    
                    $queryArray[]  = "( selling_price >= ".min($newValueArray)." AND selling_price <= ".max($newValueArray).")";
                    }
                    $filter_query .=" AND& (".implode(' OR ', $queryArray).")";
                    break;
                case 'size':
                    foreach ($value as $newValue) {
                     //$queryArray[]  = $key." LIKE "."'%[$newValue]%'";
                    $queryArray[]  = $key." LIKE "."'%$newValue%'";
                    }
                    $filter_query .=" AND& ( ".implode(' OR ', $queryArray).")";
                    break;    
                case 'discount':
                    foreach ($value as $newValue) {
                        //$queryArray[]  = $key." LIKE "."'%[$newValue]%'";
                        $queryArray[]  = $key." >= "."'$newValue'";
                    }
                    $filter_query .=" AND& ( ".implode(' OR ', $queryArray).")";
                    break; 
                default:
                    foreach ($value as $newValue) {
                    $queryArray[]  = $key." = "."'$newValue'";
                    }
                    $filter_query .=" AND& ( ".implode(' OR ', $queryArray).")";
                    break;
    
                }
            }
        }
         // echo "<pre>";
    $new_filter_query=explode("AND&",$filter_query);
    //print_r($new_filter_query);
    $t2=array_pop($new_filter_query);
    $t2key=explode(" ",$t2);
    $final_key=$t2key[2];
    $final_filter_query=implode(' AND ', $new_filter_query);

    $filter_query=str_replace("AND&","AND",$filter_query);
    
        if(!$queryData['sort_by'])
        {$queryData['sort_by']='product_position';}
        if(!$queryData['sort_dir'])
        {$queryData['sort_dir']='DESC';}
    
        $filter_attributes_array = array();
       // $response['result']['count'] = 0;
    
        if(isset($queryData['id_category'])){
            
            $records= CategoryData::where('status','active')->where('id_category',$queryData['id_category'])->get();
        }else{
        
            $records= CategoryData::where('status','active')->where('url_key',$queryData['url_key'])->get();
        
        }
        // print_r($records[0]['parent_id']);
        // die;
    
        $response['result'] = $records[0];
        $response['result']['display_category'] =[];
        if($records[0]['parent_id']>0)
        {
            $newresultp= CategoryData::select('name','url_key')->where('status','active')->where('source_category_id',$records[0]['parent_id'])->get();

            $newResultSup= CategoryData::select('name','url_key')->where('status','active')->where('parent_id',$records[0]['parent_id'])->where('id_category','!=',$records[0]['id_category'])->orderBy('position','ASC')->get();


            // $sqlsub = "SELECT name,url_key FROM pim_categories_data WHERE status = 'active' AND parent_id = '" . $newresult[0]['parent_id'] . "' and parent_id not in('1','2','3') and  id_category != '" .  $newresult[0]['id_category'] . "' order by position asc";
            // $sqlResultSup = $conn->query( $sqlsub );
            // $newResultSup = $sqlResultSup->fetch_all( MYSQLI_ASSOC );
            $response['result']['display_category'] =$newResultSup;
        }
    
        if(count($records)>0){
            $response['response']['success'] = 1;
            $response['response']['success_message'] = 'success';
            
            // //\DB::connection('mysql')->enableQueryLog();
            $id_category=$response['result']['id_category'];
            $full_array=FlatCatelog::whereHas('product_category',function($q) use($id_category){
                $q->where('id_catetory',$id_category);
                
            })->where('url_key','!=',$filter_query)
            ->where('stock_status','in-stock')
            ->groupBy('group_id')
            ->orderBy('percentile_availability','DESC')
            ->orderBy('newness','DESC')->get();
            
            $sqlj_new=FlatCatelog::whereHas('product_category',function($q) use($id_category){
                $q->where('id_catetory',$id_category);
                
            })->where('url_key','!=',$filter_query)
            ->where('stock_status','in-stock')
            ->get();
            
            $sqlj2_new=FlatCatelog::whereHas('product_category',function($q) use($id_category){
                $q->where('id_catetory',$id_category);
                
            })->where('url_key','!=',$final_filter_query)
            ->where('stock_status','in-stock')
            ->get();
            
            $full_array_cnt=count($full_array);

            $newness_array = array_slice(array($full_array), 0, round($full_array_cnt*1/4,0,PHP_ROUND_HALF_DOWN));
            $newness = implode("','",array_column($newness_array,'group_id'));
            $restArray= array_diff_assoc(array($full_array),$newness_array);
            $newRestArray = array_column($restArray, 'percentile_availability');
           // echo 'ok';
            if ( count($sqlj_new) > 0 ) {
                $queryData['no_filter']=(isset($queryData['no_filter']))?$queryData['no_filter']:'false';
                if($queryData['page']==1  && $queryData['no_filter']!='true'){  
                    // echo 'ok';
                    // die;

                    $response['result']['count'] = count($full_array);
                    $new_filter = $this->filterGenrate($sqlj_new,$newFilterArray);
                   // $new_filter =array();
                    // print_r($new_filter);
                    // die;
                    // echo  $new_filter;
                    // die;
                   // $new_filter2 =$this->filterGenrate($sqlj2_new,$newFilterArray);
                    //$new_filter2 =array();
                    // foreach ($new_filter2 as $key => $value) {
                    //     //$merge_key=$key;
                    //     foreach ($value['options'] as $key2 => $value2) {
                    //         // echo $key2;
                    //         // print_r($value2['code']);
                    //         if ($value2['code']===$final_key) {
                    //             $merge_key=$key;
                    //             $merge_lable=$value['filter_lable'];
                    //             $merge_value=$value['options'];
                    //             continue;
                    //         }
                    //     }
                    // }
                     // print_r($merge_value);die();

                        // foreach ($new_filter as $key => $value) {
                        //     if ($value['filter_lable']==$merge_lable) {
                        //     $new_filter[$key]['options']=$merge_value;
                        //     }
                        //     // else{
                        //     //     $new_filter[$merge_key]['filter_lable']=$merge_lable;
                        //     //     $new_filter[$merge_key]['options']=$merge_value;
                        //     // }

                        // }
                    }else{
                        $response['result']['count'] = count($full_array);
                        $new_filter= array();
                    }

                    $sorting_attributes=array (
                        0 => 
                        array (
                        'code' => 'discount',
                        'label' => 'Discount',
                        ),
                        1 => 
                        array (
                        'code' => 'price',
                        'label' => 'Price',
                        ),
                        2 => 
                        array (
                        'code' => 'product_position',
                        'label' => 'Popularity',
                        ),
                        3 => 
                        array (
                        'code' => 'newness',
                        'label' => 'Newest',
                        ),
                    );
                    
                    $response['count-test']=count(array_keys($newRestArray, 100)).'-'.$response['result']['count'];
                    if($queryData['sort_by']=='product_position' && $response['result']['count']>16 && count(array_keys($newRestArray, 100))>10)
                    {
                        //echo "string";die();

                        $product_array1=FlatCatelog::whereHas('product_category',function($q) use($id_category){
                            $q->where('id_catetory',$id_category);
                            
                        })->where('url_key','!=',$filter_query)
                        ->where('stock_status','in-stock')
                        ->whereNotIn('group_id',$newness)
                        ->groupBy('group_id')
                        ->orderBy('percentile_availability','DESC')
                        ->orderBy($queryData['sort_by'],$queryData['sort_dir'])
                        ->orderBy('product_position','DESC')
                        ->orderBy('sku','DESC')
                        ->get();

                        $product_array2=FlatCatelog::whereHas('product_category',function($q) use($id_category){
                            $q->where('id_catetory',$id_category);
                            
                        })->where('url_key','!=',$filter_query)
                        ->where('stock_status','in-stock')
                        ->whereIn('group_id',$newness)
                        ->groupBy('group_id')
                        ->get();

            
                        if(count($product_array1) >0 && count($product_array2) > 0)
                            {								
                                $offset=3;
                                foreach ($product_array2 as $value) {
                                array_splice($product_array1,$offset, 0, 'more');	
                                $product_array1[$offset]=$value;
                                $offset=$offset+4;	                	
                                }
                                $product_array=$product_array1;
                            }
                            else
                            {

                                $product_array=FlatCatelog::whereHas('product_category',function($q) use($id_category){
                                    $q->where('id_catetory',$id_category);
                                    
                                })->where('url_key','!=',$filter_query)
                                ->where('stock_status','in-stock')
                                ->groupBy('group_id')
                                ->orderBy('percentile_availability','DESC')
                                ->orderBy($queryData['sort_by'],$queryData['sort_dir'])
                                ->orderBy('product_position','DESC')
                                ->orderBy('sku','DESC')
                                ->paginate(16);


                            }
                    }	
                    elseif($queryData['sort_by']=='product_position')
                    {
                        
                    
                        $product_array=FlatCatelog::whereHas('product_category',function($q) use($id_category){
                            $q->where('id_catetory',$id_category);
                            
                        })->where('url_key','!=',$filter_query)
                        ->where('stock_status','in-stock')
                        ->groupBy('group_id')
                        ->orderBy('percentile_availability','DESC')
                        ->orderBy($queryData['sort_by'],$queryData['sort_dir'])
                        ->orderBy('product_position','DESC')
                        ->orderBy('sku','DESC')
                        ->paginate(16);
                    
                    }    
                    else{
                       // echo "string1";die();

                        $product_array=FlatCatelog::whereHas('product_category',function($q) use($id_category){
                            $q->where('id_catetory',$id_category);
                            
                        })->where('url_key','!=',$filter_query)
                        ->where('stock_status','in-stock')
                        ->groupBy('group_id')
                        ->orderBy('percentile_availability','DESC')
                        ->orderBy($queryData['sort_by'],$queryData['sort_dir'])
                        ->orderBy('product_position','DESC')
                        ->orderBy('sku','DESC')
                        ->paginate(16);


                        //  $sqlk = "SELECT * FROM pim_product_categories INNER JOIN pim_flat_catalog ON pim_flat_catalog.source_product_id=pim_product_categories.id_product WHERE pim_product_categories.id_catetory = '" . $response['result']['id_category'] . "' AND stock_status='in-stock' AND url_key!=''".$filter_query." GROUP BY pim_flat_catalog.group_id  
                        //                 ORDER BY " . $queryData['sort_by'] . " " . $queryData['sort_dir'] . ",percentile_availability DESC, product_position DESC,  sku DESC LIMIT " . ( $queryData['page'] - 1 ) * $queryData['count'] . "," . $queryData['count'];
                        // $sqlResultk = $conn->query( $sqlk );
                        // $product_array = $sqlResultk->fetch_all( MYSQLI_ASSOC );
                    }
                    $j = 0;
                    foreach ( $product_array as $value ) {
                        $product_array[$j]['category'] =$this->serveCategoryname($value['source_product_id']);
                        $product_array[$j]['image'] = self::ADMIN_BASE_URL."product/".str_replace(' ', '',$value['sku'])."/300/".$value['image'];
    
                        $gallery_array=ProductGallery::select('position','image')->where('id_product',$value['id_product'])->orderBy('position','ASC')->get()->toArray();
                        $galleryList = array();
                        foreach($gallery_array as $gkey => $gall_array) {
                            $galleryList[$gkey]['position'] = $gall_array['position'];
                            $galleryList[$gkey]['image'] = self::ADMIN_BASE_URL."product/".str_replace(' ', '',$value['sku'])."/300/".$gall_array['image'];
                            $galleryList[$gkey]['vedio'] = '';
                        }
                        $product_array[$j]['gallery'] = $galleryList;
    
                        $product_child_array=FlatCatelog::select('id_product','sku','configrable_atribute_value','price','selling_price','quantity','stock_status','image','size')->where('group_id',$value['group_id'])->where('stock_status','in-stock')->get();

                        $array_val= $product_child_array->toArray();
                        array_multisort( array_column($array_val, 'configrable_atribute_value' ), SORT_ASC, $array_val);
                       // return $product_child_array;

                        //print_r($product_child_array); exit;
                        $dk=0;

                        
                        foreach ($product_child_array as $key => $value) {
                            // echo $value['size'];
                            // die;
                            //$getProductSize = "SELECT id_product,value FROM pim_product_attribute_value WHERE id_product='".$value['id_product']."' AND id_product_attribute='5'";    
                            //$getProductSizeQuery = $conn->query($getProductSize);    
                            //$getProductSizeRow = $getProductSizeQuery->fetch_all( MYSQLI_ASSOC );  
                            $value['configrable_atribute_value'] = $value['size'];
                            $value['image'] = self::ADMIN_BASE_URL."product/".str_replace(' ', '',$value['sku'])."/300/".$value['image'];
                        
                        switch ($value['configrable_atribute_value']) {
                                case "FS":
                                    $dk=0;
                                    break;
                                case "XS":
                                    $dk=1;
                                    break;
                                case "S":
                                    $dk=3;    
                                    break;
                                case "M":
                                    $dk=4;
                                    break;
                                case "L":
                                    $dk=5;
                                    break;
                                case "XL":
                                    $dk=6;
                                    break;
                                case "XXL":
                                    $dk=7;
                                    break;
                                case "6":
                                    $dk=8;
                                    break;
                                case "8":
                                    $dk=9;
                                    break;
                                case "10":
                                    $dk=10;
                                    break;
                                case "12":
                                    $dk=11;
                                    break;
                                case "14":
                                    $dk=12;
                                    break;
                                case "16":
                                    $dk=13;
                                    break;
                                case "18":
                                    $dk=14;
                                    break;
                                case "WP":
                                    $dk=15;
                                    break;
                                case "WS":
                                    $dk=16;
                                    break;
                                case "WM":
                                    $dk=17;
                                    break;
                                case "WL":
                                    $dk=18;
                                    break;
                                case "WG":
                                    $dk=19;
                                    break;
                                case "WVG":
                                    $dk=20;
                                    break;
                                default:
                                    $dk=$value['configrable_atribute_value'];
                            }
                                $product_array[$j]['variation'][$dk]=$value;
                        }
                        
                        // $product_array[$j]['variation'] = $product_child_array;
    
    
    
                        if($product_array[$j]['price']=='' || $product_array[$j]['price']==0)
                        {
                            $product_array[$j]['price']=$product_array[$j]['variation'][0]['price'];
                            $product_array[$j]['selling_price']=$product_array[$j]['variation'][0]['selling_price'];
                        }
                        $j++;
                    }
                    if($response['result']['meta_keyword']=='')
                    {
                        $response['result']['meta_keyword']='Online Shopping for Women,  Aurelia'; 
                        if($queryData['store']==1){    
                        $response['result']['meta_keyword']='Online Shopping,  Ketch';
                        }
                    }
                    $response['result']['parent_name'] =($newresultp[0]['name'] ? $newresultp[0]['name'] : '');
                    $response['result']['parent_url_key'] =($newresultp[0]['url_key'] ? $newresultp[0]['url_key'] : '');
                    //$response['result']['products'] =
                    $response['result']['products'] =$product_array->getCollection();
                    $response['result']['filters'] =  $new_filter;
                    $response['result']['sort'] = $sorting_attributes;


                }else{

                    $response['response']['success'] = 0;
                    $response['response']['success_message'] = '';
                    $response['response']['error'] = 1;
                    $response['response']['error_message'] = 'no product found';

                }       

        }else {
                $response['response']['success'] = 0;
                $response['response']['success_message'] = '';
                $response['response']['error'] = 1;
                $response['response']['error_message'] = 'no result for category';
        }
        
        
        
        //return $this->sendResponse($response, self::RECORD_FOUND );
             return  response()->json($response);


    }
    public function filterGenrate($result_query,$newFilterArray){
        $product_array =array();
        $j_result=$result_query->toArray();
        $new_filter='';
        $array = implode( ', ', array_column($j_result, 'id_product' ) );
        $new_array=explode(',',$array);
        $sqlc = "SELECT code,label,value FROM pim_product_attribute JOIN pim_product_attribute_value ON pim_product_attribute_value.id_product_attribute=pim_product_attribute.id_product_attribute WHERE is_in_filter='yes' AND id_product IN (" . $array . ") GROUP BY pim_product_attribute_value.value ,pim_product_attribute_value.id_product_attribute";
        $attributes=\DB::connection('mysql')->select($sqlc);

        $filter_attributes=json_decode(json_encode($attributes), true);
        

        // print_r($filter_attributes);
        // die;
       // \DB::connection('mysql')->enableQueryLog(); 
    //    $records= \DB::connection('mysql')->table('pim_product_attribute_value')
    //         ->select('code','label','value')
    //         ->join('pim_product_attribute','pim_product_attribute_value.id_product_attribute','=','pim_product_attribute.id_product_attribute')
    //         ->where('pim_product_attribute.is_in_filter','yes')->whereIn('id_product',$new_array)->groupBy('pim_product_attribute_value.value')->groupBy('pim_product_attribute_value.id_product_attribute')->get()->toArray();
    
    //     echo '<pre>';
    //    print_r($records);
    //    echo count($records);
    //     die;
        // $record= ProductAttributeValue::with(['get_attribute'=>function($q){
        // $q->where('pim_product_attribute.is_in_filter','yes');

        // }])->whereIn('id_product',$new_array)->groupBy('value')->groupBy('id_product_attribute')->get();
        // //print_r(\DB::connection('mysql')->getQueryLog());
        // echo '<pre>';
        // print_r($record);
        // die;
        // echo '<pre>';
        // print_r($record);
        // echo count($record);
        // die;
        // $filter_attributes=ProductAttribute::select('code','label','value')
        // ->join('pim_product_attribute_value','pim_product_attribute_value.id_product_attribute','=','pim_product_attribute.id_product_attribute')
        // ->where('pim_product_attribute.is_in_filter','yes')->whereIn('id_product',$new_array)->groupBy('pim_product_attribute_value.value')->groupBy('pim_product_attribute_value.id_product_attribute')->get()->toArray();
        // echo count($filter_attributes); 
        // die;
        //  echo "<pre>";print_r($filter_attributes);die();
         
         $c = 0;
         $sizeArray=array();
         foreach ( $filter_attributes as $value ) {
           //$value= get_object_vars($val);
             if($value['code']=='size')
             {    
             $newArray=explode(",",$value['value']);    
             foreach ($newArray as $NewValue) {
             if (!in_array($NewValue, $sizeArray)) {
             $filter_attributes_array[$value['label']][preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue)]['code'] = $value['code'];
             $filter_attributes_array[$value['label']][preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue)]['value'] = preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue);
             $filter_attributes_array[$value['label']][preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue)]['value_key'] = preg_replace('/[^A-Za-z0-9\. -]/', '', $NewValue);
             $sizeArray[]=$NewValue;
             $c++;
             }
             }


             }else{    
             if($value['value']!='No'&&$value['value']!=''&&$value['value']!='0.00%'&&$value['value']!='0%'&&$value['value']!='0')  {  
             $filter_attributes_array[$value['label']][$c]['code'] = $value['code'];
             $filter_attributes_array[$value['label']][$c]['value'] = $value['value'];
             $filter_attributes_array[$value['label']][$c]['value_key'] = preg_replace('/[^A-Za-z0-9\. -]/', '_', str_replace(' ', '-', strtolower($value['value'])));
             $c++;
             }}
         }
         
         // for price filter

         $min_price= min(array_column($j_result, 'selling_price'));
         $max_price= max(array_column($j_result, 'selling_price'));
         $add_price=500;

         if($add_price >$min_price )
         {
             $start_price=0;
         }else{
             $start_price=((int)($min_price/$add_price))*$add_price;
         }
         $end_price=((int)(($max_price/$add_price)+1))*$add_price;
         $xk=$start_price;
         $price_range=array();
         $t=0;
         while ($xk <= $end_price) {
             if($t <=1) {
                 $price_range[$t]['code']='selling_price';
                 $price_range[$t]['value']='Rs.'.$xk.' to Rs.'.($xk+$add_price);
                 $price_range[$t]['value_key']=$xk.','.($xk+$add_price);
                 $xk=$xk+$add_price;
             } else {
                 $price_range[$t]['code']='selling_price';
                 $price_range[$t]['value']='Rs.'.$xk.' to Rs.'.($xk+1000);
                 $price_range[$t]['value_key']=$xk.','.($xk+1000);
                 $xk=$xk+1000;
             }
             $t++;
         }
         unset($price_range[$t-1]);
         //$price_range=array(trim($min_price),trim($max_price));
         //echo $t;
         //echo "<pre>";
         //print_r($price_range);die();
         $d=0;
         
         $discount_filter_array=array_unique(array_column($j_result, 'discount'));
         sort($discount_filter_array);
         $df=0;
$discount_send_array = array();
         foreach ($discount_filter_array as  $value) {
             if($value>0){
                 $discount_value = intdiv($value, 10)*10;
                 if($discount_value){
                     if(!in_array($discount_value,$discount_send_array)){
                         $discount_send_array[] = $discount_value;
                         $discount_filter[$df]['code']='discount';
                         $discount_filter[$df]['value']=  $discount_value .'% and Above';
                         $discount_filter[$df]['value_key']=preg_replace('/[^A-Za-z0-9\. -]/', '_', $discount_value);
                         $df++;
                     }
                 }
             }
         }
         if(count($discount_filter)>0){
         $new_filter1[count($filter_attributes_array)]['filter_lable']='Discount';
         $new_filter1[count($filter_attributes_array)]['options']=$discount_filter;
         }

         $filter_attributes_array['Discount'] = $new_filter1[count($filter_attributes_array)]['options'];

         $price_filter[count($filter_attributes_array)]['filter_lable']='Price';
         $price_filter[count($filter_attributes_array)]['options']=$price_range;
         $filter_attributes_array['Price'] = $price_filter[count($filter_attributes_array)]['options'] ;
         //$sizeKey = 1;
          $new_filter=array();
         foreach ($filter_attributes_array as $key => $value) {
             switch ($key) {
                 case "Gender":
                     $d=0;    
                     break;
                 case "Category":
                     $d=1;
                     break;
                 case "Size":
                     $d=2;    
                     break;    
                 case "Brand":
                     $d=3;
                     break;
                 case "Price":
                     $d=4;    
                     break;
                 case "Colour":
                     $d=5;    
                     break;    
                 case "Discount":
                     $d=6;
                     break;  
                 default:
                     $d=7+$d;
             }
           
             if(count($value)>1 || array_key_exists(array_values($value)[0]['code'],$newFilterArray)){    
             $new_filter[$d]['filter_lable']=$key;
             $new_filter[$d]['options']=$value;

             // if($key=="Gender") {
             //     $sizeKey = 2;
             // }
             if($key=="Size") {
                 $sizeLists = $value;
             }
             $d++;}
         }
         $lastindex = count($new_filter);
         //$new_filter[$lastindex]['filter_lable']='Price';
         //$new_filter[$lastindex]['options']=$price_range;
         ksort($new_filter);
         $new_filter = array_values($new_filter);
        //  echo '<pre>';
        //  print_r($new_filter);
        //  die;
        // array_multisort( array_column( $new_filter, 'value' ), SORT_ASC,  array_values($new_filter) ); 
         if(isset($sizeLists)) {
             ksort($sizeLists);
             $size_options = $this->sizeSort($sizeLists);
            //print_r($sizeKey); exit;
             // if($size_options!="") {
             //     $new_filter[$sizeKey]['options']=$size_options;
             // }
             foreach($new_filter as $nsKey => $new_filt) {
                 if($new_filt['filter_lable']=="Size") {
                     if($size_options!="") {
                         $new_filter[$nsKey]['options']=$size_options;
                     }
                 }
                 
             }
         }
    return $new_filter;
    }

public function sizeSort($sizeLists) {
    $dt=0; 
    foreach ($sizeLists as $skey => $sizeList) {
        // echo $value['configrable_atribute_value'];
        switch ($sizeList['value']) {
            case "FS":
                $dt=0;
                break;
            case "XS":
                $dt=1;
                break;
            case "S":
                $dt=3;    
                break;
            case "M":
                $dt=4;
                break;
            case "L":
                $dt=5;
                break;
            case "XL":
                $dt=6;
                break;
            case "XXL":
                $dt=7;
                break;
            case "6":
                $dt=8;
                break;
            case "8":
                $dt=9;
                break;
            case "10":
                $dt=10;
                break;
            case "12":
                $dt=11;
                break;
            case "14":
                $dt=12;
                break;
            case "16":
                $dt=13;
                break;
            case "18":
                $dt=14;
                break;
            case "WP":
                $dt=15;
                break;
            case "WS":
                $dt=16;
                break;
            case "WM":
                $dt=17;
                break;
            case "WL":
                $dt=18;
                break;
            case "WG":
                $dt=19;
                break;
            case "WVG":
                $dt=20;
                break;
            default:
                $dt=20+$dt;
        }
        $size_options[$dt]=$sizeList;
    }
    ksort($size_options);
    return $size_options;
}

    function serveCategoryname($productId)
    {
        $cat_info=ProductCategory::with('get_category')->where('id_product',$productId)->first();
        if($cat_info){
        return  $cat_info->name;
        }
        
        return '';
    
    }

    


}
