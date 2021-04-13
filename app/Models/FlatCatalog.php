<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlatCatalog extends Model
{
    //
    protected $connection = 'mysql';
    protected $table='pim_flat_catalog';
    public function product_category(){

        return $this->hasOne('App\Models\ProductCategory', 'id_product','source_product_id');

    }

    public function scopeWithAndWhereHas($query, $relation, $constraint){
        return $query->whereHas($relation, $constraint)
                ->with([$relation => $constraint]);
    }
    
}
