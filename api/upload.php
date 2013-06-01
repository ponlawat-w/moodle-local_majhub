<?php // $Id: upload.php 217 2013-02-21 11:10:47Z malu $

require_once __DIR__.'/../../../config.php';
require_once __DIR__.'/../classes/utils.php';
require_once __DIR__.'/../classes/storage.php';

if (false) {
    $DB = new mysqli_native_moodle_database;
}

try {
    require_login(null, false, null, false, true);

    $coursewareid = required_param('courseware', PARAM_INT);
    $position = required_param('position', PARAM_INT); // TODO: how do we handle large file >2GiB?
    $content = majhub\required_file('content');

    // checks if the courseware exists
    $courseware = $DB->get_record('majhub_coursewares', array('id' => $coursewareid), '*', MUST_EXIST);

    $storage = new majhub\storage();
    $partfile = $storage->create_partial_file_from_pathname(
        $courseware->id, $position, $content['tmp_name']);
    if ($position + $partfile->get_filesize() == $courseware->filesize) {
        // if all the parts have been saved, concatenates them into one file
        $file = $storage->concat_partial_files($courseware->id);
        $courseware->fileid = $file->get_id();
        $courseware->timeuploaded = $file->get_timecreated();
        $courseware->timemodified = $courseware->timeuploaded;
        $DB->update_record('majhub_coursewares', $courseware);

        $storage->delete_partial_files($courseware->id);
    }

    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = true;
    $root = $doc->appendChild($doc->createElement('majhub'));
    $node = $root->appendChild($doc->createElement('part'));
    $node->setAttribute('id', $partfile->get_id());
    send_headers('application/xml; charset=UTF-8');
    echo $doc->saveXML();

} catch (Exception $ex) {
    header('HTTP/1.1 400 Bad Request');
    // TODO: respond error to client
    error_log($ex->__toString());
}
