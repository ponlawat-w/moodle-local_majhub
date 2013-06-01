<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: exception.php 104 2012-11-23 04:59:04Z malu $
 */
namespace majhub;

/**
 *  MAJ Hub exception
 */
class exception extends \moodle_exception
{
    /**
     *  Constructor
     *  
     *  @param string $code  The error string ID without prefix "error:"
     *  @param mixed  $a     (Optional) Additional parameter
     */
    public function __construct($code, $a = null)
    {
        parent::__construct("error:$code", 'local_majhub', '', $a);
    }
}
