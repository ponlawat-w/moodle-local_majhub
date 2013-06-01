<?php // $Id: uninstall.php 145 2012-12-01 08:08:11Z malu $

defined('MOODLE_INTERNAL') || die;

/**
 *  MAJ Hub uninstallation
 *  
 *  @return boolean
 */
function xmldb_local_majhub_uninstall()
{
    require_once __DIR__.'/../classes/storage.php';
    majhub\storage::uninstall();

    // TODO: should we delete all preview courses?

    return true;
}
