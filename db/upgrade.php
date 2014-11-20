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
    
    if ($oldversion < 2013101602) {
        $table = new xmldb_table('majhub_coursewares');
        $field = new xmldb_field('hubcourseid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0,'courseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }
    
     if ($oldversion < 2013102200) {
        $table = new xmldb_table('majhub_coursewares');
        //add siteid
        $field = new xmldb_field('siteid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0,'hubcourseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        //add sitecourseid
        $field = new xmldb_field('sitecourseid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0,'siteid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        //set sitecourseid to the correct value, or to a default value that will retain stats. 
        //will require manual fixing up of siteid/sitecourseid if going from hubclient to new publish method 
        $DB->execute(
        		'UPDATE {majhub_coursewares} cw, {hub_course_directory} hcd 
        		SET cw.siteid = hcd.siteid, cw.sitecourseid = hcd.sitecourseid 
        		WHERE cw.hubcourseid = hcd.id'
        		);
        
        $DB->execute(
        		'UPDATE {majhub_coursewares} cw 
        		SET cw.sitecourseid = cw.courseid 
        		WHERE cw.sitecourseid = 0 AND cw.siteid = 0'
        		);
        		
        		
        $DB->execute(
        		'UPDATE {majhub_courseware_downloads} cwd, {majhub_coursewares} cw   
				SET cwd.sitecourseid = cw.courseid 
				WHERE cwd.sitecourseid = 0 AND cwd.siteid = 0 AND cw.id = cwd.coursewareid'
				);

		$DB->execute(
        		'UPDATE {majhub_courseware_reviews} cwr , {majhub_coursewares} cw 
				SET cwr.sitecourseid = cw.courseid 
				WHERE cwr.sitecourseid = 0 AND cwr.siteid = 0 AND cw.id = cwr.coursewareid'
				);
    }
    
     if ($oldversion < 2013102700) {
     	//add siteid and sitecourseid to downloads table
        $table = new xmldb_table('majhub_courseware_downloads');
        $field = new xmldb_field('siteid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0,'coursewareid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('sitecourseid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0,'siteid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
         $DB->execute(
        		'UPDATE {majhub_courseware_downloads} d, {majhub_coursewares} cw 
        		SET d.siteid = cw.siteid, d.sitecourseid = cw.sitecourseid 
        		WHERE d.coursewareid = cw.id'
        		);
        
        //add siteid and sitecourseid to reviews table
        $table = new xmldb_table('majhub_courseware_reviews');
        $field = new xmldb_field('siteid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0,'coursewareid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('sitecourseid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0,'siteid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $DB->execute(
        		'UPDATE {majhub_courseware_reviews} r, {majhub_coursewares} cw 
        		SET r.siteid = cw.siteid, r.sitecourseid = cw.sitecourseid 
        		WHERE r.coursewareid = cw.id'
        		);
    }
	
	 if ($oldversion < 2013110400) {
		 $table = new xmldb_table('majhub_coursewares');
		//add backupversion
		$field = new xmldb_field('backupversion', XMLDB_TYPE_CHAR, 20, null, null, null, 'unknown','sitecourseid');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		//add backup_release
		$field = new xmldb_field('backuprelease', XMLDB_TYPE_CHAR, 50, null, null, null, 'unknown','backupversion');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		
		//add backup_release
		$field = new xmldb_field('unrestorable', XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0,'backuprelease');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
/*
		$DB->execute(
        		'UPDATE {hub_site_directory} hsd, {majhub_coursewares} cw 
        		SET cw.backupversion = hsd.moodleversion, cw.backuprelease = hsd.backuprelease 
        		WHERE cw.siteid = hsd.id'
        		);
*/
	 
	 }
	 
	 if ($oldversion < 2013110500) {
		 $table = new xmldb_table('majhub_bonus_points');
		//add siteid and sitecourseid
		$field = new xmldb_field('siteid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0,'coursewareid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('sitecourseid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0,'siteid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $DB->execute(
        		'UPDATE {majhub_bonus_points} bp, {majhub_coursewares} cw 
        		SET bp.siteid = cw.siteid, bp.sitecourseid = cw.sitecourseid 
        		WHERE bp.coursewareid = cw.id'
        		);
	 
	 }
	 
	if ($oldversion < 2014052600) {
        $table = new xmldb_table('majhub_user_points');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('reason', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null);
        $table->add_field('points', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }
    
    if ($oldversion < 2014111800) {
        $table = new xmldb_table('majhub_courseware_versions');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('coursewareid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null);
        $table->add_field('filesize', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, null);
        $table->add_field('fileid', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        $DB->execute(
        		"INSERT INTO {majhub_courseware_versions} ( `coursewareid`, `description`,`filesize`,`fileid`,`timecreated`) 
        		SELECT id,'Original File',filesize,fileid,timecreated FROM {majhub_coursewares}"
        		);
	 
    }

    
    

    return true;
}
