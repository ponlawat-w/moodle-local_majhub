<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: storage.php 104 2012-11-23 04:59:04Z malu $
 */
namespace majhub;

require_once __DIR__.'/scoped.php';
require_once(dirname(dirname(__FILE__)).'/lib.php');

/**
 *  MAJ Hub file storage manager
 */
class storage
{
    const COMPONENT = 'majhub';
    const FILEAREA  = 'backup';

    /** @var \file_storage */
    private $storage;
    /** @var \context */
    private $context;

    /**
     *  Constructor
     */
    public function __construct()
    {
        $this->storage = \get_file_storage();
        $this->context = \context_system::instance();
    }

    /**
     *  Creates a stored file from a part of the course backup file
     *  
     *  @param int $coursewareid
     *  @param int $position
     *  @param string $pathname
     *  @return \stored_file
     *  @throws \moodle_exception
     */
    public function create_partial_file_from_pathname($coursewareid, $position, $pathname)
    {
        $filerecord = array(
            'contextid'    => $this->context->id,
            'component'    => self::COMPONENT,
            'filearea'     => self::FILEAREA,
            'itemid'       => $coursewareid,
            'filepath'     => '/parts/',
            'filename'     => sprintf('%010d', $position),
            'mimetype'     => 'application/vnd.moodle.backup.partial',
            'timecreated'  => time(),
            'timemodified' => time(),
            );
        return $this->storage->create_file_from_pathname($filerecord, $pathname);
    }

    /**
     *  Concatenates partial files into a course backup file
     *  
     *  @global object $CFG
     *  @global \moodle_database $DB
     *  @param int $coursewareid
     *  @return \stored_file
     *  @throws \moodle_exception
     */
    public function concat_partial_files($coursewareid)
    {
        global $CFG, $DB;

        // checks if the courseware record and its partial files exist
        $courseware = $DB->get_record('majhub_coursewares',
            array('id' => $coursewareid), '*', MUST_EXIST);
        $partfiles = $this->storage->get_directory_files(
            $this->context->id, self::COMPONENT, self::FILEAREA, $courseware->id, '/parts/',
            false, false);
        if (empty($partfiles))
            throw new \moodle_exception('nofile');

        // creates a temporary directory
        $tempdir = $CFG->dataroot . '/temp/majhub/' . $courseware->id;
        if (!\check_dir_exists($tempdir, true, true))
            throw new \moodle_exception('error_creating_temp_dir', 'error', $tempdir);
        $tempdirscope = new scoped(function () use ($tempdir) { \fulldelete($tempdir); });

        // concatenates partial files into a temporary file
        $filename = sprintf('%s-%s.mbz', \clean_filename($courseware->shortname), date('Ymd-His'));
        $concatfp = fopen("$tempdir/$filename", 'wb');
        if (!$concatfp)
            throw new \moodle_exception('error_creating_temp_file', 'error', "$tempdir/$filename");
        $concatfpscope = new scoped(function () use ($concatfp) { fclose($concatfp); });
        foreach ($partfiles as $file) {
            $position = intval($file->get_filename(), 10);
            $fp = $file->get_content_file_handle();
            fseek($concatfp, $position, SEEK_SET);
            $written = stream_copy_to_stream($fp, $concatfp);
            fclose($fp);
            if ($written != $file->get_filesize())
                throw new \moodle_exception('error_writing_temp_file', 'error', "$tempdir/$filename");
        }
        unset($concatfpscope);

        // creates a stored file from the concatenated file
        $filerecord = array(
            'contextid'    => $this->context->id,
            'component'    => self::COMPONENT,
            'filearea'     => self::FILEAREA,
            'itemid'       => $courseware->id,
            'filepath'     => '/',
            'filename'     => $filename,
            'mimetype'     => 'application/vnd.moodle.backup',
            'timecreated'  => time(),
            'timemodified' => time(),
            );
        return $this->storage->create_file_from_pathname($filerecord, "$tempdir/$filename");
    }
    
    //added Justin to bridge the MAJ Hub and the Plain Hub 20131014
    //But this is not a solution for the future. We will eventually
    // just swap out MAJ file logic for Plain Hub logic
    public function copy_to_storage($coursewareid, $fullpath, $filename){
    	 global $CFG, $DB;

        // checks if the courseware record exists
        $courseware = $DB->get_record('majhub_coursewares',
            array('id' => $coursewareid), '*', MUST_EXIST);
            
        // creates a stored file from the filepath
        $filerecord = array(
            'contextid'    => $this->context->id,
            'component'    => self::COMPONENT,
            'filearea'     => self::FILEAREA,
            'itemid'       => $courseware->id,
            'filepath'     => '/',
            'filename'     => $filename,
            'mimetype'     => 'application/vnd.moodle.backup',
            'timecreated'  => time(),
            'timemodified' => time(),
            );
        return $this->storage->create_file_from_pathname($filerecord, $fullpath);
    
    }
    
    //added Justin to bridge the MAJ Hub and the Plain Hub 20131014
    //But this is not a solution for the future. We will eventually
    // just swap out MAJ file logic for Plain Hub logic
    public function remove_from_storage($fileid, $timecreated, $hubcourseid){
    	 global $CFG, $DB;
    	 try{
			 //delete MAJ Hub file
			 $file = $this->storage->get_file_by_id($fileid);
			 $file->delete();
			 //Delete temp file stored in Standard Hub dir
			 $newpath  = \local_majhub_fetch_versioned_filepath($hubcourseid, $timecreated);
			 unlink($newpath);
			 return true;
    	 }catch(Exception $e){
    	 	error_log($e->getMessage());
    	 	return false;
    	 }
    	 
    }

    /**
     *  Deletes partial files
     *  
     *  @param int $coursewareid
     */
    public function delete_partial_files($coursewareid)
    {
        $partfiles = $this->storage->get_directory_files(
            $this->context->id, self::COMPONENT, self::FILEAREA, $coursewareid, '/parts/',
            false, false);
        foreach ($partfiles as $file)
            $file->delete();
    }

    /**
     *  Uninstallation
     *  
     *  Purges all the MAJ Hub files so that we can avoid collisions of itemids when the reinstallation
     */
    public static function uninstall()
    {
        \get_file_storage()->delete_area_files(\context_system::instance()->id, self::COMPONENT, self::FILEAREA);
    }
}
