<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: capability.php 200 2013-01-30 05:17:17Z malu $
 */
namespace majhub;

/**
 *  Capability
 */
final class capability
{
    /**
     *  Check if the user id site admin
     *  
     *  @global object $USER
     *  @param int|object $user
     *  @return boolean
     */
    public static function is_admin($user = null)
    {
        global $USER;
        return \has_capability('moodle/site:config', \context_system::instance(), $user ?: $USER);
    }

    /**
     *  Check if the user id courseware moderator
     *  
     *  @global object $USER
     *  @global object $COURSE
     *  @param int|object $user
     *  @param int $courseid
     *  @return boolean
     */
    public static function is_moderator($user = null, $courseid = null)
    {
        global $USER, $COURSE;
        return \has_capability('moodle/course:manageactivities',
            \context_course::instance($courseid ?: $COURSE->id), $user ?: $USER);
    }
}
