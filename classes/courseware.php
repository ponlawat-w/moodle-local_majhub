<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: courseware.php 222 2013-02-22 03:52:44Z malu $
 */
namespace majhub;

require_once __DIR__.'/metadatum.php';
require_once __DIR__.'/review.php';

/**
 *  Courseware
 *  
 *  @property-read \stdClass $user
 *  @property-read \stdClass $course
 *  @property-read \stored_file $file
 *  @property-read metadatum[] $metadata
 *  @property-read float $avarage_rating
 *  @property-read int $number_of_reviews
 *  @property-read string $unique_fullname   The course full name with the unique id prefix
 *  @property-read string $unique_shortname  The course short name with the unique id prefix
 *  @property-read boolean $missing          True if preview course has been deleted
 */
class courseware
{
    const TABLE = 'majhub_coursewares';

    /** @var int */
    public $id;
    /** @var int */
    public $userid;
    /** @var string */
    public $fullname;
    /** @var string */
    public $shortname;
    /** @var int */
    public $filesize;
    /** @var string|null */
    public $demourl;
    /** @var int|null */
    public $fileid;
    /** @var int|null */
    public $courseid;
    /** @var int|null */
    public $hubcourseid;
    /** @var int|null */
    public $previousid;
    /** @var string */
    public $version;
    /** @var boolean */
    public $deleted;
    /** @var int */
    public $timecreated;
    /** @var int */
    public $timemodified;
    /** @var int|null */
    public $timeuploaded;
    /** @var int|null */
    public $timerestored;
    /** @var int|null */
    public $timestarted;
    /** @var int|null */
    public $timeupdated;

    /** @var array */
    private $_cache;

    /**
     *  Constructor
     */
    public function __construct()
    {
        $this->_cache = array();
    }

    /**
     *  Gets a dymanic property
     *  
     *  @global \moodle_database $DB
     *  @param string $name
     *  @return mixed
     */
    public function __get($name)
    {
        global $DB;

        if (isset($this->_cache[$name]))
            return $this->_cache[$name];

        switch ($name) {
        case 'user':
            return $this->_cache[$name] = $DB->get_record('user', array('id' => $this->userid), '*', MUST_EXIST);
        case 'course':
            return $this->_cache[$name] = $DB->get_record('course', array('id' => $this->courseid), '*', MUST_EXIST);
        case 'file':
            return $this->_cache[$name] = \get_file_storage()->get_file_by_id($this->fileid);
        case 'metadata':
            $records = $DB->get_records_sql(
                'SELECT f.*, d.value
                 FROM {' . metafield::TABLE . '} f
                 LEFT JOIN {' . metadatum::TABLE . '} d ON d.metafieldid = f.id AND d.coursewareid = ?
                 ORDER BY f.optional ASC, f.weight ASC',
                array($this->id)
                );
            return $this->_cache[$name] = array_reduce($records, function (\ArrayIterator $metadata, $record)
            {
                $metadatum = new metadatum;
                $metadatum->field = metafield::from_record($record);
                $metadatum->value = trim($record->value);
                if ($metadatum->type == metafield::TYPE_CHECK) {
                    $metadatum->value = preg_split('/[\r\n]+/', $metadatum->value, -1, PREG_SPLIT_NO_EMPTY);
                }
                $metadata[$metadatum->id] = $metadatum;
                return $metadata;
            }, new \ArrayIterator);
        case 'avarage_rating':
            return $this->_cache[$name] = $DB->get_field(
                review::TABLE, 'AVG(rating)', array('coursewareid' => $this->id));
        case 'number_of_reviews':
            return $this->_cache[$name] = $DB->count_records(review::TABLE, array('coursewareid' => $this->id));
        case 'unique_fullname':
            return $this->_cache[$name] = self::generate_unique_name($this->id, $this->fullname);
        case 'unique_shortname':
            return $this->_cache[$name] = self::generate_unique_name($this->id, $this->shortname);
        case 'missing':
            return $this->_cache[$name] = $this->courseid
                && !$DB->record_exists('course', array('id' => $this->courseid));
        }
        throw new \InvalidArgumentException();
    }

    /**
     *  Gets whether courseware is reviewed by the user
     *  
     *  @global \moodle_database $DB
     *  @param int $userid
     *  @return boolean
     */
    public function is_reviewed_by($userid)
    {
        global $DB;
        return $DB->record_exists(review::TABLE, array('coursewareid' => $this->id, 'userid' => $userid));
    }

    /**
     *  Gets courseware reviews
     *  
     *  @global \moodle_database $DB
     *  @param int $sort
     *  @return review[]
     */
    public function get_reviews($limit = null, $sort = 'timemodified DESC')
    {
        global $DB;

        $records = $DB->get_records(review::TABLE, array('coursewareid' => $this->id), $sort, '*', 0, (int)$limit);
        return array_map(function ($record) { return review::from_record($record); }, $records);
    }

    /**
     *  Updates metadata
     *  
     *  @global \moodle_database $DB
     */
    public function update()
    {
        global $DB;

        foreach ($this->metadata as $metadatum) {
            $value = is_array($metadatum->value)
                   ? ("\n" . implode("\n", $metadatum->value) . "\n")
                   : $metadatum->value;
            $record = $DB->get_record(metadatum::TABLE,
                array('coursewareid' => $this->id, 'metafieldid' => $metadatum->id));
            if ($record) {
                $record->value        = $value;
                $record->timemodified = time();
                $DB->update_record(metadatum::TABLE, $record);
            } else {
                $record = new \stdClass;
                $record->coursewareid = $this->id;
                $record->metafieldid  = $metadatum->id;
                $record->value        = $value;
                $record->timecreated  = time();
                $record->timemodified = time();
                $DB->insert_record(metadatum::TABLE, $record);
            }
        }
        $this->timemodified = time();
        $DB->update_record(self::TABLE, $this);
    }

    /**
     *  Creates an instance of a courseware from a record
     *  
     *  @param object $record
     *  @return courseware
     */
    public static function from_record($record)
    {
        $courseware = new courseware;
        $courseware->id           = (int)$record->id;
        $courseware->userid       = (int)$record->userid;
        $courseware->fullname     = trim($record->fullname);
        $courseware->shortname    = trim($record->shortname);
        $courseware->filesize     = (int)$record->filesize;
        $courseware->demourl      = isset($record->demourl)    ? trim($record->demourl)   : null;
        $courseware->fileid       = isset($record->fileid)     ? (int)$record->fileid     : null;
        $courseware->courseid     = isset($record->courseid)   ? (int)$record->courseid   : null;
        $courseware->hubcourseid  = isset($record->hubcourseid)   ? (int)$record->hubcourseid   : null;
        $courseware->previousid   = isset($record->previousid) ? (int)$record->previousid : null;
        $courseware->version      = trim($record->version);
        $courseware->deleted      = (boolean)$record->deleted;
        $courseware->timecreated  = (int)$record->timecreated;
        $courseware->timemodified = (int)$record->timemodified;
        $courseware->timeuploaded = isset($record->timeuploaded) ? (int)$record->timeuploaded : null;
        $courseware->timerestored = isset($record->timerestored) ? (int)$record->timerestored : null;
        $courseware->timestarted  = isset($record->timestarted)  ? (int)$record->timestarted  : null;
        $courseware->timeupdated  = isset($record->timeupdated)  ? (int)$record->timeupdated  : null;
        return $courseware;
    }

    /**
     *  Creates an instance of a courseware from id
     *  
     *  @global \moodle_database $DB
     *  @param int $coursewareid
     *  @param int $strictness
     *  @return courseware|null
     */
    public static function from_id($coursewareid, $strictness = IGNORE_MISSING)
    {
        global $DB;

        $record = $DB->get_record(self::TABLE, array('id' => $coursewareid), '*', $strictness);
        return $record ? self::from_record($record) : null;
    }

    /**
     *  Creates an instance of a courseware from course id
     *  
     *  @global \moodle_database $DB
     *  @param int $courseid
     *  @param int $strictness
     *  @return courseware|null
     */
    public static function from_courseid($courseid, $strictness = IGNORE_MISSING)
    {
        global $DB;

        $record = $DB->get_record(self::TABLE, array('courseid' => $courseid, 'deleted' => 0), '*', $strictness);
        return $record ? self::from_record($record) : null;
    }

    /**
     *  Generates a unique name for course fullname or shortname
     *  
     *  @param int $id
     *  @param string $name
     *  @return string
     */
    public static function generate_unique_name($id, $name)
    {
        return sprintf('#%d. %s', $id, $name);
    }
}
