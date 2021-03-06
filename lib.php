<?php // $Id: lib.php 227 2013-03-01 06:17:01Z malu $

defined('MOODLE_INTERNAL') || die;


/**
 *  MAJ Hub cron job
 *  
 *  @global moodle_database $DB
 */
function local_majhub_cron(progress_trace $trace = null)
{
    global $DB;

    require_once __DIR__.'/classes/restore.php';
    require_once __DIR__.'/classes/courseware.php';


    $teacherrole = $DB->get_record('role', array('archetype' => 'teacher'), '*', IGNORE_MULTIPLE);
    $editingteacherrole = $DB->get_record('role', array('archetype' => 'editingteacher'), '*', IGNORE_MULTIPLE);
    $enroller = enrol_get_plugin('manual');
	if($trace){
	 $trace->output("Executing MAJ HUB cron");
	}

    // gets all uploaded, not restored and not restoring coursewares
    $coursewares = $DB->get_records_select(majhub\courseware::TABLE,
        'deleted = 0 AND fileid IS NOT NULL AND courseid IS NULL AND timestarted IS NULL',
        null, 'timeuploaded ASC');

    if($coursewares){
   	 if($trace){
    	$trace->output("found courses to restore");
      }
    }else{
    	if($trace){
    		$trace->output("found no courses to restore. Exiting");	
    	}
    }

    foreach ($coursewares as $courseware) try {
	    // double check to prevent from being duplicated
        $courseware = majhub\courseware::from_id($courseware->id, MUST_EXIST);
        
        if (!empty($courseware->courseid) || !empty($courseware->timestarted) || $courseware->unrestorable)
            continue;	
			
        // marks as restoring
        $DB->set_field(majhub\courseware::TABLE, 'timestarted', time(), array('id' => $courseware->id));
		
		if($trace){
        	$trace->output("  Courseware ID: {$courseware->id}");
        	$trace->output('    Creating a preview course', '...');
        }
 
            // restores the uploaded course backup file as a new course
			$courseid = majhub\restore($courseware->id);
			
			// if restoration did not happen, exit
			if($courseid === false){continue;}
			
            $courseware->courseid = $courseid;
            $course = $DB->get_record('course', array('id' => $courseware->courseid), '*', MUST_EXIST);

            // deletes old coursewares having same courseid
            $DB->execute(
                'UPDATE {majhub_coursewares} SET deleted = 1 WHERE courseid = :courseid AND id <> :coursewareid',
                array('courseid' => $courseware->courseid, 'coursewareid' => $courseware->id)
                );

            // renames the course fullname and shortname with the courseware unique id
            $course->fullname  = $courseware->unique_fullname;
            $course->shortname = $courseware->unique_shortname;
            $DB->update_record('course', $course);

            // adds a MAJ Hub block to the new course
            $page = new moodle_page();
            $page->set_course($course);
            $page->set_pagelayout('course');
            $page->set_pagetype('course-view-' . $course->format);
            $page->blocks->load_blocks();
            $page->blocks->add_block_at_end_of_default_region('majhub');
            $page->blocks->add_block('majhub_points', BLOCK_POS_LEFT, -1, false);
            
		if($trace){
			$trace->output("done. (courseid: {$courseware->courseid})");
			$trace->output('    Assigning a teacher capability for all registered users');
		}
		
            // assigns a capability for switching roles to non-editing teachers
            $context = context_course::instance($course->id);
            assign_capability('moodle/role:switchroles', CAP_ALLOW, $teacherrole->id, $context->id);

            // enrols all the registered users to the new course as non-editing teachers
            $instanceid = $enroller->add_instance($course);
            if($instanceid==null){
            	$instance = $DB->get_record('enrol', array('courseid' => $course->id,'enrol'=>'manual'), '*', MUST_EXIST);
            }else{
            	$instance = $DB->get_record('enrol', array('id' => $instanceid), '*', MUST_EXIST);
            }
            $users = get_users_confirmed();
            foreach ($users as $user) {
            	//add justin, make the owner an editing teacher
            	if($user->id == $courseware->userid && $courseware->userid > 1){
            		$enroller->enrol_user($instance, $user->id, $editingteacherrole->id);
            	}else{
                	$enroller->enrol_user($instance, $user->id, $teacherrole->id);
                }
            }
            unset($users); // for memory saving
            
        if($trace){
        	$trace->output('done.');
        }

        //Add Justin 20131020
        //make sure the course appears as ready and published. Set privacy to 1 to make it visible.
        $courseinfo = $DB->get_record('hub_course_directory', array('id' => $courseware->hubcourseid), '*', MUST_EXIST);
        $courseinfo->privacy = 1;
        $DB->update_record('hub_course_directory', $courseinfo);  
    
	} catch (Exception $ex) {
        error_log($ex->__toString());
    }
	
}

/**
 *  MAJ Hub user created event handler
 *  
 *  @global moodle_database $DB
 *  @param object $user
 */
function local_majhub_user_created_handler($user)
{
    global $DB;

    require_once __DIR__.'/classes/courseware.php';

    $teacherrole = $DB->get_record('role', array('archetype' => 'teacher'), '*', IGNORE_MULTIPLE);
    $enroller = enrol_get_plugin('manual');

    // enrols the new user to all the coursewares as a non-editing teacher
    $coursewares = $DB->get_records_select(majhub\courseware::TABLE, 'fileid IS NOT NULL AND courseid IS NOT NULL');
    foreach ($coursewares as $courseware) try {
        $instance = $DB->get_record('enrol',
            array('enrol' => $enroller->get_name(), 'courseid' => $courseware->courseid), '*', MUST_EXIST);
        $enroller->enrol_user($instance, $user->id, $teacherrole->id);
    } catch (Exception $ex) {
        //don't really need this error
		//if we have a courseware with no courseid (ie not restored) then it will fire this
		//error_log($ex->__toString());
    }
}




//this is the HUB backupfile file location
//there are several ids and its too confusing. But I explain:
//courseware:id = the id of the record in courseware table
//courseware:courseid = the course id of this course on this site(ie moodles course id)
//courseware:sitecourseid = the course of this course on the original publishing site
//courseware:hubcourseid = the id of the record for this course in the standard hubs hub_course_directory table
//in this and related functions we are temporiy using original course dir. Later hope to use exclusively
function local_majhub_hub_fetch_course_filepath($courseid){
	global $CFG;
		 $level1 = floor($courseid / 1000) * 1000;
		 $userdir = "hub/$level1/$courseid";
		 $fullpath = $CFG->dataroot . '/' . $userdir . '/backup_' . $courseid . ".mbz";
		 return $fullpath;
}
//this is a temp filepath in same dir as HUB backupfile file location
function local_majhub_fetch_versioned_filepath($courseid, $timecreated){
		$basepath = local_majhub_hub_fetch_course_filepath($courseid);
		$newpath =  str_replace('.mbz', '_' . $timecreated . '.mbz',$basepath);
		return $newpath;
}

function local_majhub_hub_course_received_handler($hubcourseid)
{

 	global $DB,$CFG;
	
	require_once __DIR__.'/classes/courseware.php';
	require_once __DIR__.'/classes/storage.php';
	require_once __DIR__.'/classes/setting.php';

	//use majhub\setting;

	$storage = new majhub\storage();

		$courseinfo = $DB->get_record('hub_course_directory', array('id' => $hubcourseid), '*', IGNORE_MISSING);
		
		 
		 //Get the user to make the owner of this course
		 $user = $DB->get_record('user', array('email' => $courseinfo->publisheremail));
		 if($user===false){
		 	//probably best not to quit if the user is not on the system
		 	//so we default to the "guest" user
		 	$userid=1;
		 }else{
		 	$userid = $user->id;
		 }
	
		// checks if the courseware exists, it shouldn't ...
		$courseware = $DB->get_record('majhub_coursewares', array('hubcourseid' => $courseinfo->id));
		

		 $fullpath = local_majhub_hub_fetch_course_filepath($hubcourseid);
		 //$filename = 'backup_' . $courseid . '.mbz';
		 $filename = basename($fullpath);
		 $filesize = filesize($fullpath);
		 
		 //flag restorable if filesize is ok
		 $maxfilesize = majhub\setting::get('maxrestorablebackupsize');
		 $restorable=true;
		 if($maxfilesize){
			$restorable = $filesize <= $maxfilesize;
		 }
		
		
		
		if(!$courseware){
			$courseware = new stdClass;
			$courseware->userid       = $userid;
			//must not specify a moodle course id (courseid)!!! 
			//cron checks for a null before restoring and does that.
			$courseware->hubcourseid      = $courseinfo->id;
			$courseware->fullname     = $courseinfo->fullname;
			$courseware->shortname    = $courseinfo->shortname;
			$courseware->demourl    = $courseinfo->demourl;
			
			//these are the keys to tying different versions together
			$courseware->siteid = $courseinfo->siteid;
			$courseware->sitecourseid = $courseinfo->sitecourseid;
			
			$courseware->filesize     = $filesize;
			$courseware->unrestorable = !$restorable;
			$courseware->version      = '1.0';
			$courseware->timecreated  = time();
			$courseware->timemodified = $courseware->timecreated;
			$courseware->id = $DB->insert_record('majhub_coursewares', $courseware);
	
		}
	
		//finally do the file copy
		$file =  $storage->copy_to_storage($courseware->id,$fullpath, $filename);
	
		//Then tidyup our courseware record
		$courseware->fileid = $file->get_id();
		//$courseware->deleted =0;
		$courseware->timeuploaded = $file->get_timecreated();
		$courseware->timemodified = $courseware->timeuploaded;
		$DB->update_record('majhub_coursewares', $courseware);
		
		//add a record to our version table also
		//if we got here it would also be good to update our version table
		$versioninfo = new stdClass();
		$versioninfo->coursewareid=$courseware->id;
		$versioninfo->description = get_string('originalversion','local_majhub');
		$versioninfo->fileid = $courseware->filesize;
		$versioninfo->fileid = $courseware->fileid;
		$versioninfo->timecreated = $courseware->timemodified;
		$DB->insert_record('majhub_courseware_versions',$versioninfo);
		
		//call this here rather than wait for cron, so logging to error works and debugging faster
		//local_majhub_cron();
	
}

function local_majhub_hub_course_deleted_handler($courseid)
{
	$courseids = array($courseid);
	 local_majhub_hub_courses_removed_handler($courseids);
}


function local_majhub_hub_courses_removed_handler($courseids)
{
	global $DB,$CFG;
	
	require_once __DIR__.'/classes/courseware.php';
	require_once __DIR__.'/classes/storage.php';
	
	$storage = new majhub\storage();
	foreach($courseids as $courseid){

		$courseware = $DB->get_record('majhub_coursewares', array('hubcourseid' => $courseid));	
		if(!$courseware){return;}
		$courseware->deleted= 1;
		$DB->update_record('majhub_coursewares', $courseware);
		
		//if we have a course set its visible to false
		$course = $DB->get_record('course', array('id' => $courseware->courseid), '*', IGNORE_MISSING);
		if($course){
			$course->visible=0;
			$DB->update_record('course', $course); 
		}
		
	}

}