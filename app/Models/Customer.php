<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    //
    protected $connection = 'cart_mysql';
    protected $table='customer';
}
