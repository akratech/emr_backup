<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\EloquentUserProvider;
use App\User;

use Validator;
use DB;
use Mail;
use Hash;
//use Auth;


class UserController extends Controller
{

    /*protected function guard()
    {
       return Auth::guard('api');
    }*/
    
    public function login(Request $request){

        $return = array('status' => 0, 'message' => '', 'data' => array());
        $credentials = [
            "username" => $request->get('username'),
            "password" => $request->get('password'),
            "active" => '1',
            "group_id" => $request->get('group')
        ];        
        if (Auth::guard('web')->attempt($credentials)) {
            $user = Auth::guard('web')->user();
            /*$user->app_api_token = Hash::make(time());*/
            $user->token = Hash::make(time());
            $user->device_platform = $request->has('device_platform') ? $request->get('device_platform') : '';
            $user->android_push_ids = $request->has('android_push_ids') ? $request->get('android_push_ids') : '';
            $user->ios_push_ids = $request->has('ios_push_ids') ? $request->get('ios_push_ids') : '';
            $user->save();            


            $patient = DB::table('demographics')
                ->join('demographics_relate', 'demographics_relate.pid', '=', 'demographics.pid')
                ->select('demographics.pid', 'demographics.firstname', 'demographics.lastname', 'demographics.state', 'demographics.sex', 'demographics.DOB')
                ->where('demographics_relate.practice_id', '=', $user->practice_id)
                ->where(function($query_array1) use ($user) {
                    $query_array1->where('demographics.firstname', '=',  $user->firstname)
                    ->orWhere('demographics.lastname', '=', $user->lastname);
                })
            ->first();

            $ma_user['id'] = $user['id'];
            $ma_user['username'] = $user['username'];
            $ma_user['email'] = $user['email'];
            $ma_user['displayname'] = $user['displayname'];
            $ma_user['firstname'] = $user['firstname'];
            $ma_user['middle'] = $user['middle'];
            $ma_user['title'] = $user['title'];
            $ma_user['group'] = $user['group'] == 2 ? 'Doctor' : 'Patient';
            $ma_user['group_id'] = $user['group_id'];
            $ma_user['token'] = $user['token'];
            $ma_user['practice_id'] = $user['practice_id'];
            $ma_user['pid'] = (isset($patient->pid) && $patient->pid != '') ? $patient->pid : '';
            $ma_user['uid'] = $user['uid'];
            $return['data']['user'] = $ma_user;
            $return['status'] = 1;
            $return['message'] = 'Login Successfully.';
        }
        else{
            $return['message'] = 'Invalid username or password.';
        }
        return  Response::json($return,200,[],JSON_FORCE_OBJECT);
    }
    
    public function logout(Request $request) {
        $user = Auth::guard('web')->user();
        $return = array('status' => 0, 'message' => '', 'data' => array());        
        if ($user) {
            $user->token = null;
            $user->save();
            $return['status'] = 1;            
            $return['message'] = 'Successfully Logout.';
        } else {
            $return['message'] = 'Could not logout';
        }
        return  Response::json($return,200,[],JSON_FORCE_OBJECT);
    }

    public function searchPatient(Request $request) {

        $return = array('status' => 0, 'message' => '', 'data' => array());

        if($request->has('patient_keywords') && $request->get('patient_keywords') != "" && $request->has('practice_id') && $request->get('practice_id') != "") {

            $keywords = $request->get('patient_keywords');

            $patients = DB::table('demographics')
                ->join('demographics_relate', 'demographics_relate.pid', '=', 'demographics.pid')
                ->select('demographics.pid', 'demographics.firstname', 'demographics.lastname', 'demographics.state', 'demographics.sex', 'demographics.DOB')
                ->where('demographics_relate.practice_id', '=', $request->get('practice_id'))
                ->where(function($query_array1) use ($keywords) {
                    $query_array1->where('demographics.lastname', 'LIKE', "%$keywords%")
                    ->orWhere('demographics.firstname', 'LIKE', "%$keywords%")
                    ->orWhere('demographics.pid', 'LIKE', "%$keywords%");
                })->get();

            if ($patients->count() > 0) {                

                $return['status'] = 1;            
                $return['message'] = 'Patients list get successfully';
                $return['data']['patients'] = $patients;

            } else {
                $return['message'] = 'Patients not found';
            }

        }
        return  Response::json($return);
    }

    public function getProviders(Request $request) {

        $return = array('status' => 0, 'message' => '', 'data' => array());

        $providers = DB::table('users')            
            ->select('id', 'username', 'email', 'displayname' ,'firstname', 'lastname')
            ->where('group_id', '=', 2)->where('active', '=', 1)->get();

        if ($providers->count() > 0) {                

            $return['status'] = 1;            
            $return['message'] = 'Providers list get successfully';
            $return['data']['providers'] = $providers;

        } else {
            $return['message'] = 'Providers not found';
        }
        return  Response::json($return,200,[],JSON_FORCE_OBJECT);
    }

    public function getAppointments(Request $request)
    {
        $return = array('status' => 0, 'message' => '', 'data' => array());

        if($request->has('provider_id') && $request->get('provider_id') != '' && $request->has('pid') &&  $request->get('pid') != '') {

            $start_time = time() - 1204800;
            $end_time = time() + 1204800;

            $query = DB::table('schedule')->where('provider_id', '=', $request->get('provider_id'))
                ->where('pid', '=', $request->get('pid'))
                ->whereBetween('start', array($start_time, $end_time))
                ->get();            

            if ($query->count() > 0) {
                foreach ($query as $row) {                    
                    $row->start = date('Y-m-d H:i:s A', $row->start);
                    $row->end = date('Y-m-d H:i:s A', $row->end);
                }

                $return['status'] = 1;
                $return['message'] = 'Appointment get Successfully ';
                $return['data'] = $query;

            } else {
                $return['message'] = 'No any Appointment';
            }
            
        } else {
            $return['message'] = 'Invalid Provider';
        }
        return  Response::json($return,200,[],JSON_FORCE_OBJECT);
        
    }
}