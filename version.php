<?php // $Id: version.php 227 2013-03-01 06:17:01Z malu $

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2018011500;
$plugin->release   = '2.6, release 1';
$plugin->requires  = 2012062500.00; // Moodle 2.3.0
$plugin->component = 'local_majhub';

$plugin->dependencies = array(
    'block_majhub'        => 2018011500,
    'block_majhub_points' => 2013030101,
);
