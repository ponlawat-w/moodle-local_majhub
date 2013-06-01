<?php // $Id: create.php 176 2013-01-24 12:11:41Z malu $

require_once __DIR__.'/../../../config.php';

if (false) {
    $USER = new stdClass;
    $DB   = new mysqli_native_moodle_database;
}

try {
    require_login(null, false, null, false, true);

    $fullname  = required_param('fullname', PARAM_TEXT);
    $shortname = required_param('shortname', PARAM_TEXT);
    $filesize  = required_param('filesize', PARAM_INT); // TODO: how do we handle large file >2GiB?

    $courseware = new stdClass;
    $courseware->userid       = $USER->id;
    $courseware->fullname     = $fullname;
    $courseware->shortname    = $shortname;
    $courseware->filesize     = $filesize;
    $courseware->version      = '1.0';
    $courseware->timecreated  = time();
    $courseware->timemodified = $courseware->timecreated;
    $courseware->id = $DB->insert_record('majhub_coursewares', $courseware);

    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = true;
    $root = $doc->appendChild($doc->createElement('majhub'));
    $node = $root->appendChild($doc->createElement('courseware'));
    $node->setAttribute('id', $courseware->id);
    send_headers('application/xml; charset=UTF-8');
    echo $doc->saveXML();

} catch (Exception $ex) {
    header('HTTP/1.1 400 Bad Request');
    // TODO: respond error to client
    error_log($ex->__toString());
}
