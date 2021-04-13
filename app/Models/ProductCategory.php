<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    //
    protected $connection = 'mysql';
    protected $table='pim_product_categories';

    public function flat_catelog(){

        return $this->hasOne('App\Models\FlatCatalog', 'source_product_id','id_product');

    }

    public function get_category(){

        return $this->belongsTo('App\Models\CategoryData', 'id_category','id_catetory')->select('id_category');

    }

}
