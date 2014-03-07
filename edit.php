<?php // $Id: edit.php 230 2013-03-01 08:48:24Z malu $

require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../lib/filelib.php';
require_once __DIR__.'/classes/courseware.php';
require_once __DIR__.'/classes/capability.php';
require_once __DIR__.'/classes/element.php';
require_once __DIR__.'/classes/user.php';
require_once __DIR__.'/lib.php';

function tag($tagName) { return new majhub\element($tagName); }

if (false) {
    $DB     = new mysqli_native_moodle_database;
    $CFG    = new stdClass;
    $USER   = new stdClass;
    $OUTPUT = new core_renderer;
    $PAGE   = new moodle_page;
}

$id = required_param('id', PARAM_INT);

$courseware = majhub\courseware::from_id($id);
if (!$courseware || $courseware->deleted || $courseware->missing) {
    if ($courseware && $courseware->missing) {
        // deletes a missing courseware (TODO: listen course deletion and do this immediately)
        $DB->set_field('majhub_coursewares', 'deleted', 1, array('id' => $courseware->id));
    }
    if (isset($_SERVER['HTTP_REFERER'])) {
        // check if wwwroot is different to avoid redirection loop
        if (substr_compare($_SERVER['HTTP_REFERER'], $CFG->wwwroot, 0, strlen($CFG->wwwroot), true) != 0) {
            redirect($_SERVER['HTTP_REFERER'] . "#missingcourseware={$id}");
        }
    }
    print_error('error:missingcourseware', 'local_majhub', null, $id);
}

$PAGE->set_url('/local/majhub/edit.php', array('id' => $id));
$PAGE->set_context(context_system::instance());
$PAGE->set_cacheable(false);

require_login();

$isowner = $courseware->userid == $USER->id;
$isadmin = majhub\capability::is_admin($USER);
if (!$isowner && !$isadmin)
    throw new majhub\exception('accessdenied');

$courseurl = $courseware->courseid ? new moodle_url('/course/view.php', array('id' => $courseware->courseid)) : null;

if (optional_param('updatemetadata', null, PARAM_TEXT)) {
    $demourl = optional_param('demourl', null, PARAM_TEXT);
    if ($demourl) {
        $response = download_file_content($demourl, null, null, true);
        if (!$response || $response->status != 200)
            $demourl = null;
    }
	
	 //update the course title / fullname
	$fullname = optional_param('fullname', null, PARAM_TEXT);
    if ($fullname) {
        $courseware->fullname = $fullname; 
    }
	
    //update the contributing user
	$updatedcontributor = optional_param('updatedcontributor', null, PARAM_INT);
    if ($updatedcontributor) {
        $courseware->userid = $updatedcontributor; 
    }
    
    //update the contributing site
	$updatedsiteid = optional_param('updatedsiteid', 0, PARAM_INT);
	$updatedsitecourseid = optional_param('updatedsitecourseid', 0, PARAM_INT);
	$oldsitecourseid=false;
	$oldsiteid=false;
	
	if($updatedsiteid  && $updatedsitecourseid){ 
		if($courseware->sitecourseid != $updatedsitecourseid || $courseware->siteid != $updatedsiteid){
				$oldsitecourseid=$courseware->sitecourseid;
				$oldsiteid=$courseware->siteid;
		}
    	//update the contributing site
		$courseware->siteid = $updatedsiteid; 

    	//update the contributing sitecourseid
    	$courseware->sitecourseid = $updatedsitecourseid;
	}
	

    $courseware->demourl = empty($demourl) ? null : $demourl;
    $invalidfields = array();
    if (isset($_POST['metadata']) && is_array($_POST['metadata'])) {
        $values = $_POST['metadata'];
        foreach ($courseware->metadata as $metadatum) {
            if (isset($values[$metadatum->id]))
                $metadatum->set_form_value($values[$metadatum->id]);
            if ($metadatum->required) {
                if (!isset($values[$metadatum->id]) || strlen($values[$metadatum->id]) == 0)
                    $invalidfields[$metadatum->id] = true;
            }
        }
    }
    
    
    /*when the sync does not occur, following an upload, this will do it */
    $resync =  optional_param('resync', 0, PARAM_INT);
    if($resync && $isadmin){
    	local_majhub_hub_course_received_handler($resync);
    }
    
    if (empty($invalidfields)) {
        $courseware->update();
        //if we changed the siteids, lets change on the standard hub too
        if($oldsiteid && $oldsitecourseid){
        	 $course = new stdClass();
        	$course->id = $courseware->hubcourseid;
        	$course->siteid = $updatedsiteid;
        	$course->sitecourseid = $updatedsitecourseid;
            $DB->update_record('hub_course_directory', $course);
        	 
        }
        
        redirect($courseurl ?: new moodle_url($PAGE->url, array('updated' => true)));
    }
}

// uses topics format style
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-topics');

$PAGE->set_title(get_string('course') . ': ' . $courseware->unique_fullname);
$PAGE->set_heading($courseware->unique_fullname);
$PAGE->navbar->add(get_string('mycourses'));
$PAGE->navbar->add($courseware->unique_shortname, $courseurl);
$PAGE->navbar->add(get_string('editcoursewaremetadata', 'local_majhub'), $PAGE->url);

$PAGE->requires->css('/local/majhub/edit.css');

echo $OUTPUT->header();

echo $div_course_content = tag('div')->classes('course-content')->start();
echo $ul_topics = tag('ul')->classes('topics')->start();
echo $li_section = tag('li')->classes('section', 'main', 'clearfix')->start();
echo $div_content = tag('div')->classes('content')->start();

echo tag('h2')->classes('main')->append(get_string('editcoursewaremetadata', 'local_majhub'));

if (optional_param('updated', null, PARAM_TEXT)) {
    echo $div_message = tag('div')->classes('message')->start();
    echo get_string('changessaved');
    if (!$courseware->courseid) {
        echo $OUTPUT->pix_icon('i/scheduled', '');
        echo tag('span')->append(get_string('previewcourseisnotready', 'local_majhub'));
    }
    echo $div_message->end();
}

//by default show the user who is currently the course owner
$userlink = $OUTPUT->action_link(
	new moodle_url('/user/profile.php', array('id' => $courseware->user->id)),
	fullname($courseware->user)
	);

//if admin, display a selectors so we can update contributor, site and sitecourseid
if($isadmin){
	$selector = new majhub\majhub_user_selector('updatedcontributor', array());
	$selectorhtml = get_string('selectnewcontributor', 'local_majhub');
	$selectorhtml .= $selector->display(true);
}else{
	$selectorhtml= "";
}


    
$fixedrows = array(
   // get_string('title', 'local_majhub')       => $courseware->fullname,
    get_string('contributor', 'local_majhub') => $userlink,
	"" => $selectorhtml,
    get_string('uploadedat', 'local_majhub')  => userdate($courseware->timeuploaded),
    get_string('filesize', 'local_majhub')    => display_size($courseware->filesize),
//  get_string('version', 'local_majhub')     => $courseware->version,
    );
 
 


echo $form = tag('form')->action($PAGE->url)->method('post')->classes('mform')->start();
echo tag('div')->style('display', 'none')->append(
    tag('input')->type('hidden')->name('id')->value($id)
    );
echo $table = tag('table')->classes('metadata')->start();

//added the ability to edit the title of the course
if($isadmin){
echo row(get_string('title', 'local_majhub'),
    tag('input')->type('text')->name('fullname')->value($courseware->fullname)->size(50)
    );
}

foreach ($fixedrows as $name => $value) {
    echo row($name, $value);
}

//if admin, display selectors so we can select site id
if($isadmin){
	$options = $DB->get_records_select_menu('hub_site_directory','');
	if($options){
		//output site select
		echo row(get_string('site', 'local_majhub'), html_writer::select($options, 'updatedsiteid', $courseware->siteid));
		//output site course id input
		$inputattributes=array();
		$inputattributes['name'] = "updatedsitecourseid";
		$inputattributes['value'] = $courseware->sitecourseid;	
		echo row(get_string('sitecourseid', 'local_majhub'), html_writer::empty_tag('input', $inputattributes));
	}
}


echo row(get_string('demourl', 'local_majhub'),
    tag('input')->type('text')->name('demourl')->value($courseware->demourl)->size(50)
    );
foreach ($courseware->metadata as $metadatum) {
    $name = $metadatum->name;
    $attr = null;
    if ($metadatum->required) {
        $attr = 'required';
        $name = $name . $OUTPUT->pix_icon('req', get_string('required'), '', array('class' => 'req'));
    } elseif ($metadatum->optional) {
        $attr = 'optional';
    }
    echo row($name, $metadatum->render_form_element('metadata'), $attr);
}


//if admin, display an option to rysync an upload that arrived at hub butnot maj hub
if($isadmin){
	$result = $DB->get_records('majhub_coursewares',array(),null,'hubcourseid');
	if($result){
		$idset = array();
		foreach($result as $rowdata){
			$idset[] = $rowdata->hubcourseid;
		}
		$notset = " id NOT IN (" . implode(',',$idset) .  ") ";
	
		$sort='id';
		$selectfields="id,concat(id,fullname)";
		$options = $DB->get_records_select_menu('hub_course_directory',$notset, null, $sort, $selectfields);
		//print_r($options);
		echo row(get_string('resync', 'local_majhub'), html_writer::select($options, 'resync'));
	}
}


$buttons = tag('input')->type('submit')->name('updatemetadata')->value(get_string('savechanges'));
if ($courseurl) {
    $buttons .= '  ';
    $buttons .= tag('input')->type('button')->value(get_string('cancel'))->onclick("location.href = '$courseurl'");
}
echo row('', $buttons);
echo $table->end();
echo $form->end();

echo $div_content->end();
echo $li_section->end();
echo $ul_topics->end();
echo $div_course_content->end();



echo $OUTPUT->footer();

function row($th, $td, $attr = null)
{
    $tr = tag('tr')->append(tag('th')->append($th), tag('td')->append($td));
    if ($attr)
        $tr->classes($attr);
    return $tr;
}
