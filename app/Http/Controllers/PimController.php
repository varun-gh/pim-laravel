<?php

namespace App\Http\Controllers;
ini_set('max_execution_time', '3600');
ini_set('memory_limit', '2048M');


use Illuminate\Http\Request;
use App\Models\Product;
use App\Jobs\Breadcrumbs;
use App\Models\CategoryData;
use Illuminate\Support\Facades\DB;

class PimController extends Controller
{
    const SUCCESS_MSG = 'success';
    const RECORD_FOUND = 'Record found';
    const RECORD_NOT = 'Record not found';
    const SOMETHING_WRONG = 'Something Went Wrong';

    //
    public function breadcrumbs(Request $request){
        try{
            $response = array();
            $response['response']['success'] = "0";
            $response['response']['success_message'] = '';
            $response['response']['error'] = "0";
            $response['response']['error_message'] = '';
            $response['query'] = '';
            $response['result'] = '';
            // dispatch(new Breadcrumbs());
            // return 'ok';
            $result= Product::all()->pluck('id_product');
            // print_r($result);
            // die;
            if(count($result)>0){
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::SUCCESS_MSG;

                foreach($result as $key=>$value){
                
                    $newresult=CategoryData::WhereHas('get_product_category',function($q) use($value){  
                        $q->where('id_product',$value);
                    })->select('name','url_key')->where('include_in_breadcrumb','yes')->get()->toArray();
                    $data = json_encode($newresult); 
                    // print_r($data);
                    // die;
                    Product::where('id_product',$value)->update(["breadcrumb"=>$data]);
                }
                return response()->json($response);

            }else{
                $response['response']['success'] = 1;
                $response['response']['success_message'] = self::RECORD_NOT;
                return response()->json($response);
            }

            


        }catch(\Exception $e){
            $response['response']['success'] = 0;
            $response['response']['success_message'] = self::SOMETHING_WRONG;
            return response()->json($response);

        }
    
    }

    public function re_index(Request $request){
        // echo 'ok';
        // die;

        // $sql1 =  "update pim_product T1
        // join (
        // select `sku`,attribute_group_id,
        // count(case stock_status when 'in-stock' then 1 else null end)*100/count(stock_status) percentile_availability,
        // ( CASE WHEN selling_price_from_date <= CURRENT_DATE() && selling_price_to_date >= CURRENT_DATE() THEN selling_price ELSE price END) AS `selling_price`,

        // (ROUND((price-( CASE WHEN selling_price_from_date <= CURRENT_DATE() && selling_price_to_date >= CURRENT_DATE() THEN selling_price ELSE price END))*100/price)) AS discount

        // from pim_product
        // group by `attribute_group_id`
        // ) T2 on T1.`attribute_group_id` = T2.`attribute_group_id`    
        // set T1.percentile_availability = T2.percentile_availability,T1.final_price = T2.selling_price,T1.discount = T2.discount
        // ";

        //    $olr=array(
        //     [sku] => 11110001439116,
        //     [attribute_group_id] => BCSH013084,
        //     [stock_status] => 1,
        //     [stock_cnt] => 6,
        //     [product_price] => 1699,
        //     [discount] => 0,
        //     [id_products] => 77762,77763,77764,77765,77766,77767
        //    );
        
        $query=Product::query();
        $query->select('sku','attribute_group_id',DB::raw("
        (CASE WHEN stock_status THEN 1 ELSE null END) stock_status"),DB::raw('count(stock_status) stock_cnt'),
        DB::raw('(CASE WHEN selling_price_from_date <= CURRENT_DATE() &&  selling_price_to_date >= CURRENT_DATE() THEN selling_price ELSE price END) AS product_price'),
        DB::raw('(ROUND((price-( CASE WHEN selling_price_from_date <= CURRENT_DATE() && selling_price_to_date >= CURRENT_DATE() THEN selling_price ELSE price END))*100/price)) AS discount'),
        DB::raw("GROUP_CONCAT(id_product) as id_products")
        
        );
        $records=   $query->groupBy('attribute_group_id')->get()->toArray();
        
        foreach($records as $list){
        
            if($list['sku']!='' && $list['attribute_group_id']!='' ){
                $availability=$list['stock_status']*100/$list['stock_cnt'];
                Product::where('attribute_group_id',$list['attribute_group_id'])
                ->update(['percentile_availability'=>$availability,'final_price'=>$list['product_price'],'discount'=>$list['discount']]);
                
            }

        }
        echo 'updated';
       // print_r($records);

                    
        die;
            


        
    



    }



}
;