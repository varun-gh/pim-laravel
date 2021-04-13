<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PromotionCouponList;
use App\Models\PromotionList;
use App\Models\Customer;
use App\Models\PimOrders;
use App\Models\Payment;

class ValidateCouponController extends Controller
{
    //
    const COUPON_CODE_NOT_CORRECT='Coupon code is not correct';
    const IN_CORRECT_METHOD='Please use post method';
    const NOT_APPLICABLE_FOR_YOU='This coupon is not applicable for you';
    const ALREADY_USED='Coupon is already used.';
    const NOT_APPLICABLE='Coupon is not applicable';
    const VALID_BUT_NOT_APPLICABLE='Coupon is valid but not applicable';
    const VALID_COUPON='Coupon is valid';
    const DISCOUNT_APPLIED='Discount Applied';

    public function validate_coupon(Request $request){
        $response                    = array();
        $response['success']         = 0;
        $response['success_message'] = '';
        $response['success_discount_amount'] = '';
        $response['success_total_amount'] = '';
        $response['success_discount_skus'] =  array();
        $response['error']           = 0;
        $response['error_message']   = '';
        $response['promotion_id']   = '';
        $response['promotion_name'] = '';
        $response['promotion_description'] = '';
        $response['display_message'] = '';
        $price_list='';
        $i                           = 0;
        $cart_qty=$subtotal=0;

        // //return response()->json($response);
        file_put_contents('cache/log/promotionlog_'.date("j.n.Y").'.log', "==========================Input Details=============".date('m/d/Y h:i:s a', time()), FILE_APPEND);
        file_put_contents('cache/log/promotionlog_'.date("j.n.Y").'.log', $request->all(), FILE_APPEND);

        /**
         * this section executed when coupon_code 
         * available in request          * 
         */
        if (isset($request) && $request['coupon_code']) {

            $today=date('Y-m-d');
        
            $coupon_code=$request['coupon_code'];
            /**
             * this section returns coupon information
            */
        
            $result=PromotionList::withAndWhereHas('get_coupon_list',function($q) use($coupon_code){
                $q->where('coupon_code',$coupon_code);

            })->first();
            /**
             * This section executes and return error message 
             * when requested coupon 
             * not available in our record
            */
            if (!$result) {
                $response['error']         = 1;
		        //$response['error_message'] = 'Coupon code is not correct';
                $response['error_message'] = self::COUPON_CODE_NOT_CORRECT;
                echo json_encode($response);
                exit();
            }

        }else{
            /**
             * This section executes and return error message 
             * when coupon_code not available in request parameter 
            */
            $response['error']         = 1;
            $response['error_message'] = self::IN_CORRECT_METHOD;
            echo json_encode($response);
            exit();
        }
    
        /**
         * This section executes 
         * when coupon_code is available in in our records 
         * and get coupon uses conditions 
         * decode json value in array 
        */
        $conditions = json_decode($result->conditions, true);
    
        $response['promotion_id']   = $result->get_coupon_list->promotion_id;
        $response['promotion_name']   = $result->promotion_name;
        $response['promotion_description']   = $result->description;
        // Get current date
        $today_date = date('Y-m-d', strtotime(date('Y-m-d')));
        // Get coupon applicable from date
        if($result->from_date)
        $from_date  = date('Y-m-d', strtotime($result->from_date));
        // Get coupon applicable to date
        if($result->to_date)
        $to_date    = date('Y-m-d', strtotime($result->to_date));
        

        // check whether the coupon is active or not, applicable on current store and check expiry of coupons
        if ($today_date < $from_date || $today_date > $to_date || $result->status != 'active' ) {
            $response['error']         = 1;
            $response['error_message'] = self::COUPON_CODE_NOT_CORRECT;
            echo json_encode($response);
            exit();
        }
    
        // validate Mobile Number 
        if($conditions['allow_mobiles_no']['value']){
        
            if(isset($request->mobile_number)){
                /**
                 * This section executes when mobile_number 
                 * is in requested parameter
                */
                $key = 'allow_mobiles_no';
                /**
                 * match requested mobile_number 
                 * is available in coupon conditions or not
                */
                $matchResult = $this->compare_keys($key, $request->mobile_number, $conditions );	
                //$matchResult=1;
                /**
                 * if requested mobile_number not available 
                 * in coupon condition 
                 * then this section returns error message 
                 * not applicable
                */
                if($matchResult===0){
                
                    $response['error']         = 1;
                    $response['error_message'] = self::NOT_APPLICABLE_FOR_YOU;
                    echo json_encode($response);
                    exit();
                    /**
                     * if requested mobile_number not available 
                     * in coupon condition 
                     * then this section returns error message 
                     * not applicable
                    */
                } else {
                    
                    $mobile=$request->mobile_number;
                    $payment_mode = array('cod', 'precod', 'wallet');
                    $result1 = PimOrders::whereHas('get_customers', function ($query) use ($mobile) {
                        $query->where('phone', $mobile);
                    })->whereIn('payment_method', $payment_mode)->where('discount_code', $coupon_code)->groupBy('order_increment_id')->get();
            
            
                    $total_cod = $result1->count(); //cod
            
                    $payment_mode2 = array('razorpay', 'prepayment');
            
                    $result2 = PimOrders::whereHas('get_payments', function ($query) {
                        $query->where('unmappedstatus', 'captured')->where('status', 'captured');
                    })->whereHas('get_customers', function ($query) use ($mobile) {
                        $query->where('phone', $mobile);
                    })->whereIn('payment_method', $payment_mode2)->where('discount_code', $coupon_code)->groupBy('order_increment_id')->get();
                    $total_razorpay = count($result2);
            
            
                    $result3 = PimOrders::whereHas('get_customers', function ($query) use ($mobile) {
                        $query->where('phone', $mobile);
                    })->where('payment_method', 'gift')->where('discount_code', $coupon_code)->groupBy('order_increment_id')->get();
                    $total_gift = count($result3);
            
                    $total_customer_uses = $total_cod + $total_razorpay + $total_gift;
                    // echo $total_customer_uses;
                    // die;

                    if ($total_customer_uses > 0) {
                        if($total_customer_uses>=$result->get_coupon_list->use_per_customer){
                        
                            $response['error']         = 1;
                            $response['error_message'] = $result->error_message!="" ? $result->error_message : 'Coupon is already used you.';
                                            
                            echo json_encode($response);
                            exit();
                        }
                    } 
                    //$conn->close();
                }
            } else {
                $response['error']         = 1;
                $response['error_message'] = self::NOT_APPLICABLE_FOR_YOU;
                echo json_encode($response);
                exit();
            }
        }


        // Check if coupon is not exeeded it's used limit
        if ($result->get_coupon_list->use_per_coupon) {
            // echo 'ok';
            // die;
            if ($result->get_coupon_list->time_used >= $result->get_coupon_list->use_per_coupon) {
                $response['error']         = 1;
                $response['error_message'] = self::ALREADY_USED;
                echo json_encode($response);
                exit();
            }
        }

        // filter the applicable items
        if(isset($request->products)){
            foreach ($request->products as $pkey => $product) {
                foreach ($product as $key => $value) {
                    $matchResult = $this->compare_keys($key, $value, $conditions );	
                    if($matchResult===0)
                        { unset($request->products[$pkey] );}
                }	
            }
    
            if(count($request->products)<1){
                $response['error']         = 1;
                $response['error_message'] = self::NOT_APPLICABLE;
                echo json_encode($response);
                exit();
            }
    
            array_multisort(array_column($request->products, 'final_price'), SORT_ASC, $request->products);
    
    
            $price_list=array();
            $sku_list=array();
            $discount_on=array();
            foreach ($request->products as $value) {
                $ProductPrice = $value['discounted'] == 'yes' ? $value['selling_price'] : $value['final_price'];
                $subtotal=$subtotal+$ProductPrice*$value['qty'];
                $cart_qty=$cart_qty+$value['qty'];
                for ($x = 1; $x <= $value['qty']; $x++) {
                    $price_list[]=$ProductPrice;
                    $sku_list[]=$value['sku'];
                    $discout_on[$value['sku']]=$value['discounted'] == 'yes' ? 'selling_price' : 'final_price';
                }
            }
    
            // echo $subtotal;
            // die;
            ////////////////////////////////////////////////////////////////////////////////////
            $response['success_total_amount'] = array_sum($price_list);
            if($this->compare_keys('cart_qty', $cart_qty, $conditions )===1 && $this->compare_keys('subtotal', $subtotal, $conditions )===1)
            {
    
                switch ($result->discount_type) {
                    case 'fixed':
                        $response['success_discount_amount']=$result->amount;
                        break;
                    case 'percentage':
                        $response['success_discount_amount']= round($result->amount*$subtotal/100,2);
                        break;
    
                    case 'free_item':
                    if(($result->buy_qty+$result->free_qty)<=$cart_qty)
                        {
                            $j=0;
                            foreach ($price_list as $price_value) {
                                if ( $result->free_qty == $j ) {
                                    break;
                                }
                                $response['success_discount_amount']=$response['success_discount_amount']+$price_value;
                                $j++;
                            }
                            $total_discount_qty=$result->buy_qty+$result->free_qty;
                            $sku_list = array_slice($sku_list,0,$total_discount_qty);
    
                            $price_list = array_slice($price_list,0,$total_discount_qty);
    
                            $response['success_total_amount'] = array_sum($price_list);
    
    
                        }
                        break;		
                    
                    default:
                        # code...
                        break;
                }
                //print_r(array_count_values($sku_list));die();
                $i=0;
                foreach (array_count_values($sku_list) as $key => $value) {
                    $response['success_discount_skus'][$i]['sku']=$key;
                    $response['success_discount_skus'][$i]['vqty']=$value;
                    $response['success_discount_skus'][$i]['discout_on']=$discout_on[$key];
                    $i++;
                }
    
                //$response['success_discount_skus'] = $sku_list;
                $response['success_discount_amount']=min($response['success_discount_amount'],$subtotal);
                $response['success']         = 1;
                $response['success_message'] = self::VALID_COUPON;
                $response['display_message'] = self::DISCOUNT_APPLIED;
                echo json_encode($response);
                // print_r($response);
                exit();
    
            }
            else{
                $response['error']         = 1;
                $response['error_message'] = self::VALID_BUT_NOT_APPLICABLE;
            }
        }
        
   

        echo json_encode($response);
        file_put_contents('cache/log/promotion_log_'.date("j.n.Y").'.log', json_encode($response), FILE_APPEND);
    }
        
    /**
     * compare_keys
     *
     * @param  mixed $key
     * @param  mixed $value
     * @param  mixed $conditions
     * @return void
     */
    public function compare_keys($key, $value, $conditions ){
        if(($key=='discounted' && $value=='no') || ($key=='discounted' && $value=='yes')|| (!$conditions[$key]['value']))
            return 1;
        if(! array_key_exists($key, $conditions)){
            return false;
        }
        
        $condition_operator = str_replace(' ', '',$conditions[$key]['operator']);
        $condition_value = $conditions[$key]['value']; 
    
        switch ($condition_operator) {
            case '>':
                $result = ($value > $condition_value) ? 1 : 0 ;
                return $result;
                break;
            
            case '<':
                $result = ($value < $condition_value) ? 1 : 0 ;
                return $result;
                break;
    
            case '=':
                $result = ($value == $condition_value) ? 1 : 0 ;
                return $result;
                break;
            case '!=':
                $result = ($value != $condition_value) ? 1 : 0 ;
                return $result;
                break;
            case 'is_in':
                $srcString = explode(',',$condition_value);
                $subset = explode(',',$value);
                $c = array_intersect($subset, $srcString);
                $result = (count($c) > 0) ? 1 : 0 ;
                return $result;
                break;	
            case 'not_in':
                $srcString = explode(',',$condition_value);
                $subset = explode(',',$value);
                $c = array_intersect($subset, $srcString);
                $result = (count($c) < 1) ? 1 : 0 ;
                return $result;
                break;			
    
            default:
                //return "no conditions matched";
                break;
        }
    }
        


}
