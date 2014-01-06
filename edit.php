<?php // $Id: edit.php 230 2013-03-01 08:48:24Z malu $

require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../lib/filelib.php';
require_once __DIR__.'/classes/courseware.php';
require_once __DIR__.'/classes/capability.php';
require_once __DIR__.'/classes/element.php';
require_once __DIR__.'/classes/user.php';

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
	$updatedcontributor = optional_param('updatedcontributor', null, PARAM_INT);
    if ($updatedcontributor) {
        $courseware->userid = $updatedcontributor; 
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
    if (empty($invalidfields)) {
        $courseware->update();
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

//if admin, display a selector so we can change the contributor
if($isadmin){
	$selector = new majhub\majhub_user_selector('updatedcontributor', array());
	$selectorhtml = get_string('selectnewcontributor', 'local_majhub');
	$selectorhtml .= $selector->display(true);
}


    
$fixedrows = array(
    get_string('title', 'local_majhub')       => $courseware->fullname,
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
foreach ($fixedrows as $name => $value) {
    echo row($name, $value);
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
