<?php // $Id: lib.php 227 2013-03-01 06:17:01Z malu $

defined('MOODLE_INTERNAL') || die;

/**
 *  MAJ Hub cron job
 *  
 *  @global moodle_database $DB
 */
function local_majhub_cron()
{
    global $DB;

    require_once __DIR__.'/classes/restore.php';
    require_once __DIR__.'/classes/courseware.php';

    $teacherrole = $DB->get_record('role', array('archetype' => 'teacher'), '*', IGNORE_MULTIPLE);
    $enroller = enrol_get_plugin('manual');

    // gets all uploaded, not restored and not restoring coursewares
    $coursewares = $DB->get_records_select(majhub\courseware::TABLE,
        'deleted = 0 AND fileid IS NOT NULL AND courseid IS NULL AND timestarted IS NULL',
        null, 'timeuploaded ASC');
    foreach ($coursewares as $courseware) try {
        // double check to prevent from being duplicated
        $courseware = majhub\courseware::from_id($courseware->id, MUST_EXIST);
        if (!empty($courseware->courseid) || !empty($courseware->timestarted))
            continue;
        // marks as restoring
        $DB->set_field(majhub\courseware::TABLE, 'timestarted', time(), array('id' => $courseware->id));

        mtrace("  Courseware ID: {$courseware->id}");

        mtrace('    Creating a preview course', '...');
        {
            // restores the uploaded course backup file as a new course
            $courseware->courseid = majhub\restore($courseware->id);
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
        }
        mtrace("done. (courseid: {$courseware->courseid})");

        mtrace('    Assigning a teacher capability for all registered users', '...');
        {
            // assigns a capability for switching roles to non-editing teachers
            $context = context_course::instance($course->id);
            assign_capability('moodle/role:switchroles', CAP_ALLOW, $teacherrole->id, $context->id);

            // enrols all the registered users to the new course as non-editing teachers
            $instanceid = $enroller->add_instance($course);
            $instance = $DB->get_record('enrol', array('id' => $instanceid), '*', MUST_EXIST);
            $users = get_users_confirmed();
            foreach ($users as $user) {
                $enroller->enrol_user($instance, $user->id, $teacherrole->id);
            }
            unset($users); // for memory saving
        }
        mtrace('done.');

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
        error_log($ex->__toString());
    }
}
