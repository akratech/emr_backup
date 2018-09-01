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
        $data['assets_js'] = $this->assets_js('chart');
        $data['assets_css'] = $this->assets_css('chart');
        return view('communication', $data);
    }
}
