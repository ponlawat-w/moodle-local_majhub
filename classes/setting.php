<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: setting.php 180 2013-01-27 11:04:47Z malu $
 */
namespace majhub;

/**
 *  Setting
 */
class setting
{
    const TABLE = 'majhub_settings';

    private static $_cache = array();

    /**
     *  Gets a setting value by name
     *  
     *  @global \moodle_database $DB
     *  @param string $name
     *  @param string $default
     *  @return string
     */
    public static function get($name, $default = null)
    {
        global $DB;
        if (isset(self::$_cache[$name]))
            return self::$_cache[$name];
        $record = $DB->get_record(self::TABLE, array('name' => $name), 'value');
        return self::$_cache[$name] = ($record ? $record->value : $default);
    }

    /**
     *  Sets a setting value for name
     *  
     *  @global \moodle_database $DB
     *  @param string $name
     *  @param string $value
     *  @return string
     */
    public static function set($name, $value)
    {
        global $DB;
        $record = $DB->get_record(self::TABLE, array('name' => $name));
        if ($record) {
            $record->value        = (string)$value;
            $record->timemodified = time();
            $DB->update_record(self::TABLE, $record);
        } else {
            $record = new \stdClass;
            $record->name         = $name;
            $record->value        = (string)$value;
            $record->timecreated  = time();
            $record->timemodified = time();
            $DB->insert_record(self::TABLE, $record);
        }
        return self::$_cache[$name] = $value;
    }
}
