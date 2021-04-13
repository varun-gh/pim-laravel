<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PimOrders extends Model
{
    //
    protected $connection = 'cart_mysql';
    protected $table='pim_orders';

    public function get_customers(){

        return $this->hasOne('App\Models\Customer', 'id','customer_id');

    }
    public function get_payments(){

        return $this->hasMany('App\Models\Payment', 'txnid','transaction_id');

    }


}
