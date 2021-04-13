<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    //
    protected $connection = 'cart_mysql';
    protected $table='payment';
}
