<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * MAJHub Report Classes.
 *
 * @package    local_majhub
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/../classes/point.php';

/**
 * Classes for Reports in MAJHub
 *
 *	The important functions are:
*  process_raw_data : turns log data for one thig (question attempt) into one row
 * fetch_formatted_fields: uses data prepared in process_raw_data to make each field in fields full of formatted data
 * The allusers report is the simplest example 
 *
 * @package    local_majhub
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class local_majhub_base_report {

    protected $report="";
    protected $head=array();
	protected $rawdata=null;
    protected $fields = array();
	protected $dbcache=array();
	
	abstract function process_raw_data($formdata);
	abstract function fetch_formatted_heading();
	
	public function fetch_fields(){
		return $this->fields;
	}
	public function fetch_head(){
		$head=array();
		foreach($this->fields as $field){
			$head[]=get_string($field,'local_majhub');
		}
		return $head;
	}
	public function fetch_name(){
		return $this->report;
	}

	public function truncate($string, $maxlength){
		if(strlen($string)>$maxlength){
			$string=substr($string,0,$maxlength - 2) . '..';
		}
		return $string;
	}

	public function fetch_cache($table,$rowid){
		global $DB;
		if(!array_key_exists($table,$this->dbcache)){
			$this->dbcache[$table]=array();
		}
		if(!array_key_exists($rowid,$this->dbcache[$table])){
			$this->dbcache[$table][$rowid]=$DB->get_record($table,array('id'=>$rowid));
		}
		return $this->dbcache[$table][$rowid];
	}

	public function fetch_time_difference($starttimestamp,$endtimestamp){
			
			//return empty string if the timestamps are not both present.
			if(!$starttimestamp || !$endtimestamp){return '';}
			
			$s = $date = new DateTime();
			$s->setTimestamp($starttimestamp);
						
			$e =$date = new DateTime();
			$e->setTimestamp($endtimestamp);
						
			$diff = $e->diff($s);
			$ret = $diff->format("%H:%I:%S");
			return $ret;
	}
	
	public function fetch_time_difference_js($starttimestamp,$endtimestamp){
			
			//return empty string if the timestamps are not both present.
			if(!$starttimestamp || !$endtimestamp){return '';}
			
			$s = $date = new DateTime(); 
			$s->setTimestamp($starttimestamp / 1000);
						
			$e =$date = new DateTime();
			$e->setTimestamp($endtimestamp / 1000);
						
			$diff = $e->diff($s);
			$ret = $diff->format("%H:%I:%S");
			return $ret;
	}
	
	public function fetch_formatted_rows($withlinks=true){
		$records = $this->rawdata;
		$fields = $this->fields;
		$returndata = array();
		foreach($records as $record){
			$data = new stdClass();
			foreach($fields as $field){
				$data->{$field}=$this->fetch_formatted_field($field,$record,$withlinks);
			}//end of for each field
			$returndata[]=$data;
		}//end of for each record
		return $returndata;
	}
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'timecreated':
					$ret = date("Y-m-d H:i:s",$record->timecreated);
					break;
				case 'userid':
					$ret =fullname($DB->get_record('user',array('id'=>$record->userid)));
					break;
				default:
					if(property_exists($record,$field)){
						$ret=$record->{$field};
					}else{
						$ret = '';
					}
			}
			return $ret;
	}
	
}


/*
* local_majhub_allusers_report 
*
*
*/

class local_majhub_allusers_report extends  local_majhub_base_report {
	
	protected $report="allusers";
	protected $fields = array('fullname','username','email','lastaccess','points');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'lastaccess':
					if($record->user->lastaccess){
						$ret =  date("Y-m-d",$record->user->lastaccess);
					}else{
						$ret = get_string('neveraccessed', 'local_majhub');
					}
					break;
				case 'fullname':
					$ret = fullname($record->user);
					if($withlinks){
						$ret = $this->truncate($ret,40);
					}
					break;
				case 'username':
					$ret = $record->user->username;
					if($withlinks){
						$ret = $this->truncate($ret,40);
					}
					break;
				case 'email':
					$ret = $record->user->email;
					break;
				case 'points':
						$ret = $record->points;
					break;
				
				default:
					if(property_exists($record,$field)){
						$ret=$record->{$field};
					}else{
						$ret = '';
					}
			}
			return $ret;
	}
	
	public function fetch_formatted_heading(){
		return get_string('allusersreport','local_majhub');
	}
	
	public function process_raw_data($formdata){
		global $DB;

		//no data in the heading, so an empty class even is overkill ..
		$this->headingdata = new stdClass();
		//get all the users in the db
		$users = $DB->get_records('user',array('deleted'=>0));
		
		$alldata=array();
		if($users){
			foreach($users as $user){
				$adata = new stdClass();
				$adata->user=$user;
				$userpoints = majhub\point::from_userid($user->id);
				$adata->points=$userpoints->total;	
				$alldata[]= $adata;
			}
		}
		//At this point we have an event object per question from the log to process.
		//eg timetaken = $question->selectanswer - $question->endplayquestion;
		$this->rawdata= $alldata;
		return true;
	}

}

/*
* local_majhub_allusers_report 
*
*
*/

class local_majhub_points_report extends  local_majhub_base_report {
	
	protected $report="points";
	protected $fields = array('fullname','username','registration','upload','review','popularity','quality','user','download','total');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
			
				case 'fullname':
					$ret = fullname($record->u);
					if($withlinks){
						$ret = $this->truncate($ret,40);
					}
					break;
				case 'username':
					$ret = $record->u->username;
					if($withlinks){
						$ret = $this->truncate($ret,40);
					}
					break;
				case 'download':
					$ret = -1 * $record->download;
					break;
				default:
					if(property_exists($record,$field)){
						$ret=$record->{$field};
					}else{
						$ret = '';
					}
			}
			return $ret;
	}
	
	public function fetch_formatted_heading(){
		return get_string('pointsreport','local_majhub');
	}
	
	public function process_raw_data($formdata){
		global $DB;

		//no data in the heading, so an empty class even is overkill ..
		$this->headingdata = new stdClass();
		//get all the users in the db
		$users = $DB->get_records('user',array('deleted'=>0));
		
		$alldata=array();
		if($users){
			foreach($users as $user){
				$userpoints = majhub\point::from_userid($user->id);
				$adata = new stdClass();
				$adata->u=$user;
				$adata->registration=$userpoints->total;
				$adata->upload=$userpoints->upload;
				$adata->review=$userpoints->review;
				$adata->popularity=$userpoints->popularity;
				$adata->quality=$userpoints->quality;
				$adata->user=$userpoints->user;
				$adata->download=$userpoints->download;
				$adata->total=$userpoints->total;	
				$alldata[]= $adata;
			}
		}
		//At this point we have an event object per question from the log to process.
		//eg timetaken = $question->selectanswer - $question->endplayquestion;
		$this->rawdata= $alldata;
		return true;
	}

}