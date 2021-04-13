<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    //

    protected $connection = 'mysql';
    protected $table='pim_product_attribute';


    public function get_attribute_value(){

        return $this->hasMany('App\Models\ProductAttributeValue', 'id_product_attribute','id_product_attribute')->select('pim_product_attribute_value.value','pim_product_attribute_value.id_product_attribute');

    }

}
