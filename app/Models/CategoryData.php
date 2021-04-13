<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryData extends Model
{
    //
    protected $connection = 'mysql';
    protected $table='pim_categories_data';

    
    public function get_product_category(){

        return $this->hasOne('App\Models\ProductCategory', 'id_catetory','id_category');

    }

    public function scopeWithAndWhereHas($query, $relation, $constraint){
        return $query->whereHas($relation, $constraint)
                ->with([$relation => $constraint]);
    }

}
