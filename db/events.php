<?php // $Id: events.php 57 2012-11-14 01:59:32Z malu $

defined('MOODLE_INTERNAL') || die;

$handlers = array(
    'user_created' => array(
        'handlerfile'     => '/local/majhub/lib.php',
        'handlerfunction' => 'local_majhub_user_created_handler',
        'schedule'        => 'instant',
    ),
    'hub_course_received' => array(
        'handlerfile'     => '/local/majhub/lib.php',
        'handlerfunction' => 'local_majhub_hub_course_received_handler',
        'schedule'        => 'instant',
    ),
	'hub_course_deleted' => array(
        'handlerfile'     => '/local/majhub/lib.php',
        'handlerfunction' => 'local_majhub_hub_course_deleted_handler',
        'schedule'        => 'instant',
    ),
    'hub_courses_removed' => array(
        'handlerfile'     => '/local/majhub/lib.php',
        'handlerfunction' => 'local_majhub_hub_courses_removed_handler',
        'schedule'        => 'instant',
    )
);

//added hub courses received by Justin 20131014
//added hub courses removed by Justin 20131015

