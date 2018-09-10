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

class MessageController extends Controller
{
	public function __construct()
	{
		/* $this->middleware('auth'); */
	}

	// get all Messages list
	public function getMessages(Request $request, $type='inbox') {

		$user = Auth::guard('api')->user();
		$return = array('status' => 1, 'message' => '', 'data' => array());
		$practice = DB::table('practiceinfo')->where('practice_id', '=', $user->practice_id)->first();
		$user_from_id = $request->get('user_from_id');
		$sentbyme = $request->get('sentbyme');
		

		$type_arr = array(
			'inbox' => array('Inbox', 'fa-inbox'),
			'drafts' => array('Drafts', 'fa-pencil-square-o'),
			'outbox' => array('Sent Messages', 'fa-upload'),
			'separator' => 'separator',
			'scans' => array('Scans', 'fa-file-o')
		);
		if ($practice->fax_type !== '') {
			$type_arr['separator1'] = 'separator';
			$type_arr['faxes'] = array('Faxes', 'fa-fax');
			$type_arr['faxes_draft'] = array('Draft Faxes', 'fa-share-square');
			$type_arr['faxes_sent'] = array('Sent Faxes', 'fa-share-square-o');
		}
		$dropdown_array = [
			'items_button_text' => $type_arr[$type][0]
		];
		foreach ($type_arr as $key => $value) {
			if ($value == 'separator') {
				$items[] = [
					'type' => 'separator'
				];
			} else {
				if ($key !== $type) {
					$items[] = [
						'type' => 'item',
						'label' => $value[0],
						'icon' => $value[1],
						'url' => route('messaging', [$key])
					];
				}
			}
		}

		$dropdown_array['items'] = $items;
		$data['panel_dropdown'] = $this->dropdown_build($dropdown_array);
		$list_array = [];
		if(trim($sentbyme))
			$query = DB::table('messaging')->where('message_from', '=', $user->id)->orderBy('message_id', 'desc')->get();
		else if(trim($user_from_id))
			$query = DB::table('messaging')->where('mailbox', '=', $user->id)->where('message_from', '=', $user_from_id)->orderBy('date', 'desc')->get();
		else				
			$query = DB::table('messaging')->where('mailbox', '=', $user->id)->orderBy('date', 'desc')->get();
		$columns = Schema::getColumnListing('messaging');
		$row_index = $columns[0];
		if ($query->count()) {
			foreach ($query as $row) {
				$arr = [];
				$user = DB::table('users')->where('id', '=', $row->message_from)->first();

				$arr['message_id'] = $row->$row_index;
				$arr['message_to'] = $row->message_to;
				$arr['message_from'] = $row->message_from;
				$arr['date'] = date('d-M-Y H:i:s A', $this->human_to_unix($row->date));
				$arr['cc'] = $row->cc;
				$arr['subject'] = $row->subject;
				$arr['body'] = $row->body;				
				$arr['patient_name'] = $row->patient_name;				

				if ($row->read == 'n' || $row->read == null) {
					$arr['read'] = true;
				}

				$arr['user_from_displayname'] = $user->displayname;
				$arr['user_from_id'] = $user->id;
				$arr['user_from_fname'] = $user->firstname;
				$arr['user_from_lname'] = $user->lastname;
				$arr['user_from_username'] = $user->username;


				$list_array[] = $arr;
			}
		}
		$return['data'] = $list_array;

		return  Response::json($return);
	}	

	// add message
	public function searchPatient(Request $request) {
		
		$user = Auth::guard('api')->user();
		$return = array('status' => 1, 'message' => '', 'data' => array());

		$users = DB::table('users')                
		->where('active', '=', 1)
		->where('group_id', '=', 100)
		->where('id', '!=', $user->id);
		$keywords = str_replace(' ', '%', trim($request->get('name')));
		if($keywords) {
			$users = $users->where(function($query_array1) use ($keywords) {
				$query_array1->where('lastname', 'LIKE', "%$keywords%")
				->orWhere('firstname', 'LIKE', "%$keywords%")
				->orWhere('displayname', 'LIKE', "%$keywords%")
				->orWhere('username', 'LIKE', "%$keywords%");
			});
		}
		$users = $users->get();
		foreach ($users as $user) {
			$tmp = array();
			$tmp['id'] = $user->id;
			$tmp['firstname'] = $user->firstname;
			$tmp['lastname'] = $user->lastname;
			$tmp['displayname'] = $user->displayname;	
			$tmp['patient_name'] = $user->displayname . ' (' . $user->id . ')';
			$data[] = $tmp;
		}
		return $return = array('status' => 1, 'message' => '', 'data' => $data);
	}

	// add message
	public function getTousers(Request $request, $group='2') {
		$user = Auth::guard('api')->user();		
		$practiceinfo = DB::table('practiceinfo')->where('practice_id', '=', $user->practice_id)->first();

		$type = 2;
		if ($user->group_id == '100') {
			if ($practiceinfo->patient_centric == 'y') {
				$query = DB::table('users')->select('id','username','displayname','firstname','lastname')->where('group_id', '!=', '100')->where('group_id', '!=', '1')->where('active', '=', '1')->get();
			} else {
				$query = DB::table('users')->select('id','username','displayname','firstname','lastname')->where('group_id', '!=', '100')->where('group_id', '!=', '1')->where('practice_id', '=', $user->practice_id)->where('active', '=', '1')->get();
			}
		} else {
			if ($practiceinfo->patient_centric == 'yp') {
				$query = DB::table('users')->select('id','username','displayname','firstname','lastname')->where('group_id', '!=', '1')->where('active', '=', '1')->get();
			} else {
				$query = DB::table('users')->select('id','username','displayname','firstname','lastname')->where('group_id', '!=', '1')->where('practice_id', '=', $user->practice_id)->where('active', '=', '1')->get();
			}
		}
		if ($query->count()) {
			foreach ($query as $row) {
				$return[] = $row;
			}
		}              
		return $return = array('status' => 1, 'message' => '', 'data' => $return);
	}


	// delete message
	public function deleteMessages(Request $request) {
		$user = Auth::guard('api')->user();
		$query = DB::table('messaging')->where('mailbox', '=', $user->id)->orderBy('date', 'desc')->get();
		DB::table('messaging')->where('message_id', '=', $request->get('message_id'))->where('mailbox', '=', $user->id)->delete();
		$this->audit('Delete');
		return $return = array('status' => 1, 'message' => 'Message delete success.', 'data' => array());
	}


	// add message
	public function addMessages(Request $request) {

		$user = Auth::guard('api')->user();
		$return = array('status' => 1, 'message' => 'Message sent success.', 'data' => array());
		$practice = DB::table('practiceinfo')->where('practice_id', '=', $user->practice_id)->first();

		$ma_to = array_filter(explode(',', $request->get('to')));
		$ma_cc = array_filter(explode(',', $request->get('cc')));		

		$ma_message_to = array();
		foreach ($ma_to as $to) {
			$userTo = DB::table('users')->where('id', '=', $to)->first();
			$ma_message_to[] = $userTo->displayname . ' (' . $userTo->id . ')';

		}
		$ma_message_cc = array();
		foreach ($ma_cc as $cc) {
			$userCc = DB::table('users')->where('id', '=', $cc)->first();
			$ma_message_cc[] = $userCc->displayname . ' (' . $userCc->id . ')';
		}
		$ma_mailbox = array_merge($ma_cc,$ma_to);
		foreach ($ma_mailbox as $mailbox_row) {
			if ($mailbox_row !== '') {
				$send_data = [
					'message_to' => implode(';', $ma_message_to),
					'message_from' => $user->id,
					'patient_name' => $request->get('patient_name'),
					'subject' => $request->get('subject'),
					'body' => $request->get('body'),
					't_messages_id' => 0,
					'pid' => 0,
					'status' => 'Sent',
					'mailbox' => $mailbox_row,
					'practice_id' => $user->practice_id
				];				
				$send_data['cc'] = implode(';', $ma_message_cc);
				DB::table('messaging')->insert($send_data);
				$this->audit('Add');
				$user_row = DB::table('users')->where('id', '=',$mailbox_row)->first();
				if ($user_row->group_id === '100') {
					$data_message['patient_portal'] = $practice->patient_portal;
					$this->send_mail('emails.newmessage', $data_message, 'New Message in your Patient Portal', $user_row->email, $user->practice_id);
				}
			}
		}
		return  Response::json($return);
	}
}
