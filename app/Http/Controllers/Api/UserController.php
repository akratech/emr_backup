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

use App\Helper\FunctionUtils;

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
            $ma_user['uid'] = (isset($user['uid']) && $user['uid'] != '') ? $user['uid']: '';

            $patient = DB::table('demographics')            
            ->select('demographics.pid','demographics.firstname','demographics.lastname','demographics.middle','demographics.title','demographics.sex','demographics.DOB','demographics.email','demographics.address', 'demographics.city','demographics.state','demographics.zip','demographics.language','demographics.active','demographics.photo','vitals.weight','vitals.height','vitals.BMI','vitals.bp_systolic','vitals.bp_diastolic','vitals.bp_position','vitals.pulse','vitals.respirations')
            ->join('demographics_relate', 'demographics_relate.pid', '=', 'demographics.pid')
            ->leftjoin('vitals', 'vitals.pid', '=', 'demographics.pid')
            ->where('demographics_relate.practice_id', '=', $user->practice_id)
            ->where(function($query_array1) use ($user) {
                $query_array1->where('demographics.firstname', '=',  $user->firstname)
                ->orWhere('demographics.lastname', '=', $user->lastname);
            })
            ->first();

            $ma_user['pid'] = (isset($patient->pid) && $patient->pid != '') ? $patient->pid : '';
            $ma_user['date'] = (isset($patient->date) && $patient->date != '') ? date('d-M-Y H:i:s A',strtotime($patient->date)) : date('d-M-Y H:i:s A');

            $ma_user['created_at'] = (isset($user['created_at']) && $user['created_at'] != '') ? date('d-M-Y',strtotime($user['created_at'])) : '';

            if($group_id == 100){
                $ma_user['sex'] = (isset($patient->sex) && $patient->sex != '') ?  ($patient->sex == 'm') ? 'Male' : ($patient->sex == 'f') ? 'Female' : '' : '';
                $ma_user['DOB'] = (isset($patient->DOB) && $patient->DOB != '') ? date('d-M-Y',strtotime($patient->DOB)) : '';

                // Patient Info
                $title = '';
                if(isset($patient->lastname) && isset($patient->firstname) && isset($patient->DOB) && isset($patient->pid) && $patient->lastname != '' && $patient->firstname != '' && $patient->DOB != '' && $patient->pid != '') {
                    $title = $patient->lastname.', '.$patient->firstname.' (DOB: '.date('d-M-Y',strtotime($patient->DOB)).') (ID: '.$patient->pid.')';
                }
                $ma_user['patientinfo']['title'] = $title;
                $ma_user['patientinfo']['address'] = (isset($patient->address) && $patient->address != '') ? $patient->address : '';
                $ma_user['patientinfo']['city'] = (isset($patient->city) && $patient->city != '') ? $patient->city : '';
                $ma_user['patientinfo']['state'] = (isset($patient->state) && $patient->state != '') ? $patient->state : '';
                $ma_user['patientinfo']['zip'] = (isset($patient->zip) && $patient->zip != '') ? $patient->zip : '';
                $ma_user['patientinfo']['language'] = (isset($patient->language) && $patient->language != '') ? $patient->language : '';
                $ma_user['patientinfo']['photo'] = (isset($patient->photo) && $patient->photo != '') ? $patient->photo : '';
                $ma_user['patientinfo']['weight'] = (isset($patient->weight) && $patient->weight != '') ? $patient->weight : '';
                $ma_user['patientinfo']['height'] = (isset($patient->height) && $patient->height != '') ? $patient->height : '';
                $ma_user['patientinfo']['BMI'] = (isset($patient->BMI) && $patient->BMI != '') ? $patient->BMI : '';
                $ma_user['patientinfo']['bp_systolic'] = (isset($patient->bp_systolic) && $patient->bp_systolic != '') ? $patient->bp_systolic : '';
                $ma_user['patientinfo']['bp_diastolic'] = (isset($patient->bp_diastolic) && $patient->bp_diastolic != '') ? $patient->bp_diastolic : '';
                $ma_user['patientinfo']['bp_position'] = (isset($patient->bp_position) && $patient->bp_position != '') ? $patient->bp_position : '';
                $ma_user['patientinfo']['pulse'] = (isset($patient->pulse) && $patient->pulse != '') ? $patient->pulse : '';
                $ma_user['patientinfo']['respirations'] = (isset($patient->respirations) && $patient->respirations != '') ? $patient->respirations : '';
            }            

            if($group_id == 2){
                // Practice Info
                $practiceinfo = DB::table('practiceinfo')->where('practice_id', '=', $user['practice_id'])->first();
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
                $providers = DB::table('providers')->where('id', '=', $user['id'])->first();
                $providers = json_decode(json_encode($providers), true);
                $ma_user['practiceinfo']["description"] = $providers['description'];
                $ma_user['practiceinfo']["language"] = $providers['Language'];
                $ma_user['practiceinfo']["country"] = $providers['Country'];
                $ma_user['practiceinfo']["photo"] = $providers['photo'];
                $ma_user['practiceinfo']["certificate"] = $providers['certificate']; 
                $ma_user['practiceinfo']["specialty"] = $providers['specialty'];               
                $ma_user['practiceinfo']["license"] = $providers['license'];
                $ma_user['practiceinfo']["license_state"] = $providers['license_state'];
                $ma_user['practiceinfo']["npi_number"] = $providers['npi'];
                $ma_user['practiceinfo']["dea_number"] = $providers['dea'];
                $ma_user['practiceinfo']["medicare_number"] = $providers['medicare'];                
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

    public function updateProfile(Request $request) {

        $user = Auth::guard('api')->user();

        $return = array('status' => 0, 'message' => '', 'data' => array());

        $rules['group_id'] = 'required';
        $rules['photo'] = 'image|mimes:jpg,jpeg,png|max:2048';

        $rules_msg['group_id.required'] = 'Please set user group';
        $rules_msg['photo.max'] = "Profile picture size should be maximum 2MB";

        if($request->has('group_id') && $request->get('group_id') == 100) {           
            // $rules['user_id'] = 'required';
            // $rules_msg['group_id.required'] = 'Please set user group';
        }

        if($request->has('group_id') && $request->get('group_id') == 2) {
            // $rules['group_id'] = 'required';
            // $rules_msg['group_id.required'] = 'Please set user group';   
        }        

        $validator = Validator::make($request->all(), $rules, $rules_msg);
        if ($validator->fails()) { 
            $return['error'] = $validator->errors()->toArray();
            $return['message'] = 'Validation Error Occurred';
            return response()->json($return);
        }

        if($request->has('group_id') && $request->get('group_id') == 100) {

            $patient = DB::table('demographics')->where('pid', '=', $user->id)->first();

            $data = array();

            if ($request->hasFile("photo")) {
                $file = $request->file('photo');                    
                FunctionUtils::createDir('profile');
                $oldfile = FunctionUtils::getProfileUploadPath() . $patient->photo;
                if (is_file($oldfile)) {
                    unlink($oldfile);
                }
                if ($file_name = FunctionUtils::UploadFile($file, FunctionUtils::getProfileUploadPath())) {
                    $data['photo'] = $file_name;
                }
            }

            DB::table('demographics')->where('pid', '=', $user->id)->update($data);

            if($this->audit('Update')) {
                $return['status'] = 1;
                $return['message'] = 'Profile updated Successfully.';
            }
        }

        if($request->has('group_id') && $request->get('group_id') == 2) {

            $provider = DB::table('providers')->where('providers.id', '=', $user->id)->first();

            $data = array();

            if ($request->hasFile("photo")) {
                $file = $request->file('photo');                    
                FunctionUtils::createDir('profile');
                $oldfile = FunctionUtils::getProfileUploadPath() . $provider->photo;
                if (is_file($oldfile)) {
                    unlink($oldfile);
                }
                if ($file_name = FunctionUtils::UploadFile($file, FunctionUtils::getProfileUploadPath())) {
                    $data['photo'] = $file_name;
                }
            }

            DB::table('providers')->where('providers.id', '=', $user->id)->update($data);

            if($this->audit('Update')) {
                $return['status'] = 1;
                $return['message'] = 'Profile updated Successfully.';
            }
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

        $pid = $request->has('pid') ? $request->get('pid') : '';

        $patients = DB::table('demographics')        
            ->select('demographics.pid','demographics.firstname','demographics.lastname','demographics.middle','demographics.title','demographics.sex','demographics.DOB','demographics.email','demographics.address', 'demographics.city','demographics.state','demographics.zip','demographics.language','demographics.active','demographics.photo','vitals.weight','vitals.height','vitals.BMI','vitals.bp_systolic','vitals.bp_diastolic','vitals.bp_position','vitals.pulse','vitals.respirations')        
            ->join('demographics_relate', 'demographics_relate.pid', '=', 'demographics.pid')
            ->leftjoin('vitals', 'vitals.pid', '=', 'demographics.pid')
            ->where('demographics_relate.practice_id', '=', $user->practice_id);

        if($keywords) {
            $patients = $patients->where(function($query_array1) use ($keywords) {
                $query_array1->where('demographics.lastname', 'LIKE', "%$keywords%")
                ->orWhere('demographics.firstname', 'LIKE', "%$keywords%")
                ->orWhere('demographics.pid', 'LIKE', "%$keywords%");
            });
        }

        if($pid != '') {
            $patients = $patients->where('demographics.pid', $pid);            
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

        $provider_id = $request->has('provider_id') ? $request->get('provider_id') : '';
        $name = $request->has('name') ? $request->get('name') : '';
        $specialty = $request->has('specialty') ? $request->get('specialty') : '';
        $language = $request->has('language') ? $request->get('language') : '';

        $providers = DB::table('users')
        ->leftJoin('providers', 'providers.id', '=', 'users.id')
        ->select('users.id', 'users.username', 'users.email', 'users.displayname' ,'users.firstname', 'users.lastname', 'providers.description','providers.specialty', 'providers.Language as language','providers.Country as country','providers.photo','providers.certificate','providers.license','providers.license_state','providers.npi','providers.dea','providers.medicare','providers.tax_id','providers.schedule_increment AS increment_for_schedule_minuntes','providers.timeslotsperhour','practiceinfo.minTime','practiceinfo.maxTime','practiceinfo.weekends','practiceinfo.timezone','practiceinfo.sun_o','practiceinfo.sun_c','practiceinfo.mon_o','practiceinfo.mon_c','practiceinfo.tue_o','practiceinfo.tue_c','practiceinfo.wed_o','practiceinfo.wed_c','practiceinfo.thu_o','practiceinfo.thu_c','practiceinfo.fri_o','practiceinfo.fri_c','practiceinfo.sat_o','practiceinfo.sat_c')
        ->leftJoin('practiceinfo', 'practiceinfo.practice_id', '=', 'users.practice_id')
        ->where('users.group_id', '=', 2)
        ->where('users.active', '=', 1);

        // name filter
        if(trim($name) != "") {
            $keywords = str_replace(' ', '%', trim($name));            
            $providers = $providers->where(function($query_array1) use ($keywords) {
                $query_array1->where('users.displayname', 'LIKE', "%$keywords%")
                ->orWhere('users.firstname', 'LIKE', "%$keywords%")
                ->orWhere('users.lastname', 'LIKE', "%$keywords%");
            });
        }

        // specialty filter
        if(trim($specialty) != "") {
            $keywords = str_replace(' ', '%', trim($specialty));            
            $providers = $providers->where('providers.specialty', 'LIKE', "%$keywords%");
        }

        // language filter
        if(trim($language) != "") {
            $language = str_replace(' ', '%', trim($language));
            $providers = $providers->where('providers.language', 'LIKE', "%$language%");
        }

        if($provider_id != '') {
            $providers = $providers->where('users.id', $provider_id);            
        }

        $providers = $providers->get();

        if ($providers->count() > 0) {

            $return['status'] = 1;            
            $return['message'] = 'Providers list get successfully';
            $return['data']['providers'] = $providers;

        } else {
            $return['message'] = 'Providers not found';
        }
        return  Response::json($return);
    }

    public function getAppointments(Request $request) {

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