<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ValidateCouponController;
use App\Http\Controllers\SearchTapController;
use App\Http\Controllers\PimController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//Route::post('register', [RegisterController::class, 'register']);


Route::get('get_result',[ApiController::class, 'get_result']);
Route::get('list-api',[PromotionController::class,'list_api']);
Route::post('validate-coupon',[ValidateCouponController::class,'validate_coupon']);

Route::get('delete-search-tap',[SearchTapController::class,'delete']);
Route::get('category-sync',[SearchTapController::class,'category_sync']);
Route::get('full-sync',[SearchTapController::class,'full_sync']);

Route::get('breadcrumbs',[PimController::class,'breadcrumbs']);
Route::get('re-index',[PimController::class,'re_index']);

