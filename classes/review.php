<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: review.php 180 2013-01-27 11:04:47Z malu $
 */
namespace majhub;

require_once __DIR__.'/courseware.php';

/**
 *  Review
 *  
 *  @property-read \stdClass $user
 *  @property-read courseware $courseware
 */
class review
{
    const TABLE = 'majhub_courseware_reviews';

    /** @var int */
    public $id;
    /** @var int */
    public $userid;
    /** @var int */
    public $coursewareid;
    /** @var int */
    public $rating;
    /** @var string */
    public $comment;
    /** @var int */
    public $timecreated;
    /** @var int */
    public $timemodified;

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
        case 'courseware':
            return $this->_cache[$name] = courseware::from_id($this->coursewareid, MUST_EXIST);
        }
        throw new \InvalidArgumentException();
    }

    /**
     *  Creates an instance of a review from a record
     *  
     *  @param object $record
     *  @return review
     */
    public static function from_record($record)
    {
        $review = new review;
        $review->id           = (int)$record->id;
        $review->userid       = (int)$record->userid;
        $review->coursewareid = (int)$record->coursewareid;
        $review->rating       = (int)$record->rating;
        $review->comment      = trim($record->comment);
        $review->timecreated  = (int)$record->timecreated;
        $review->timemodified = (int)$record->timemodified;
        return $review;
    }
}
