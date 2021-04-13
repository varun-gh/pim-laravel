<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PromotionCouponList;
use App\Models\PromotionList;
use App\Models\Customer;
use App\Models\PimOrders;
use App\Models\Payment;

class PromotionController extends Controller
{
    //
    const VALID_PHONE='Valid Phone number required';
    const COUPON_NOT_FOUND='Coupon Not Found';
    
    /**
     * list_api
     *
     * @param  mixed $request
     * @return void
     */
    public function list_api(Request $request)
    {
        // echo $_GET['phone'];
        // die;
        $result = PromotionList::with('get_coupon_list')->where('status', 'active')->get();

        $response = array();
        $response['success'] = "0";
        $response['success_message'] = '';
        $i = 0;
        //echo "<pre>";
        //Check Coupon code Exits in our record
        if(count($result)>0){
            foreach ($result as  $value) {
                $today_date = date('Y-m-d', strtotime(date('Y-m-d')));
                // echo $value->from_date;
                // die;
                if ($value->from_date){
                    $from_date  = date('Y-m-d', strtotime($value->from_date));
                }
                if ($value->to_date){
                    $to_date    = date('Y-m-d', strtotime($value->to_date));
                }
                //check coupon expiry
                if ($today_date<$from_date || $today_date > $to_date) {
                    
                    continue;
                } else {
                    
                    //If coupon if not expire the check phone number  exist in request or not 
                    if (isset($_GET['phone']) && !empty($_GET['phone'])) {
                        $mobile_num = $_GET['phone'];
                        //take allow_mobile_no column value
                        $cond = json_decode($value->conditions)->allow_mobiles_no;
                        $operation = ['=', 'is_in'];

                        if (!empty($cond->value)) {

                            if (in_array($cond->operator, $operation)) {
                                 //this section executed when a coupon available for more than one mobile number
                                if ($cond->operator == "is_in") {

                                    $totalMobNum = explode(",", $cond->value);
                                    if (is_array($totalMobNum)) {
                                        // this section executed when requested mobile number available in allow_mobile_no
                                        if (in_array($mobile_num, $totalMobNum)) {
                                            // call a function getCouponUsesByMobile (this function return no of times used by same customer) 
                                            $totaluses = $this->getCouponUsesByMobile($mobile_num, $value->get_coupon_list[0]['coupon_code']);
                                        
                                            if ($totaluses != $value->get_coupon_list[0]['use_per_customer']) {
                                                $response['result'][$i]['promotion_id'] = $value->get_coupon_list[0]['promotion_id'];
                                                $response['result'][$i]['coupon_code'] = $value->get_coupon_list[0]['coupon_code'];
                                                $response['result'][$i]['display_message'] = $value->display_message;
                                                $response['result'][$i]['store'] = $value->store;
                                            }
                                            //$i++;
                                        }
                                    }
                                   //this section executed when a coupon available for single mobile number  
                                } else if ($cond->operator == "=") {

                                    //this section executed when requested mobile exits in allow_mobile_no field
                                    if ($mobile_num == $cond->value) {
                                        // call a function getCouponUsesByMobile (this function return no of times used by same customer) 
                                        $totaluses = $this->getCouponUsesByMobile($mobile_num, $value->get_coupon_list[0]['coupon_code']);

                                        if ($totaluses != $value->get_coupon_list[0]['use_per_customer']) {
                                            $response['result'][$i]['promotion_id'] = $value->get_coupon_list[0]['promotion_id'];
                                            $response['result'][$i]['coupon_code'] = $value->get_coupon_list[0]['coupon_code'];
                                            $response['result'][$i]['display_message'] = $value->display_message;
                                            $response['result'][$i]['store'] = $value->store;
                                        }
                                        //$i++;
                                    }
                                }
                                // print_r($request['sku'].$cond->operator.$cond->value); exit;
                            }
                        } else {

                            if ($value->display_type == "yes") {
                                $response['result'][$i]['promotion_id'] = $value->get_coupon_list[0]['promotion_id'];
                                $response['result'][$i]['coupon_code'] = $value->get_coupon_list[0]['coupon_code'];
                                $response['result'][$i]['display_message'] = $value->display_message;
                                $response['result'][$i]['store'] = $value->store;
                            }
                        }
                    } else {
                        $response['success']='0';
                        $response['success_message']=self::VALID_PHONE;
            
                    }

                    $i++;
                }
            }
        }else{

            $response['success']='0';
            $response['success_message']=self::COUPON_NOT_FOUND;



        }

        return response()->json($response);
    }
    
    /**
     * getCouponUsesByMobile
     *
     * @param  mixed $mobile
     * @param  mixed $coupon_code
     * @return void
     */
    public function getCouponUsesByMobile($mobile, $coupon_code)
    {
        // Get Orders with cod precod and wallet payment mode 
        $payment_mode = array('cod', 'precod', 'wallet');
        $result1 = PimOrders::whereHas('get_customers', function ($query) use ($mobile) {
            $query->where('phone', $mobile);
        })->whereIn('payment_method', $payment_mode)->where('discount_code', $coupon_code)->groupBy('order_increment_id')->get();


        $total_cod = $result1->count(); //cod

        $payment_mode2 = array('razorpay', 'prepayment');
        // Get Orders with razorpay and prepayment 
        $result2 = PimOrders::whereHas('get_payments', function ($query) {
            $query->where('unmappedstatus', 'captured')->where('status', 'captured');
        })->whereHas('get_customers', function ($query) use ($mobile) {
            $query->where('phone', $mobile);
        })->whereIn('payment_method', $payment_mode2)->where('discount_code', $coupon_code)->groupBy('order_increment_id')->get();
        $total_razorpay = count($result2);

        // Get Orders with gift payment mode 
        $result3 = PimOrders::whereHas('get_customers', function ($query) use ($mobile) {
            $query->where('phone', $mobile);
        })->where('payment_method', 'gift')->where('discount_code', $coupon_code)->groupBy('order_increment_id')->get();
        $total_gift = count($result3);

        //return total orders by current user with applied coupon code
        return $total_customer_uses = $total_cod + $total_razorpay + $total_gift;
    }


}
