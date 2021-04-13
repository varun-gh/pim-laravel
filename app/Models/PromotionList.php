<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionList extends Model
{
    //
    protected $connection = 'cart_mysql';
    protected $table='promotion_list';

    public function get_coupon_list(){

        return $this->hasOne('App\Models\PromotionCouponList', 'promotion_id','id');

    }

    public function scopeWithAndWhereHas($query, $relation, $constraint){
        return $query->whereHas($relation, $constraint)
                ->with([$relation => $constraint]);
    }
}
