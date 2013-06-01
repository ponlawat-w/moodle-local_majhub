<?php // $Id: settings.php 204 2013-02-01 03:11:30Z malu $

defined('MOODLE_INTERNAL') || die;

if (false) {
    $CFG   = new stdClass;
    $ADMIN = new admin_root(null);
}

$ADMIN->add('root', new admin_category('majhub', new lang_string('pluginname', 'local_majhub')));
if (empty($CFG->customfrontpageinclude) || realpath($CFG->customfrontpageinclude) !== realpath(__DIR__.'/frontpage.php') ||
    empty($CFG->customscripts)          || realpath($CFG->customscripts)          !== realpath(__DIR__.'/customscripts')) {
    // MAJ Hub requires special configuration for custom frontpage
    $ADMIN->add('majhub', new admin_externalpage('majhub/install',
        'INSTALL', new moodle_url('/local/majhub/INSTALL.txt')));
} else {
    $ADMIN->add('majhub', new admin_externalpage('majhub/frontpage',
        new lang_string('settings/frontpage', 'local_majhub'),
        new moodle_url('/local/majhub/admin/frontpage.php')));
    $ADMIN->add('majhub', new admin_externalpage('majhub/metafields',
        new lang_string('settings/metafields', 'local_majhub'),
        new moodle_url('/local/majhub/admin/metafields.php')));
    $ADMIN->add('majhub', new admin_externalpage('majhub/pointsystem',
        new lang_string('settings/pointsystem', 'local_majhub'),
        new moodle_url('/local/majhub/admin/pointsystem.php')));
}
