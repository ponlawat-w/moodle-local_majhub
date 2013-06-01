<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: utils.php 104 2012-11-23 04:59:04Z malu $
 */
namespace majhub;

/**
 *  Requires a posted file parameter
 *  
 *  @param string $formname
 *  @return array  $_FILES[$formname]
 *  @throws \moodle_exception
 */
function required_file($formname)
{
    if (!isset($_FILES[$formname]))
        throw new \moodle_exception('nofile');
    if (!empty($_FILES[$formname]['error'])) {
        switch ($_FILES[$formname]['error']) {
        case UPLOAD_ERR_OK        : break;
        case UPLOAD_ERR_INI_SIZE  : throw new \moodle_exception('uploadserverlimit');
        case UPLOAD_ERR_FORM_SIZE : throw new \moodle_exception('uploadformlimit');
        case UPLOAD_ERR_PARTIAL   : throw new \moodle_exception('uploadpartialfile');
        case UPLOAD_ERR_NO_FILE   : throw new \moodle_exception('uploadnofilefound');
        case UPLOAD_ERR_NO_TMP_DIR: throw new \moodle_exception('uploadnotempdir');
        case UPLOAD_ERR_CANT_WRITE: throw new \moodle_exception('uploadcantwrite');
        case UPLOAD_ERR_EXTENSION : throw new \moodle_exception('uploadextension');
        default                   : throw new \moodle_exception('uploadproblem');
        }
    }
    return $_FILES[$formname];
}
