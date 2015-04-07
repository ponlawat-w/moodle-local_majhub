<?php // $Id: events.php

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
	'classname' => 'local_majhub\task\cron_task',                                                            
    'blocking' => 0,                                                                                             
    'minute' => '*/5',
	'hour' => '*',
	'day' => '*',
	'dayofweek' => '*',
	'month' => '*'
	)
);
