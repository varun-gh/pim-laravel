<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    //
    protected $connection = 'mysql';
    protected $table='pim_product_attribute_value';

    public function get_attribute(){

        return $this->belongsTo('App\Models\ProductAttribute', 'id_product_attribute','id_product_attribute')->select('code','label','is_in_filter','id_product_attribute');

    }

    public function scopeWithAndWhereHas($query, $relation, $constraint){
        return $query->whereHas($relation, $constraint)
                ->with([$relation => $constraint]);
    }

}
