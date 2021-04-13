<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
class UsersController extends BaseController
{
    const RECORD_FOUND='Record found';
    //
    
    public function get_token(Request $request){
        $site='my record';
        $token['token'] = Str::random(60);
        return $this->sendResponse($token, self::RECORD_FOUND );
    }

}
