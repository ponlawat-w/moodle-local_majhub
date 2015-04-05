<?php // $Id: metafields.php 176 2013-01-24 12:11:41Z malu $

require_once __DIR__.'/../../../config.php';


function tag($tagName) { return new majhub\element($tagName); }

if (false) {
    $DB     = new mysqli_native_moodle_database;
    $OUTPUT = new core_renderer;
    $PAGE   = new moodle_page;
}
$id= optional_param('id', 0, PARAM_INT);
$action = optional_param('action', 'nothing', PARAM_TEXT);

require_login();
$context = context_system::instance();
require_capability('local/hub:unregistercourse',$context);

$PAGE->set_url(new moodle_url('/local/majhub/admin/reports.php'));
$PAGE->navbar->ignore_active(true);
$PAGE->navbar->add(get_string('administrationsite'));
$PAGE->navbar->add(get_string('pluginname', 'local_majhub'));


if ($id && $action='resetrestore' && confirm_sesskey()) {
      $resetrec = new \stdClass;
      $resetrec->id=$id;
      $resetrec->timestarted=null;
      $resetrec->courseid=null;
      $DB->update_record('majhub_coursewares', $resetrec);
}
redirect($PAGE->url,get_string('coursewareupdated','local_majhub'));