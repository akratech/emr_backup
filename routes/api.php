<?php

use Illuminate\Http\Request;

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


Route::post('/doctor_register','Auth\AuthApiController@doctor')->name('doctor_register');
Route::post('/patient_register','Auth\AuthApiController@patient')->name('patient_register');
