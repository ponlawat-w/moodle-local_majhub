<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: point.php 227 2013-03-01 06:17:01Z malu $
 */
namespace majhub;

require_once __DIR__.'/setting.php';
require_once __DIR__.'/courseware.php';

/**
 *  Point
 *  
 *  @property-read int $registration
 *  @property-read int $upload
 *  @property-read int $review
 *  @property-read int $popularity
 *  @property-read int $quality
 *  @property-read int $download
 *  @property-read int $total
 */
class point
{
    /** @var int */
    public $userid;

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
        case 'registration':
            return $this->_cache[$name] = self::get_settings()->pointsforregistration;
        case 'upload':
            $uploadingcount = $DB->count_records_sql(
                'SELECT COUNT(cw.id) FROM {' . courseware::TABLE . '} cw
                 WHERE cw.userid = :userid AND cw.courseid IS NOT NULL AND cw.deleted = 0',
                array('userid' => $this->userid)
                );
            return $this->_cache[$name] = self::get_settings()->pointsforuploading * $uploadingcount;
        case 'review':
            $reviewingcount = $DB->count_records(review::TABLE, array('userid' => $this->userid));
            return $this->_cache[$name] = self::get_settings()->pointsforreviewing * $reviewingcount;
        case 'popularity':
            $popularcoursewares = $DB->get_records_sql(
                'SELECT cw.id, COUNT(d.id) FROM {' . courseware::TABLE . '} cw
                 JOIN {majhub_courseware_downloads} d ON d.coursewareid = cw.id
                 WHERE cw.userid = :userid AND d.userid <> cw.userid
                 GROUP BY cw.id HAVING COUNT(d.id) >= :countforpopularity',
                array('userid' => $this->userid, 'countforpopularity' => self::get_settings()->countforpopularity)
                );
         
            return $this->_cache[$name] = self::get_settings()->pointsforpopularity * count($popularcoursewares);
       
        case 'quality':
        
        	$ret = $DB->get_record_sql(
                'SELECT SUM(b.points) AS pointtotal FROM {majhub_bonus_points} b
                 JOIN {' . courseware::TABLE . '} cw ON cw.id = b.coursewareid
                 WHERE cw.userid = :userid AND b.reason = :reason',
                array('userid' => $this->userid, 'reason' => 'quality')
                );
            
     
            if(is_object($ret) && is_numeric($ret->pointtotal) && $ret->pointtotal > 0 ){
            	return $this->_cache[$name] = $ret->pointtotal;
            	unset($ret);
            }else{
            	return $this->_cache[$name] = 0;
            }
        /*
            return $this->_cache[$name] = $DB->count_records_sql(
                'SELECT SUM(b.points) FROM {majhub_bonus_points} b
                 JOIN {' . courseware::TABLE . '} cw ON cw.id = b.coursewareid
                 WHERE cw.userid = :userid AND b.reason = :reason',
                array('userid' => $this->userid, 'reason' => 'quality')
                );
                */
    	case 'user':
        
        	$ret = $DB->get_record_sql(
                'SELECT SUM(u.points) AS pointtotal FROM {majhub_user_points} u
                 WHERE u.userid = :userid',
                array('userid' => $this->userid)
                );
            
     
            if(is_object($ret) && is_numeric($ret->pointtotal) && $ret->pointtotal > 0 ){
            	return $this->_cache[$name] = $ret->pointtotal;
            	unset($ret);
            }else{
            	return $this->_cache[$name] = 0;
            }
            
        case 'download':
        $downloadingcount = $DB->count_records_sql(
                'SELECT COUNT(d.id) FROM {majhub_courseware_downloads} d
                 JOIN {' . courseware::TABLE . '} cw ON cw.id = d.coursewareid
                 WHERE d.userid = :userid AND cw.userid <> d.userid',
                array('userid' => $this->userid)
                );
       
            return $this->_cache[$name] = self::get_settings()->pointsfordownloading * $downloadingcount;
            
        case 'total':
            return $this->registration
                 + $this->upload
                 + $this->review
                 + $this->popularity
                 + $this->quality
                 + $this->user
                 - $this->download;
        }
        throw new \InvalidArgumentException();
    }

    /**
     *  Creates an instance of a point from userid
     *  
     *  @param int $userid
     *  @return point
     */
    public static function from_userid($userid)
    {
        $point = new point;
        $point->userid = $userid;
        return $point;
    }

    /**
     *  Gets the point system settings
     *  
     *  @return pointsettings
     */
    public static function get_settings()
    {
        static $settings = null;
        if ($settings === null) {
            $settings = new pointsettings;
            foreach (get_object_vars($settings) as $name => $default)
                $settings->{$name} = setting::get($name, $default);
        }
        return $settings;
    }
}

/**
 *  Point system settings
 */
class pointsettings
{
    /** @var int */
    public $pointsforregistration = 10;
    /** @var int */
    public $pointsforuploading    = 20;
    /** @var int */
    public $pointsforreviewing    = 5;
    /** @var int */
    public $pointsforquality      = 20;
    /** @var int */
    public $pointsforpopularity   = 20;
    /** @var int */
    public $countforpopularity    = 10;
    /** @var int */
    public $lengthforreviewing    = 100;
    /** @var int */
    public $pointsfordownloading  = 10;
}
