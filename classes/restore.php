<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: restore.php 176 2013-01-24 12:11:41Z malu $
 */
namespace majhub;

require_once __DIR__.'/scoped.php';

/**
 *  Restores a courseware as a new course
 *  
 *  @global object $CFG
 *  @global \moodle_database $DB
 *  @param int $coursewareid
 *  @return int  A new course id
 *  @throws \moodle_exception
 */
function restore($coursewareid)
{
    global $CFG, $DB;

    require_once __DIR__.'/../../../backup/util/includes/restore_includes.php';

    // checks if the courseware exists and is not restored yet
    $courseware = $DB->get_record('majhub_coursewares', array('id' => $coursewareid), '*', MUST_EXIST);
    if (!empty($courseware->courseid))
        throw new \moodle_exception('error_course_already_exists', 'error', $courseware->courseid);

    $admin = \get_admin();
    $fs = \get_file_storage();

    // cleanups temporary files when this function returns
    $tempfiles = array();
    $scope = new scoped(function () use (&$tempfiles)
    {
        foreach (array_reverse($tempfiles) as $tempfile)
            \fulldelete($tempfile);
    });

    // prepares the restore working directory
    $workdir = $CFG->dataroot . '/temp/backup';
    if (!\check_dir_exists($workdir, true, true))
        throw new \moodle_exception('error_creating_temp_dir', 'error', $workdir);

    $tempdir = sprintf('%s-%d', date('Ymd-His'), $courseware->id);

    // copies the backup archive into the working directory
    $tempzip = \restore_controller::get_tempdir_name();
    $file = $fs->get_file_by_id($courseware->fileid);
    $file->copy_content_to("$workdir/$tempzip");
    $tempfiles[] = "$workdir/$tempzip";

    // extracts the archive in the working directory
    $packer = new \zip_packer();
    $packer->extract_to_pathname("$workdir/$tempzip", "$workdir/$tempdir");
    $tempfiles[] = "$workdir/$tempdir";

    // restores the backup as a new course in the fist top-level category
    $categories = $DB->get_records('course_categories', array('parent' => 0), 'id ASC', '*', 0, 1);
    $category = reset($categories);
    $fullname = sprintf('#%d. %s', $courseware->id, $courseware->fullname);
    $shortname = sprintf('#%d. %s', $courseware->id, $courseware->shortname);
    $courseid = \restore_dbops::create_new_course($fullname, $shortname, $category->id);
    $rc = new \restore_controller($tempdir, $courseid,
        \backup::INTERACTIVE_NO, \backup::MODE_HUB, $admin->id, \backup::TARGET_NEW_COURSE);
    // TODO: detect if the course requires non-standard plugins
    $rc->set_status(\backup::STATUS_AWAITING);
    $rc->execute_plan();
    $rc->destroy();

    unset($scope);

    // updates the courseware record
    $courseware->courseid = $courseid;
    $courseware->timerestored = time();
    $courseware->timemodified = $courseware->timerestored;
    $DB->update_record('majhub_coursewares', $courseware);

    return $courseid;
}
