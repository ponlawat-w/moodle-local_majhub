<?php
/**
 *  Custom script for /login/confirm.php
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: confirm.php 204 2013-02-01 03:11:30Z malu $
 */

ob_start(function ($buffer)
{
    // removes the link to "All Courses", that is confusing for Hub users
    return preg_replace('@<form[^>]+action="[^"]*/course/"[^>]*>.+?</form>@msi', '', $buffer);
});
