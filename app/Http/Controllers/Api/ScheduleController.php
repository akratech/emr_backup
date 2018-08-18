<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

use Mail;
use Hash;
use DB;
use Date;
use Validator;

use App\User;

class ScheduleController extends Controller
{
	public function __construct()
	{
		/* $this->middleware('auth'); */
	}

	public function providerSchedules(Request $request) {

		$user = Auth::guard('api')->user();

		$return = array('status' => 0, 'message' => '', 'data' => array());

		$validator = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_end' => 'required',
            'provider_id' => 'required',
        ]);

        if ($validator->fails()) {         

        	$return['message'] = 'Validation Error Occurred';
        	$return['error'] = $validator;

            return response()->json($return);
        }        

		$start = strtotime($request->get('start_date'));
		$end = strtotime($request->get('end_end'));
		$id = $request->get('provider_id');

		$events = [];
		$query = DB::table('schedule')->where('provider_id', '=', $id)->whereBetween('start', [$start, $end])->get();
		if ($query) {
			foreach ($query as $row) {
				if ($row->visit_type != '') {
					$row1 = DB::table('calendar')
					->select('classname')
					->where('visit_type', '=', $row->visit_type)
					->where('practice_id', '=', $user->practice_id)
					->first();
					$classname = $row1->classname;
				} else {
					$classname = 'colorblack';
				}
				if ($row->pid == '0') {
					$pid = '';
					$patients_info = '';
				} else {
					$pid = $row->pid;
					$patients_info = DB::table('demographics')
		            ->select('pid', 'firstname', 'lastname', 'middle', 'title', 'sex', 'DOB', 'email', 'address', 'city', 'state', 'zip', 'active','photo')
		            ->where('pid', $pid)->first();

		            if(isset($patients_info) && !empty($patients_info)) {

			            if($patients_info->sex == 'm') { 
		                    $patients_info->sex = "Male";
		                }
		                if($patients_info->sex == 'f') { 
		                    $patients_info->sex = "Female"; 
		                }
		                $patients_info->DOB = date('d-M-Y',strtotime($patients_info->DOB));
		                
		            } else {
		            	$patients_info = '';
		            }
	                
				}
				if ($row->timestamp == '0000-00-00 00:00:00' || $row->user_id == '') {
					$timestamp = '';					
				} else {
					$user_row = DB::table('users')->where('id', '=', $row->user_id)->first();
					$timestamp = date('d-M-Y H:i:s',strtotime($row->timestamp));
                    //$timestamp = $row->timestamp;
				}
				$sub_title = 'Appointment added by ' . $user_row->displayname . ' on ' . $timestamp;

				$row_start = date('c', $row->start);
				$row_end = date('c', $row->end);

				$row_start_date = date('d-M-Y', $row->start);
				$row_start_time = date('H:i:s', $row->start);

				$row_end_date = date('d-M-Y', $row->end);
				$row_end_time = date('H:i:s', $row->end);

				$event = [
					'id' => $row->appt_id,
					'start' => $row_start,
					'end' => $row_end,
					'start_date' => $row_start_date,
					'start_time' => $row_start_time,
					'end_date' => $row_end_date,
					'end_time' => $row_end_time,
					'visit_type' => $row->visit_type,
					'className' => $classname,
					'provider_id' => $row->provider_id,
					'pid'=> $pid,
					'timestamp' => $timestamp,
					'sub_title' => $sub_title
				];
				if ($user->group_id == '100' || $user->group_id == 'schedule') {
					if ($request->get('pid') != $pid) {
						$event['title'] = 'Appointment taken';
						$event['reason'] = 'Private';
						$event['status'] = 'Private';
						$event['notes'] = '';
						$event['editable'] = false;
					} else {
						$event['title'] = $row->title;
						$event['reason'] = $row->reason;
						$event['status'] = $row->status;
						$event['notes'] = '';
						$event['editable'] = true;
					}
				} else {
					$event['title'] = $row->title;
					$event['reason'] = $row->reason;
					$event['status'] = $row->status;
					$event['notes'] = $row->notes;
					if ($user->group_id == '1') {
						$event['editable'] = false;
					} else {
						$event['editable'] = true;
					}
					if ($row->status == 'Attended') {
						$event['borderColor'] = 'green';
					}
					if ($row->status == 'DNKA') {
						$event['borderColor'] = 'black';
					}
					if ($row->status == 'LMC') {
						$event['borderColor'] = 'red';
					}
				}				

				$event['patients_info'] = $patients_info;

				$events[] = $event;
			}
		}
		$query2 = DB::table('repeat_schedule')->where('provider_id', '=', $id)->get();
		if ($query2) {
			foreach ($query2 as $row2) {
				if ($row2->start <= $end || $row2->start == "0") {
					if ($row2->repeat == "86400") {
						if ($row2->start <= $start) {
							$repeat_start = strtotime('this ' . strtolower(date('l', $start)) . ' ' . $row2->repeat_start_time, $start);
							$repeat_end = strtotime('this ' . strtolower(date('l', $start)) . ' ' . $row2->repeat_end_time, $start);
						} else {
							$repeat_start = strtotime('this ' . $row2->repeat_day . ' ' . $row2->repeat_start_time, $start);
							$repeat_end = strtotime('this ' . $row2->repeat_day . ' ' . $row2->repeat_end_time, $start);
						}
					} else {
						$repeat_start = strtotime('this ' . $row2->repeat_day . ' ' . $row2->repeat_start_time, $start);
						$repeat_end = strtotime('this ' . $row2->repeat_day . ' ' . $row2->repeat_end_time, $start);
					}
					if ($row2->until == '0') {
						while ($repeat_start <= $end) {
							$repeat_id = 'R' . $row2->repeat_id;
							$until = '';
							if ($row2->reason == '') {
								$row2->reason = $row2->title;
							}

							$repeat_start1 = date('c', $repeat_start);
							$repeat_end1 = date('c', $repeat_end);

							$repeat_start_date1 = date('d-M-Y', $repeat_start);
							$repeat_start_time1 = date('H:i:s', $repeat_start);

							$repeat_end_date1 = date('d-M-Y', $repeat_end);
							$repeat_end_time1 = date('H:i:s', $repeat_end);

							$event1 = array(
								'id' => $repeat_id,
								'start' => $repeat_start1,
								'end' => $repeat_end1,
								'start_date' => $repeat_start_date1,
								'start_time' => $repeat_start_time1,
								'end_date' => $repeat_end_date1,
								'end_time' => $repeat_end_time1,
								'repeat' => $row2->repeat,
								'until' => $until,
								'className' => 'colorblack',
								'provider_id' => $row2->provider_id,
								'status' => 'Repeated Event',
								'notes' => ''
							);
							if ($user->group_id == '100') {
								$event1['title'] = 'Provider Not Available';
								$event1['reason'] = 'Provider Not Available';
								$event1['editable'] = false;
							} else {
								$event1['title'] = $row2->title;
								$event1['reason'] = $row2->reason;
								if ($user->group_id == '1') {
									$event1['editable'] = false;
								} else {
									$event1['editable'] = true;
								}
							}
							$events[] = $event1;
							$repeat_start = $repeat_start + $row2->repeat;
							$repeat_end = $repeat_end + $row2->repeat;
						}
					} else {
						while ($repeat_start <= $end) {
							if ($repeat_start > $row2->until) {
								break;
							} else {
								$repeat_id = 'R' . $row2->repeat_id;
								$until = date('m/d/Y', $row2->until);
								if ($row2->reason == '') {
									$row2->reason = $row2->title;
								}

								$repeat_start1 = date('c', $repeat_start);
								$repeat_end1 = date('c', $repeat_end);

								$repeat_start_date1 = date('d-M-Y', $repeat_start);
								$repeat_start_time1 = date('H:i:s', $repeat_start);

								$repeat_end_date1 = date('d-M-Y', $repeat_end);
								$repeat_end_time1 = date('H:i:s', $repeat_end);

								$event1 = array(
									'id' => $repeat_id,
									'start' => $repeat_start1,
									'end' => $repeat_end1,
									'start_date' => $repeat_start_date1,
									'start_time' => $repeat_start_time1,
									'end_date' => $repeat_end_date1,
									'end_time' => $repeat_end_time1,
									'repeat' => $row2->repeat,
									'until' => $until,
									'className' => 'colorblack',
									'provider_id' => $row2->provider_id,
									'status' => 'Repeated Event',
									'notes' => ''
								);
								if ($user->group_id == '100') {
									$event1['title'] = 'Provider Not Available';
									$event1['reason'] = 'Provider Not Available';
									$event1['editable'] = false;
								} else {
									$event1['title'] = $row2->title;
									$event1['reason'] = $row2->reason;
									if ($user->group_id == '1') {
										$event1['editable'] = false;
									} else {
										$event1['editable'] = true;
									}
								}
								$events[] = $event1;
								$repeat_start = $repeat_start + $row2->repeat;
								$repeat_end = $repeat_end + $row2->repeat;
							}
						}
					}
				}
			}
		}

		$row3 = DB::table('practiceinfo')->where('practice_id', '=', $user->practice_id)->first();

		if($row3) {		

			$compminTime = strtotime($row3->minTime);
			$compmaxTime = strtotime($row3->maxTime);

			if ($row3->sun_o != '') {
				$comp1o = strtotime($row3->sun_o);
				$comp1c = strtotime($row3->sun_c);
				if ($comp1o > $compminTime) {
					$events = $this->add_closed1('sunday', $row3->minTime, $row3->sun_o, $events, $start, $end);
				}
				if ($comp1c < $compmaxTime) {
					$events = $this->add_closed2('sunday', $row3->maxTime, $row3->sun_c, $events, $start, $end);
				}
			} else {
				$events = $this->add_closed3('sunday', $row3->minTime, $row3->maxTime, $events, $start, $end);
			}

			if ($row3->mon_o != '') {
				$comp2o = strtotime($row3->mon_o);
				$comp2c = strtotime($row3->mon_c);
				if ($comp2o > $compminTime) {
					$events = $this->add_closed1('monday', $row3->minTime, $row3->mon_o, $events, $start, $end);
				}
				if ($comp2c < $compmaxTime) {
					$events = $this->add_closed2('monday', $row3->maxTime, $row3->mon_c, $events, $start, $end);
				}
			} else {
				$events = $this->add_closed3('monday', $row3->minTime, $row3->maxTime, $events, $start, $end);
			}

			if ($row3->tue_o != '') {
				$comp3o = strtotime($row3->tue_o);
				$comp3c = strtotime($row3->tue_c);
				if ($comp3o > $compminTime) {
					$events = $this->add_closed1('tuesday', $row3->minTime, $row3->tue_o, $events, $start, $end);
				}
				if ($comp3c < $compmaxTime) {
					$events = $this->add_closed2('tuesday', $row3->maxTime, $row3->tue_c, $events, $start, $end);
				}
				// $events['start_date'] = date('d-M-Y',strtotime($start));
				// $events['end_date'] = date('d-M-Y',strtotime($end));
			} else {
				$events = $this->add_closed3('tuesday', $row3->minTime, $row3->maxTime, $events, $start, $end);				
			}

			if ($row3->wed_o != '') {
				$comp4o = strtotime($row3->wed_o);
				$comp4c = strtotime($row3->wed_c);
				if ($comp4o > $compminTime) {
					$events = $this->add_closed1('wednesday', $row3->minTime, $row3->wed_o, $events, $start, $end);
				}
				if ($comp4c < $compmaxTime) {
					$events = $this->add_closed2('wednesday', $row3->maxTime, $row3->wed_c, $events, $start, $end);
				}
			} else {
				$events = $this->add_closed3('wednesday', $row3->minTime, $row3->maxTime, $events, $start, $end);
			}

			if ($row3->thu_o != '') {
				$comp5o = strtotime($row3->thu_o);
				$comp5c = strtotime($row3->thu_c);
				if ($comp5o > $compminTime) {
					$events = $this->add_closed1('thursday', $row3->minTime, $row3->thu_o, $events, $start, $end);
				}
				if ($comp5c < $compmaxTime) {
					$events = $this->add_closed2('thursday', $row3->maxTime, $row3->thu_c, $events, $start, $end);
				}
			} else {
				$events = $this->add_closed3('thursday', $row3->minTime, $row3->maxTime, $events, $start, $end);
			}

			if ($row3->fri_o != '') {
				$comp6o = strtotime($row3->fri_o);
				$comp6c = strtotime($row3->fri_c);
				if ($comp6o > $compminTime) {
					$events = $this->add_closed1('friday', $row3->minTime, $row3->fri_o, $events, $start, $end);
				}
				if ($comp6c < $compmaxTime) {
					$events = $this->add_closed2('friday', $row3->maxTime, $row3->fri_c, $events, $start, $end);
				}
			} else {
				$events = $this->add_closed3('friday', $row3->minTime, $row3->maxTime, $events, $start, $end);
			}

			if ($row3->sat_o != '') {
				$comp7o = strtotime($row3->sat_o);
				$comp7c = strtotime($row3->sat_c);
				if ($comp7o > $compminTime) {
					$events = $this->add_closed1('saturday', $row3->minTime, $row3->sat_o, $events, $start, $end);
				}
				if ($comp7c < $compmaxTime) {
					$events = $this->add_closed2('saturday', $row3->maxTime, $row3->sat_c, $events, $start, $end);
				}
			} else {
				$events = $this->add_closed3('saturday', $row3->minTime, $row3->maxTime, $events, $start, $end);
			}

			$return['status'] = 1;
			$return['message'] = 'Schedule get successfully';
			$return['data']['schedules'] = $events;			

		} else {
			$return['message'] = 'No any schedule fixed';
		}

		return  Response::json($return);
	}

	public function updateSchedule(Request $request) {

		$user = Auth::guard('api')->user();

		$return = array('status' => 0, 'message' => '', 'data' => array());

		$validator = Validator::make($request->all(), [			
			'provider_id' => 'required',
			'reason' => 'required',	
			'title' => 'required',						
			'start_date' => 'required',
			'start_time' => 'required',
            'end_time' => 'required',
        ]);

        if ($validator->fails()) {
        	$return['message'] = 'Validation Error Occurred';
        	$return['error'] = $validator;
            return response()->json($return);                
        }


        if ($user->group_id == '100') {        	
            $pid = $request->get('pid');
            $row1 = DB::table('demographics')->where('pid', '=', $pid)->first();
            $title = $row1->lastname . ', ' . $row1->firstname . ' (DOB: ' . date('m/d/Y', strtotime($row1->DOB)) . ') (ID: ' . $pid . ')';
        } else {    
            $pid = $request->get('pid');
            if ($pid == '' || $pid == '0') {
                $title = $request->get('reason');
            } else {
                $title = $request->get('title');
            }
        }

        $start = strtotime($request->get('start_date') . " " . $request->get('start_time'));
        if ($pid == '' || $pid == '0') {        	
            $end = strtotime($request->get('start_date') . " " . $request->get('end_time'));
            $visit_type = '';
        } else {
            $visit_type = $request->get('visit_type');
            $row = DB::table('calendar')
                ->select('duration')
                ->where('visit_type', '=', $visit_type)
                ->where('active', '=', 'y')
                ->where('practice_id', '=', $user->practice_id)
                ->first();                
            $end = $start + $row->duration;
        }

        $provider_id = $request->get('provider_id');
        $reason = $request->get('reason');
        $id = $request->get('event_id');
        $repeat = $request->get('repeat');

        if ($id == '') {        	
            if ($user->group_id == '100') {            	
                $status = 'Pending';
            } else {
                if ($pid == '' || $pid == '0') {
                    $status = '';
                } else {
                    $status = 'Pending';
                }
            }
        } else {
            $status = $request->get('status');
        }        

        if ($repeat != '') {

            $repeat_day1 = date('l', $start);
            $repeat_day = strtolower($repeat_day1);
            $repeat_start_time = date('h:ia', $start);
            $repeat_end_time = date('h:ia', $end);
            if ($request->get('until') != '') {
                $until = strtotime($request->get('until'));
            } else {
                $until = '0';
            }
            $data1 = [
                'repeat_day' => $repeat_day,
                'repeat_start_time' => $repeat_start_time,
                'repeat_end_time' => $repeat_end_time,
                'repeat' => $repeat,
                'until' => $until,
                'title' => $title,
                'reason' => $reason,
                'provider_id' => $provider_id,
                'start' => $start
            ];
            if ($id == '') {
                DB::table('repeat_schedule')->insert($data1);
                $this->audit('Add');
                $return['status'] = 1;
                $return['message'] = 'Repeated event added.';
            } else {
                $id_check = strpbrk($id, 'N');
                if ($id_check == TRUE) {
                    $nid = str_replace('N', '', $id);
                    DB::table('repeat_schedule')->insert($data1);
                    $this->audit('Add');
                    DB::table('schedule')->where('appt_id', '=', $nid)->delete();
                    $this->audit('Delete');
                    $return['status'] = 1;
                    $return['message'] = 'Repeated event updated.';
                } else {
                    $rid = str_replace('R', '', $id);
                    DB::table('repeat_schedule')->where('repeat_id', '=', $rid)->update($data1);
                    $this->audit('Update');
                    $return['status'] = 1;
                    $return['message'] = 'Repeated event updated.';
                }
            }

            $return['data'] = $data1;
        } else {

            $data = [            
            	'appt_id' => $id,
                'pid' => $pid,
                'start' => $start,
                'end' => $end,
                'title' => $title,
                'visit_type' => $visit_type,
                'reason' => $reason,
                'status' => $status,
                'provider_id' => $provider_id,
                'user_id' => $user->id,
            ];

            if ($user->group_id != '100') {
                $data['notes'] = $request->get('notes');
            }

            if ($id == '') {

                $appt_id = DB::table('schedule')->insertGetId($data);
                $this->audit('Add');

                if ($pid != '0' && $pid !== '') {                	
                    /*$this->schedule_notification($appt_id);*/                    
                }               


                $data['appt_id'] = $appt_id;
                $data['start'] = date('d-M-Y H:i:s', $data['start']);
                $data['end'] = date('d-M-Y H:i:s', $data['end']);
                $data['start_date'] = date('d-M-Y', $data['start']);
                $data['start_time'] = date('H:i:s', $data['start']);
                $data['end_date'] = date('d-M-Y', $data['end']);
                $data['end_time'] = date('H:i:s', $data['end']);

                $return['status'] = 1;
                $return['message'] = 'Appointment/Event added.';
               
            } else {
                $id_check1 = strpbrk($id, 'NR');
                if ($id_check1 == TRUE) {
                    $nid1 = str_replace('NR', '', $id);
                    DB::table('schedule')->insert($data);
                    $this->audit('Add');
                    DB::table('repeat_schedule')->where('repeat_id', '=', $nid1)->delete();
                    $this->audit('Delete');
                    $return['status'] = 1;
                    $return['message'] = 'Event updated.';
                } else {
                    $notify = DB::table('schedule')->where('appt_id', '=', $id)->first();
                    if($notify) {
	                    DB::table('schedule')->where('appt_id', '=', $id)->update($data);
	                    $this->audit('Update');
	                    if ($notify->start != $start && $notify->end != $end) {
	                        if ($pid != '0' && $pid !== '') {
								/* $this->schedule_notification($id); */
	                        }
	                    }
	                    $return['status'] = 1;
	                    $return['message'] = 'Appointment updated.';
	                }
                }
            }

            $return['data'] = $data;
        }

        

        return  Response::json($return);
    }

    public function deleteSchedule(Request $request) {

    	$return = array('status' => 0, 'message' => 'Schedule Could not deleted', 'data' => array());	

    	if($request->has('$') && $request->get('schedule_id') != '') {

    		$schedule_id = $request->get('schedule_id');
	        $id_check = strpbrk($schedule_id, 'R');
	        if ($id_check == false) {
	            $schedule = DB::table('schedule')->where('appt_id', '=', $schedule_id);
	            if($schedule->delete()) {
	            	$this->audit('Delete');
	            	$return['status'] = 1;
	        		$return['message'] ='Schedule successfully deleted';
	        	} else {
	        		$return['message'] ='Schedule not available';
	        	}
	        } else {
	            $rid = str_replace('R', '', $schedule_id);
	            $schedule = DB::table('repeat_schedule')->where('repeat_id', '=', $rid);	            
	            if($schedule->delete()) {
	            	$this->audit('Delete');
	            	$return['status'] = 1;
	        		$return['message'] ='Schedule successfully deleted';
	        	} else {
	        		$return['message'] ='Schedule not available';
	        	}
	        }
    	}       

        return  Response::json($return);
	}	

}
