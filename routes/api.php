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

Route::post("/api/login", ["as" => "login", "uses" => "Api\UserController@login"]);
Route::post("/api/logout", ["as" => "logout", "uses" => "Api\UserController@logout"]);

Route::group(["prefix" => "api" ,"middleware" => "auth:api"], function() {

	Route::post("/search-patient", ["as" => "searchPatientApi", "uses" => "Api\UserController@searchPatient"]);
	Route::post("/get-providers", ["as" => "getProvidersApi", "uses" => "Api\UserController@getProviders"]);
	Route::post("/get-appointments",["as" => "getAppointmentsApi", "uses" => "Api\UserController@getAppointments"]);

	Route::post('/provider-schedules', ['as' => 'providerSchedulesApi', 'uses' => 'Api\ScheduleController@providerSchedules']);
	Route::post('/update-schedule', ['as' => 'updateScheduleApi', 'uses' => 'Api\ScheduleController@updateSchedule']);
	Route::post('/delete-schedule', ['as' => 'deleteScheduleApi', 'uses' => 'Api\ScheduleController@deleteSchedule']);

});

/*Route::group(["prefix" => "api"], function() {
	Route::post("/search-patient", array("as" => "searchPatientApi", "uses" => "Api\UserController@searchPatient"));
	Route::post("/get-providers", array("as" => "getProvidersApi", "uses" => "Api\UserController@getProviders"));
	Route::post("/get-appointments", array("as" => "getAppointmentsApi", "uses" => "Api\UserController@getAppointments"));
	Route::post('/provider-schedules', ['as' => 'providerSchedulesApi', 'uses' => 'Api\ScheduleController@providerSchedules']);
	Route::post('/update-schedule', ['as' => 'updateScheduleApi', 'uses' => 'Api\ScheduleController@updateSchedule']);
	Route::post('/delete-schedule', ['as' => 'deleteScheduleApi', 'uses' => 'Api\ScheduleController@deleteSchedule']);	
});*/