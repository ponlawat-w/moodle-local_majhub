<?php // $Id: events.php 57 2012-11-14 01:59:32Z malu $

defined('MOODLE_INTERNAL') || die;

$handlers = array(
    'user_created' => array(
        'handlerfile'     => '/local/majhub/lib.php',
        'handlerfunction' => 'local_majhub_user_created_handler',
        'schedule'        => 'instant',
    ),
);
