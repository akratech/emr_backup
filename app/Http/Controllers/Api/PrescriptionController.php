<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\MessageBag;

use Mail;
use Hash;
use DB;
use Date;
use Validator;
use Schema;
use App\User;

class PrescriptionController extends Controller
{

	public function getPrescriptions(Request $request) {
		$user = Auth::guard('api')->user();
		//$rx_list = DB::table('rx_list')->where('pid', '=', $user->id)->get();
		$rx_list = DB::table('rx_list')->get();
		//$rx_list = DB::table('users')->get();		
		$return = array('status' => 1, 'message' => '', 'data' => $rx_list);
		return  Response::json($return);
	}	

}
