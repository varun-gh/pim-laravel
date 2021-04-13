<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $connection = 'mysql';
    protected $table='pim_product';
    protected $primaryKey = 'id_product';


}

