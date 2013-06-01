<?php // $Id: upgrade.php 227 2013-03-01 06:17:01Z malu $

defined('MOODLE_INTERNAL') || die;

/**
 *  MAJ Hub upgrade
 *  
 *  @global moodle_database $DB
 *  @return boolean
 */
function xmldb_local_majhub_upgrade($oldversion = 0)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012120100) {
        $table = new xmldb_table('majhub_courseware_extensions');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('pluginname', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }

    if ($oldversion < 2013012400) {
        $table = new xmldb_table('majhub_settings');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }

    if ($oldversion < 2013012700) {
        $table = new xmldb_table('majhub_settings');
        $key = new xmldb_key('name', XMLDB_KEY_UNIQUE, array('name'));
        $dbman->add_key($table, $key);
    }

    if ($oldversion < 2013012801) {
        $table = new xmldb_table('majhub_bonus_points');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('coursewareid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('reason', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null);
        $table->add_field('points', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }

    if ($oldversion < 2013012802) {
        $table = new xmldb_table('majhub_review_proscons');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('reviewid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('procon', XMLDB_TYPE_CHAR, 4, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }

    if ($oldversion < 2013012902) {
        require_once __DIR__.'/../classes/setting.php';
        if (majhub\setting::get('coursewaresperpageoptions') === null)
            majhub\setting::set('coursewaresperpageoptions', '5, 10, 50, 100');
        if (majhub\setting::get('coursewaresperpagedefault') === null)
            majhub\setting::set('coursewaresperpagedefault', '10');
    }

    if ($oldversion < 2013020102) {
        require_once __DIR__.'/../classes/setting.php';
        if (majhub\setting::get('lengthforreviewing') === null)
            majhub\setting::set('lengthforreviewing', 100);
    }

    if ($oldversion < 2013022101) {
        $table = new xmldb_table('majhub_coursewares');
        $field = new xmldb_field('timestarted', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null, null, null, 'timerestored');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2013022102) {
        $DB->execute('UPDATE {majhub_coursewares} SET timestarted = timeuploaded
                      WHERE courseid IS NOT NULL AND timestarted IS NULL');
    }

    if ($oldversion < 2013022104) {
        // deletes old coursewares having same courseid
        $duplicates = $DB->get_records_sql('SELECT courseid FROM {majhub_coursewares}
                                            WHERE deleted = 0 AND courseid IS NOT NULL
                                            GROUP BY courseid HAVING COUNT(*) > 1');
        foreach ($duplicates as $dup) {
            $latest = $DB->get_records('majhub_coursewares',
                array('courseid' => $dup->courseid, 'deleted' => 0), 'timerestored DESC', 'id', 0, 1);
            $latest = reset($latest);
            $DB->execute(
                'UPDATE {majhub_coursewares} SET deleted = 1 WHERE courseid = :courseid AND id <> :coursewareid',
                array('courseid' => $dup->courseid, 'coursewareid' => $latest->id)
                );
        }
    }

    if ($oldversion < 2013030102) {
        $courses = $DB->get_records_sql(
            'SELECT DISTINCT c.* FROM {course} c JOIN {majhub_coursewares} cw ON cw.courseid = c.id');
        foreach ($courses as $course) {
            $page = new moodle_page();
            $page->set_course($course);
            $page->set_pagelayout('course');
            $page->set_pagetype('course-view-' . $course->format);
            $page->blocks->load_blocks();
            if (!$page->blocks->is_block_present('majhub_points')) {
                $page->blocks->add_block('majhub_points', BLOCK_POS_LEFT, -1, false);
            }
        }
    }

    return true;
}
