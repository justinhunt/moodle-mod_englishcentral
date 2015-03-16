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
 *  Report Classes.
 *
 * @package    englishcentral
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/user/profile/lib.php");

/**
 * Classes for Reports in MAJHub
 *
 *	The important functions are:
*  process_raw_data : turns log data for one thig (question attempt) into one row
 * fetch_formatted_fields: uses data prepared in process_raw_data to make each field in fields full of formatted data
 * The allusers report is the simplest example 
 *
 * @package    englishcentral
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mod_englishcentral_base_report {

    protected $report="";
    protected $head=array();
	protected $rawdata=null;
    protected $fields = array();
	protected $dbcache=array();
	protected $englishcentral=null;
	
	
	abstract function process_raw_data($formdata,$englishcentral);
	abstract function fetch_formatted_heading();
	
	public function fetch_fields(){
		return $this->fields;
	}
	public function fetch_head(){
		$head=array();
		foreach($this->fields as $field){
			$head[]=get_string($field,'englishcentral');
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

	public function fetch_formatted_time($seconds){
			
			//return empty string if the timestamps are not both present.
			if(!$seconds){return '';}
			
			return $this->fetch_time_difference($time, $time + $seconds);
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
					$u = $this->fetch_cache('user',$record->userid);
					$ret =fullname($u);
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
* mod_englishcentral_attempt_report 
*
*
*/
class mod_englishcentral_attemptdetails_report extends  mod_englishcentral_base_report {
	
	protected $report="attemptdetils";
	protected $fields = array('item','value');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	protected $englishcentral=null;
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'item':
						$ret = $record->item;
						break;
				
				case 'value':
						$ret = $record->value;
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
		$record = $this->headingdata;
		$ret='';
		if(!$record){return $ret;}
		$ec = $this->fetch_cache('englishcentral',$record->englishcentralid);
		
		
		$at = $this->fetch_cache('englishcentral_attempt',$record->attemptid);
		$u = $this->fetch_cache('user',$record->userid);
		$a = new stdClass();
		$a->name=$ec->name;
		$a->username = fullname($u);
		$a->date= date("Y-m-d H:i:s",$at->timecreated);
		return get_string('attemptdetails','englishcentral',$a);
		
	}
	
	public function process_raw_data($formdata,$englishcentral){
		global $DB;
		
		//heading data
		$this->englishcentral = $englishcentral;
		$this->headingdata = new stdClass();
		$this->headingdata->attemptid=$formdata->attemptid;
		$this->headingdata->userid=$formdata->userid;
		$this->headingdata->englishcentralid=$formdata->englishcentralid;
		
		$attemptdata = array();
		$adata = $DB->get_record('englishcentral_attempt',array('id'=>$formdata->attemptid));
		if($adata){
			$adata_array = (array)$adata;
			foreach($adata_array as $key=>$value){
				$item = new stdClass();
				$item->item = $key;
				$item->value = $value;
				$attemptdata[] = $item;			
			}
		}
		$this->rawdata= $attemptdata;
		return true;
	}
}

/*
* mod_englishcentral_allusers_report 
*
*
*/

class mod_englishcentral_allusers_report extends  mod_englishcentral_base_report {
	
	protected $report="allusers";
	protected $fields = array('date','username','activetime','lineswatched','linesrecorded','sessionscore','sessiongrade','compositescore');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	protected $englishcentral=null;
	
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'date':
					$ret =  date("Y-m-d",$record->timecreated);
					break;
				case 'activetime':
					$ret = gmdate("H:i:s",$record->activetime);
					break;

				case 'username':
						$theuser = $this->fetch_cache('user',$record->userid);
						$ret = fullname($theuser);
						
					break;
				
				case 'linesrecorded':
						$ret = $record->linesrecorded;
						if($withlinks){
							$phonemesurl = new moodle_url('/mod/englishcentral/reports.php', 
								array('n'=>$record->englishcentralid,
								'report'=>'phonemes',
								'userid'=>$record->userid,
								'attemptid'=>$record->id));
							$ret = html_writer::link($phonemesurl,$ret);
						}
						
					break;
				
				case 'sessiongrade':
						$ret = $record->sessiongrade;
					break;
					
				case 'sessionscore':
						$ret = $record->sessionscore;
					break;
				
				case 'compositescore':
						$completionrate = $record->recordingComplete ? 1 : 0;
						//this won't work in speaklitemode because linestotal is for watchable, not recordable
						if(!$this->englishcentral->speaklitemode && $record->linesrecorded > 0){
							$completionrate = $record->linesrecorded / $record->linestotal;
						}
						$ret = round($completionrate*$record->sessionscore,0) .'%';
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
		return get_string('allusers','englishcentral');
	}
	
	public function process_raw_data($formdata,$englishcentral){
		global $DB;

		//no data in the heading, so an empty class even is overkill ..
		$this->headingdata = new stdClass();
		$this->englishcentral = $englishcentral;
		
		//the current attempts
		$alldata = $DB->get_records('englishcentral_attempt',array('englishcentralid'=>$formdata->englishcentralid,'status'=>1));

		//At this point we have an event object per question from the log to process.
		//eg timetaken = $question->selectanswer - $question->endplayquestion;
		$this->rawdata= $alldata;
		return true;
	}

}

/*
* mod_englishcentral_allusers_report 
*
*
*/

class mod_englishcentral_allattempts_report extends  mod_englishcentral_base_report {
	
	protected $report="allattempts";
	protected $fields = array('date', 'username','status','activetime','points','details','phonemes','delete');
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	protected $englishcentral=null;
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'date':
					$ret =  date("Y-m-d H:i:s",$record->timecreated);
					break;

				case 'username':
					$theuser = $this->fetch_cache('user',$record->userid);
					if($withlinks){
						$theuser = $this->fetch_cache('user',$record->userid);
						$ret = fullname($theuser);
						if($withlinks){
							$detailsurl = new moodle_url('/mod/englishcentral/reports.php', 
								array('n'=>$record->englishcentralid,
								'report'=>'attemptdetails',
								'userid'=>$record->userid,
								'attemptid'=>$record->id));
							$ret = html_writer::link($detailsurl,$ret);
						}
					}else{
						$ret = fullname($theuser);
					}
					break;
				
				case 'status':
						$ret = $record->status ? 'current':'old';
					break;
					
				case 'details':
						if($withlinks){
							$detailsurl = new moodle_url('/mod/englishcentral/reports.php', 
								array('n'=>$record->englishcentralid,
								'report'=>'attemptdetails',
								'userid'=>$record->userid,
								'attemptid'=>$record->id));
							$ret = html_writer::link($detailsurl,get_string('viewreport', 'englishcentral'));
						}else{
							$ret="";
						}
					break;
				case 'phonemes':
					if($withlinks){
						$phonemesurl =  new moodle_url('/mod/englishcentral/reports.php', 
								array('n'=>$record->englishcentralid,
								'report'=>'phonemes',
								'userid'=>$record->userid,
								'attemptid'=>$record->id));
						$ret = html_writer::link($phonemesurl, get_string('phonemes', 'englishcentral'));
					}else{
						$ret="";
					}
					
					break;
				case 'delete':
					if($withlinks){
						$actionurl = '/mod/englishcentral/manageattempts.php';
						$deleteurl = new moodle_url($actionurl, array('id'=>$record->cmid,'attemptid'=>$record->id,'action'=>'confirmdelete'));
						$ret = html_writer::link($deleteurl, get_string('deleteattempt', 'englishcentral'));
					}else{
						$ret="";
					}
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
		return get_string('allattempts','englishcentral');
	}
	
	public function process_raw_data($formdata,$englishcentral){
		global $DB;

		//no data in the heading, so an empty class even is overkill ..
		$this->headingdata = new stdClass();
		$this->englishcentral = $englishcentral;
		
		//the current attempts
		$alldata = $DB->get_records('englishcentral_attempt',array('englishcentralid'=>$formdata->englishcentralid));
		foreach($alldata as $adata){
			$adata->cmid = $formdata->cmid;
		}

		//At this point we have an event object per question from the log to process.
		//eg timetaken = $question->selectanswer - $question->endplayquestion;
		$this->rawdata= $alldata;
		return true;
	}

}

/*
* mod_englishcentral_allusers_report 
*
*
*/

class mod_englishcentral_phonemes_report extends  mod_englishcentral_base_report {
	
	protected $report="phonemes";
	protected $fields = array('phoneme','badcount','goodcount','total');
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	protected $englishcentral=null;
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){


				case 'phoneme':
					$ret = $record->phoneme;
					break;
				
				case 'goodcount':
					$ret = $record->goodcount;
					break;
				
				case 'badcount':
					$ret = $record->badcount;
					break;
				
				case 'total':
					$ret = $record->badcount + $record->goodcount;
					break;;	
				
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
		$attempt = $this->fetch_cache('englishcentral_attempt',$this->headingdata->attemptid);
		$user = $this->fetch_cache('user',$attempt->userid);
		$englishcentral = $this->fetch_cache('englishcentral',$attempt->englishcentralid);
		$a = new stdClass();
		$a->englishcentralname = $englishcentral->name;
		$a->username = fullname($user);
		$a->status = $attempt->status;
		$a->attemptdate = date("Y-m-d H:i:s",$attempt->timecreated);
		return get_string('phonemesheader','englishcentral',$a);
	}
	
	public function process_raw_data($formdata,$englishcentral){
		global $DB;

		//The data to help display a meaningful heading
		$hdata = new stdClass();
		$hdata->attemptid = $formdata->attemptid;
		$this->headingdata = $hdata;
		$this->englishcentral = $englishcentral;
		
		
		//the current attempts
		//the current attempts
		$logs = $DB->get_records('englishcentral_phs',array('attemptid'=>$formdata->attemptid));

		//At this point we have an event object per question from the log to process.
		//eg timetaken = $question->selectanswer - $question->endplayquestion;
		$this->rawdata= $logs;
		return true;
	}

}