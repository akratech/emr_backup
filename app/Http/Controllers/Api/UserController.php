<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\User;


use Validator;
use DB;
use Mail;
use Hash;
//use Auth;


class UserController extends Controller {

    use AuthenticatesUsers;

    public function login(Request $request){

        $return = array('status' => 0, 'message' => '', 'data' => array());
        $credentials = [
            "username" => $request->get('username'),
            "password" => $request->get('password'),            
            "group_id" => $request->get('group'),
            "active" => '1'
        ];        
        if ($this->guard('api')->attempt($credentials)) {

            $user = $this->guard('api')->user();
            $group_id = $request->get('group');

            $user->api_token = $user->token = Hash::make(time());
            $user->device_platform = $request->has('device_platform') ? $request->get('device_platform') : '';
            $user->android_push_ids = $request->has('android_push_ids') ? $request->get('android_push_ids') : '';
            $user->ios_push_ids = $request->has('ios_push_ids') ? $request->get('ios_push_ids') : '';
            $user->save();

            $patient = DB::table('demographics')
            ->join('demographics_relate', 'demographics_relate.pid', '=', 'demographics.pid')
            ->select('demographics.pid', 'demographics.firstname', 'demographics.lastname', 'demographics.state', 'demographics.sex', 'demographics.DOB', 'demographics.date')
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
            $ma_user['group_id'] = $user['group_id'];
            $ma_user['group'] = $user['group'] == 2 ? 'Doctor' : 'Patient';            
            $ma_user['api_token'] = $user['api_token'];
            $ma_user['practice_id'] = $user['practice_id'];
            $ma_user['pid'] = (isset($patient->pid) && $patient->pid != '') ? $patient->pid : '';
            $ma_user['date'] = (isset($patient->date) && $patient->date != '') ? $patient->date : date('d-M-Y');
            $ma_user['uid'] = (isset($user['uid']) && $user['uid'] != '') ? $user['uid']: '';;
            if($group_id == 2){
                $practiceinfo = DB::table('practiceinfo')->where('practice_id', '=', $user['practice_id'])->first();
                $providers = DB::table('providers')->where('id', '=', $user['id'])->first();
                $practiceinfo = json_decode(json_encode($practiceinfo), true);
                $ma_user['practiceinfo']["sun_o"] = $practiceinfo['sun_o'];
                $ma_user['practiceinfo']["sun_c"] = $practiceinfo['sun_c'];
                $ma_user['practiceinfo']["mon_o"] = $practiceinfo['mon_o'];
                $ma_user['practiceinfo']["mon_c"] = $practiceinfo['mon_c'];
                $ma_user['practiceinfo']["tue_o"] = $practiceinfo['tue_o'];
                $ma_user['practiceinfo']["tue_c"] = $practiceinfo['tue_c'];
                $ma_user['practiceinfo']["wed_o"] = $practiceinfo['wed_o'];
                $ma_user['practiceinfo']["wed_c"] = $practiceinfo['wed_c'];
                $ma_user['practiceinfo']["thu_o"] = $practiceinfo['thu_o'];
                $ma_user['practiceinfo']["thu_c"] = $practiceinfo['thu_c'];
                $ma_user['practiceinfo']["fri_o"] = $practiceinfo['fri_o'];
                $ma_user['practiceinfo']["fri_c"] = $practiceinfo['fri_c'];
                $ma_user['practiceinfo']["sat_o"] = $practiceinfo['sat_o'];
                $ma_user['practiceinfo']["sat_c"] = $practiceinfo['sat_c'];
                $ma_user['practiceinfo']["minTime"] = $practiceinfo['minTime'];
                $ma_user['practiceinfo']["maxTime"] = $practiceinfo['maxTime'];
                $ma_user['practiceinfo']["weekends"] = $practiceinfo['weekends'];
                $ma_user['practiceinfo']["timezone"] = $practiceinfo['timezone'];
                
                // provider info
                $providers = json_decode(json_encode($providers), true);
                $ma_user['practiceinfo']["license"] = $providers['license'];
                $ma_user['practiceinfo']["license_state"] = $providers['license_state'];
                $ma_user['practiceinfo']["npi_number"] = $providers['npi'];
                $ma_user['practiceinfo']["dea_number"] = $providers['dea'];
                $ma_user['practiceinfo']["medicare_number"] = $providers['medicare'];
                $ma_user['practiceinfo']["specialty"] = $providers['specialty'];
                $ma_user['practiceinfo']["tax_id_number"] = $providers['tax_id'];
                $ma_user['practiceinfo']["increment_for_schedule_minuntes"] = $providers['schedule_increment'];
                $ma_user['practiceinfo']["timeslotsperhour"] = $providers['timeslotsperhour'];
                
            }

            $return['status'] = 1;
            $return['message'] = 'Login Successfully.';
            $return['data']['user'] = $ma_user;
            
        }
        else{
            $return['message'] = 'Invalid username or password.';
        }
        return  Response::json($return);
    }

    public function logout(Request $request) {
        /*
        Auth::guard('api')->user(); // instance of the logged user
        Auth::guard('api')->check(); // if a user is authenticated
        Auth::guard('api')->id(); // the id of the authenticated user
        */

        $return = array('status' => 0, 'message' => 'Already logged out.', 'data' => array());

        $user = Auth::guard('api')->user();        
        if ($user) {
            $user->token = null;
            $user->api_token = null;
            $user->save();        

            $return['status'] = 1;
            $return['message'] = 'Successfully Logout.';
        }        
        return  Response::json($return);
    }

    public function searchPatient(Request $request) {

        $user = Auth::guard('api')->user();

        $return = array('status' => 0, 'message' => '', 'data' => array());

        
        $keywords = $request->get('patient_keywords');

        $patients = DB::table('demographics')
        ->join('demographics_relate', 'demographics_relate.pid', '=', 'demographics.pid')
        ->select('demographics.pid', 'demographics.firstname', 'demographics.lastname', 'demographics.state', 'demographics.sex', 'demographics.DOB')
        ->where('demographics_relate.practice_id', '=', $user->practice_id);

        if($keywords) {
            $patients = $patients->where(function($query_array1) use ($keywords) {
                $query_array1->where('demographics.lastname', 'LIKE', "%$keywords%")
                ->orWhere('demographics.firstname', 'LIKE', "%$keywords%")
                ->orWhere('demographics.pid', 'LIKE', "%$keywords%");
            });
        }
        $patients = $patients->get();

        if ($patients->count() > 0) {                

            foreach($patients as $patient) {
                if($patient->sex == 'm') { 
                    $patient->sex = "Male" ;
                }
                if($patient->sex == 'f') { 
                    $patient->sex = "Female"; 
                }
                $patient->DOB = date('d-M-Y',strtotime($patient->DOB));
                $patient->title = $patient->lastname.', '.$patient->firstname.' (DOB: '.date('d-M-Y',strtotime($patient->DOB)).') (ID: '.$patient->pid.')';
            }

            $return['status'] = 1;            
            $return['message'] = 'Patients list get successfully';
            $return['data']['patients'] = $patients;

        } else {
            $return['message'] = 'Patients not found';
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
        return  Response::json($return);
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
                    $row->start = date('d-M-Y H:i:s A', $row->start);                    
                    $row->end = date('d-M-Y H:i:s A', $row->end);
                    $row->start_date = date('d-M-Y', strtotime($row->start));
                    $row->start_time = date('H:i:s A', strtotime($row->start));
                    $row->end_date = date('d-M-Y', strtotime($row->end));
                    $row->end_time = date('H:i:s A', strtotime($row->end));
                    $row->timestamp = date('d-M-Y H:i:s A', strtotime($row->timestamp));
                }

                $return['status'] = 1;
                $return['message'] = 'Appointment get Successfully ';
                $return['data']['appointments'] = $query;

            } else {
                $return['message'] = 'No any Appointment';
            }

        } else {
            $return['message'] = 'Invalid Provider';
        }
        return  Response::json($return);

    }
}