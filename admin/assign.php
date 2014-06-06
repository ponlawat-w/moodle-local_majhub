<?php // $Id: assign.php 208 2013-02-04 00:39:45Z malu $

require_once __DIR__.'/../../../config.php';
require_once __DIR__.'/form.php';
require_once __DIR__.'/../classes/point.php';
require_once __DIR__.'/../classes/bulkform.php';

require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/lib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/user_bulk_forms.php');

admin_externalpage_setup('userbulk');

if (!isset($SESSION->bulk_users)) {
    $SESSION->bulk_users = array();
}
// create the user filter form
$ufiltering = new user_filtering();

// array of bulk operations
// create the bulk operations form
$action_form = new majhub\maj_user_bulk_action_form();
$assign=0;
if ($data = $action_form->get_data()) {
    // check if an action should be performed and do so
    switch ($data->action) {
        case 1: $assign = $data->points;break;
        case 2: $assign = $data->points * -1;break;
        case 3: $assign =-1;break;
        default: $assign =0;
    }
}

$user_bulk_form = new user_bulk_form(null, get_selection_data($ufiltering));

$udata = $user_bulk_form->get_data();
$updatedusers=0;
//points update
if($assign>0){
        //$isadmin = majhub\capability::is_admin($USER);
       // $ismoderator = majhub\capability::is_moderator($USER);
       
       foreach($SESSION->bulk_users as $userid) {
			if ($userid == -1) {
				continue;
			}
			//create and insert a points entry
			$majuserpoints = new stdClass;
			$majuserpoints->userid = $userid;
			$majuserpoints->points = $assign;
			$majuserpoints->reason = $data->reason;
			$majuserpoints->timecreated = time();
			$DB->insert_record('majhub_user_points', $majuserpoints);
			$updatedusers++;        
       }
//profile update
}elseif($assign<0 && $data->profilefield){
	foreach($SESSION->bulk_users as $userid) {
			if ($userid == -1) {
				continue;
			}
			$conditions = array('userid'=>$userid, 'fieldid'=>$data->profilefield);
			if($DB->record_exists('user_info_data',$conditions)){
				$DB->set_field('user_info_data','data',$data->profilefieldvalue,$conditions);
				$updatedusers++;
			}else{
				$profile_field_data = new stdClass();
				$profile_field_data->userid=$userid;
				$profile_field_data->fieldid=$data->profilefield;
				$profile_field_data->data=$data->profilefieldvalue;
				$profile_field_data->format=0;
				$ret = $DB->insert_record('user_info_data',$profile_field_data);
				if($ret){$updatedusers++;}
				
			}
	}
}

//This is the do block
$actionperformed =false;
if($assign!=0){
	switch($data->action){
		case 1:
			$result = new stdClass;
			$result->points = $data->points;
			$result->users = $updatedusers;
			$actionperformed = get_string('giveresult','local_majhub',$result);
			break;
		case 2:
			$result = new stdClass;
			$result->points = $data->points;
			$result->users = $updatedusers;
			$actionperformed = get_string('removeresult','local_majhub',$result);
			break;
		case 3:
			$result = new stdClass;
			$result->updatedvalue = $data->profilefieldvalue;
			$result->users = $updatedusers;
			$actionperformed = get_string('profileresult','local_majhub',$result);
			break;
		default:echo "nothing";break;
	
	}
}

//end of do block
$user_bulk_form = new user_bulk_form(null, get_selection_data($ufiltering));

if ($data = $user_bulk_form->get_data()) {
    if (!empty($data->addall)) {
        add_selection_all($ufiltering);

    } else if (!empty($data->addsel)) {
        if (!empty($data->ausers)) {
            if (in_array(0, $data->ausers)) {
                add_selection_all($ufiltering);
            } else {
                foreach($data->ausers as $userid) {
                    if ($userid == -1) {
                        continue;
                    }
                    if (!isset($SESSION->bulk_users[$userid])) {
                        $SESSION->bulk_users[$userid] = $userid;
                    }
                }
            }
        }

    } else if (!empty($data->removeall)) {
        $SESSION->bulk_users= array();

    } else if (!empty($data->removesel)) {
        if (!empty($data->susers)) {
            if (in_array(0, $data->susers)) {
                $SESSION->bulk_users= array();
            } else {
                foreach($data->susers as $userid) {
                    if ($userid == -1) {
                        continue;
                    }
                    unset($SESSION->bulk_users[$userid]);
                }
            }
        }
    }
    // reset the form selections
    unset($_POST);
    $user_bulk_form = new user_bulk_form(null, get_selection_data($ufiltering));
}

// do output
echo $OUTPUT->header();
if($actionperformed){
	echo $OUTPUT->box($actionperformed);
}
$ufiltering->display_add();
$ufiltering->display_active();
$user_bulk_form->display();
$action_form->display();
echo $OUTPUT->footer();
