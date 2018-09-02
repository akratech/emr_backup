<?php

namespace App\Http\Controllers;

use App;
use App\Http\Requests;
use App\Libraries\OpenIDConnectClient;
use Config;
use Crypt;
use Date;
use DB;
use File;
use Form;
use HTML;
use Htmldom;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Minify;
use PdfMerger;
use QrCode;
use Response;
use Schema;
use Session;
use SoapBox\Formatter\Formatter;
use URL;
use Illuminate\Support\Facades\Auth;

class CommunicationController extends Controller {

    public function __construct()
    {
        $this->middleware('checkinstall');
        $this->middleware('auth');
        $this->middleware('csrf');
        $this->middleware('postauth');
        $this->middleware('patient');
    }
    
    public function init_conference(){
        $next_appt = DB::table('schedule')->where('pid', '=', Session::get('pid'))->where('appt_id', '=', Session::get('apptid'))->where('start', '<', time())->where('end', '>', time())->first();
        if(!isset($next_appt)){
            Session::put('message_action', 'Error - Visit Valid appointment!');
            return redirect('patient')->with('message_action','Error - Visit Valid appointment!');
        }
        
        $data['title'] = Session::get('ptname');
        if(Auth::user()->group_id == 2){
            $data['urname'] = Session::get('ptname');
            $data['myname'] = Auth::user()->username;
//            $data['urname'] = "ppp";
//            $data['myname'] = "aaa";
        }else{
            $provide_id = Session::get('provider_id');
            $provider = DB::table('users')->where('id',$provide_id)->first();
            $data['urname'] = $provider->username;
            $data['myname'] = Session::get('ptname');
//            $data['urname'] = 'aaa';
//            $data['myname'] = 'ppp';
        }
        
        
        $data['room'] = md5(Session::get('pid'));
        $data['pid'] = Session::get('pid');
        $data['assets_js'] = $this->assets_js('chart');
        $data['assets_css'] = $this->assets_css('chart');
        return view('communication', $data);
    }
}
