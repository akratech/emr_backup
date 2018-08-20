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
		$return = array('status' => 0, 'message' => '', 'data' => array());
		$practice = DB::table('practiceinfo')->where('practice_id', '=', $user->practice_id)->first();

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
		$query = DB::table('messaging')->where('mailbox', '=', $user->id)->orderBy('date', 'desc')->get();
		$columns = Schema::getColumnListing('messaging');
		$row_index = $columns[0];
		if ($query->count()) {
			foreach ($query as $row) {
				$arr = [];
				$user = DB::table('users')->where('id', '=', $row->message_from)->first();

				$arr['id'] = $row->$row_index;
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

	// delete message
	// get all Messages list
	public function deleteMessages(Request $request, $type='delete') {

		$user = Auth::guard('api')->user();
		$return = array('status' => 0, 'message' => '', 'data' => array());
		$practice = DB::table('practiceinfo')->where('practice_id', '=', $user->practice_id)->first();

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
		$query = DB::table('messaging')->where('mailbox', '=', $user->id)->orderBy('date', 'desc')->get();
		$columns = Schema::getColumnListing('messaging');
		$row_index = $columns[0];
		if ($query->count()) {
			foreach ($query as $row) {
				$arr = [];
				$user = DB::table('users')->where('id', '=', $row->message_from)->first();

				$arr['id'] = $row->$row_index;
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
	
}
