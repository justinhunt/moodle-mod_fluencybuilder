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
 * @package    mod_fluencybuilder
 * @copyright  fluencybuilder
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Classes for Reports 
 *
 *	The important functions are:
*  process_raw_data : turns log data for one thing (e.g question attempt) into one row
 * fetch_formatted_fields: uses data prepared in process_raw_data to make each field in fields full of formatted data
 * The allusers report is the simplest example 
 *
 * @package    mod_fluencybuilder
 * @copyright  fluencybuilder
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mod_fluencybuilder_base_report {

    protected $report="";
    protected $head=array();
	protected $rawdata=null;
    protected $fields = array();
	protected $dbcache=array();
	
	
	abstract function process_raw_data($formdata,$moduleinstance);
	abstract function fetch_formatted_heading();
	
	public function fetch_fields(){
		return $this->fields;
	}
	public function fetch_head(){
		$head=array();
		foreach($this->fields as $field){
			$head[]=get_string($field,MOD_FLUENCYBUILDER_LANG);
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
			$time = time();
			
			return $this->fetch_time_difference($time, $time + $seconds);
	}
	
	public function fetch_formatted_milliseconds($milliseconds){
			
			//return empty string if the timestamps are not both present.
			if(!$milliseconds){return '';}
			$time = time();
			
			return $this->fetch_time_difference($time, $time + ($milliseconds/1000));
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
* Basic Report
*
*
*/
class mod_fluencybuilder_basic_report extends  mod_fluencybuilder_base_report {
	
	protected $report="basic";
	protected $fields = array('id','name','timecreated');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'id':
						$ret = $record->id;
						break;
				
				case 'name':
						$ret = $record->name;
					break;
				
				case 'timecreated':
						$ret = date("Y-m-d H:i:s",$record->timecreated);
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
		//$ec = $this->fetch_cache(MOD_FLUENCYBUILDER_TABLE,$record->englishcentralid);
		return get_string('basicheading',MOD_FLUENCYBUILDER_LANG);
		
	}
	
	public function process_raw_data($formdata,$moduleinstance){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		
		$emptydata = array();
		$alldata = $DB->get_records(MOD_FLUENCYBUILDER_TABLE,array());
		if($alldata){
			$this->rawdata= $alldata;
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
}

/*
* All Attempts Report
*
*
*/
class mod_fluencybuilder_allattempts_report extends  mod_fluencybuilder_base_report {
	
	protected $report="allattempts";
	protected $fields = array('id','username','partnername','sessionscore','totaltime','timecreated', 'delete');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'id':
						$ret = $record->id;
						if($withlinks){
							$oneattempturl = new moodle_url('/mod/fluencybuilder/reports.php', 
									array('n'=>$record->fluencybuilderid,
									'report'=>'oneattempt',
									'itemid'=>$record->id));
								$ret = html_writer::link($oneattempturl,$ret);
						}
						break;
				
				case 'username':
						$theuser = $this->fetch_cache('user',$record->userid);
						$ret=fullname($theuser);
					break;
					
				case 'partnername':
						$theuser = $this->fetch_cache('user',$record->partnerid);
						$ret=fullname($theuser);
					break;
				case 'totaltime':
						$ret= $this->fetch_formatted_milliseconds($record->totaltime);
						break;
						
				case 'timecreated':
						$ret = date("Y-m-d H:i:s",$record->timecreated);
					break;
					

				case 'delete':
					if($withlinks){
						$actionurl = '/mod/fluencybuilder/manageattempts.php';
						$deleteurl = new moodle_url($actionurl, array('id'=>$record->cmid,'attemptid'=>$record->id,'action'=>'confirmdelete'));
						$ret = html_writer::link($deleteurl, get_string('deleteattempt', 'fluencybuilder'));
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
		$record = $this->headingdata;
		$ret='';
		if(!$record){return $ret;}
		//$ec = $this->fetch_cache(MOD_FLUENCYBUILDER_TABLE,$record->englishcentralid);
		return get_string('allattemptsheading',MOD_FLUENCYBUILDER_LANG);
		
	}
	
	public function process_raw_data($formdata,$moduleinstance){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		
		$emptydata = array();
		$alldata = $DB->get_records(MOD_FLUENCYBUILDER_ATTEMPTTABLE,array('course'=>$moduleinstance->course,'fluencybuilderid'=>$moduleinstance->id));
		
		
		foreach($alldata as $adata){
			$adata->cmid = $formdata->cmid;
		}
		
		if($alldata){
			$this->rawdata= $alldata;
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
}


/*
* All Attempts Report
*
*
*/
class mod_fluencybuilder_latestattemptsummary_report extends mod_fluencybuilder_base_report {
	
	protected $report="latestattemptsummary";
	protected $fields = array();//this is set in process raw data	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_head(){
		$head=array();
		foreach($this->fields as $field){
			if(strpos($field,'item_correct_')===0){
				$itemid = str_replace('item_correct_','',$field);
				$fbquestion =$this->fetch_cache('fluencybuilder_fbquestions',$itemid);
				if($fbquestion){
					$head[]=$fbquestion->name . ':correct' ;
				}else{
					$head[]='item:correct';
				}	
			}elseif(strpos($field,'item_duration_')===0){
				$itemid = str_replace('item_duration_','',$field);
				$fbquestion =$this->fetch_cache('fluencybuilder_fbquestions',$itemid);
				if($fbquestion){
					$head[]=$fbquestion->name . ':time' ;
				}else{
					$head[]='item:duration';
				}
			}else{
				$head[]=get_string($field,MOD_FLUENCYBUILDER_LANG);
			}
		}
		return $head;
	}
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){

				case 'username':
						$theuser = $this->fetch_cache('user',$record->username);
						$ret=fullname($theuser);
					break;
					
				case 'fluencybuilder':
						$thefluencybuilder = $this->fetch_cache('fluencybuilder',$record->fluencybuilder);
						$ret=$thefluencybuilder->name;
					break;
			
				default:
					//put logic here if need to format item correct or time
					if(strpos($field,'item_correct_')===0){
						//do something
					}elseif(strpos($field,'item_duration_')===0){
						//do something
					}
				
					if(property_exists($record,$field)){
						$ret=$record->{$field};
					}else{
						$ret = '';
					}
			}
			return $ret;
	}
	
	public function fetch_formatted_heading(){
		return get_string('latestattemptsummary',MOD_FLUENCYBUILDER_LANG,$this->headingdata->name );
	}
	
	public function process_raw_data($formdata,$moduleinstance){
		global $DB;
		
		//heading data for report header, add moodle cst name
		$this->headingdata = new stdClass();
		$this->headingdata = $this->fetch_cache('fluencybuilder',$moduleinstance->id);
		
		$emptydata = array();
		
		$itemarray= $DB->get_fieldset_select(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,
		'fbquestionid', 'fluencybuilderid = ?',array($moduleinstance->id));
		$items = array_unique($itemarray);
		
		//print_r($items);
				
		$sql ='SELECT *, MAX(attemptid) as maxattemptid FROM {' . MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE . '} ';
		$sql .= 'WHERE fluencybuilderid =? AND fbquestionid IN ('. implode(',',$items) .') GROUP BY userid,fbquestionid';
		
		//echo $sql;
		//die;
		
		$itemsbyuser = $DB->get_records_sql($sql,array($moduleinstance->id));

		//update the fields since each run of the report may have diff fields in it
		$this->fields = array('username');	
		foreach($items as $item){
			$this->fields[]='item_correct_' . $item;
			$this->fields[]='item_duration_' . $item;
		}
		
		//sometimes we get a userid of 0 ... this is odd
		//how does that happen. Anyway default is -1 which means the first
		//pass of data processing will detect a new user data set
		$currentuserid=-1;
		
		$rawdatarow = false;
		foreach($itemsbyuser as $useritem){
			//data is a series of rows each of a diff fbquestion grouped by user
			//so we group data till the user changes, then we stash it
			if($useritem->userid!=$currentuserid){
				if($rawdatarow){
					$this->rawdata[]= $rawdatarow;
				}
				$currentuserid = $useritem->userid;
				$rawdatarow= new stdClass;
				$rawdatarow->username=$useritem->userid;
				$rawdatarow->fluencybuilder=$moduleinstance->id;
				foreach($items as $item){
					$rawdatarow->{'item_correct_' . $item}='-';
					$rawdatarow->{'item_duration_' . $item}='-';
				}
			}
			//stash the slide pair data
			$rawdatarow->{'item_correct_' . $useritem->fbquestionid}=$useritem->correct;
			$rawdatarow->{'item_duration_' . $useritem->fbquestionid}=$useritem->duration;
		}
		if($rawdatarow){
			$this->rawdata[]= $rawdatarow;
		}
		
		if(!$rawdatarow){
			$this->rawdata= $emptydata;
		}
		return true;
	}
}







/*
* All Attempts Report
*
*
*/
class mod_fluencybuilder_oneattempt_report extends  mod_fluencybuilder_base_report {
	
	protected $report="oneattempt";
	protected $fields = array('id','fbquestionname','answer','correct','points','totaltime','timecreated');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'id':
						$ret = $record->id;
						if($withlinks && false){
							$oneattempturl = new moodle_url('/mod/fluencybuilder/reports.php', 
									array('n'=>$record->fluencybuilderid,
									'report'=>'oneattempt',
									'itemid'=>$record->id));
								$ret = html_writer::link($oneattempturl,$ret);
						}
						break;
				
				case 'fbquestionname':
						$thefbquestion = $this->fetch_cache(MOD_FLUENCYBUILDER_FBQUESTION_TABLE,$record->fbquestionid);
						$ret=$thefbquestion->name;
					break;
					
				case 'correct':
						$theuser = $this->fetch_cache('user',$record->partnerid);
						$ret=$record->correct ? get_string('yes') : get_string('no');
					break;
				
				case 'answer':
						$ret=$record->answerid;
					break;
				
				case 'totaltime':
						$ret= $this->fetch_formatted_milliseconds($record->duration);
						break;
						
				case 'timecreated':
						$ret = date("Y-m-d H:i:s",$record->timecreated);
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
		//$ec = $this->fetch_cache(MOD_FLUENCYBUILDER_TABLE,$record->englishcentralid);
		return get_string('oneattemptheading',MOD_FLUENCYBUILDER_LANG);
		
	}
	
	public function process_raw_data($formdata,$moduleinstance){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		
		$emptydata = array();
		$alldata = $DB->get_records(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,array('attemptid'=>$formdata->attemptid,'course'=>$moduleinstance->course,'fluencybuilderid'=>$moduleinstance->id));
		if($alldata){
			$this->rawdata= $alldata;
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
}

/*
* All Attempts Report
*
*
*/
class mod_fluencybuilder_allfbquestions_report extends  mod_fluencybuilder_base_report {
	
	protected $report="allfbquestions";
	protected $fields = array('id','fbquestionname','count','avgcorrect','avgtotaltime');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'id':
						$ret = $record->fbquestionid;
						if($withlinks){
							$onefbquestionurl = new moodle_url('/mod/fluencybuilder/reports.php', 
									array('n'=>$record->fluencybuilderid,
									'report'=>'onefbquestion',
									'itemid'=>$record->fbquestionid));
								$ret = html_writer::link($onefbquestionurl,$ret);
						}
						break;
						break;
				
				case 'fbquestionname':
						$thefbquestion = $this->fetch_cache(MOD_FLUENCYBUILDER_FBQUESTION_TABLE,$record->fbquestionid);
						$ret=$thefbquestion->name;
					break;
					
				case 'count':
						$ret=$record->cntfbquestionid;
					break;
				
				case 'avgcorrect':
						$ret= round($record->avgcorrect,2);
						break;				
					
				case 'avgtotaltime':
						$ret= $this->fetch_formatted_milliseconds(round($record->avgtotaltime));
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
		//$ec = $this->fetch_cache(MOD_FLUENCYBUILDER_TABLE,$record->englishcentralid);
		return get_string('allfbquestionsheading',MOD_FLUENCYBUILDER_LANG);
		
	}
	
	public function process_raw_data($formdata,$moduleinstance){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		
		$emptydata = array();
		$alldata= $DB->get_records_sql('SELECT fbquestionid,fluencybuilderid,COUNT(fbquestionid) AS cntfbquestionid, AVG(correct) AS avgcorrect,AVG(duration) AS avgtotaltime FROM {'.	MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE.'} WHERE fluencybuilderid=:fluencybuilderid GROUP BY fbquestionid',array('fluencybuilderid'=>$moduleinstance->id));

		if($alldata){
			$this->rawdata= $alldata;
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
}

/*
* All Attempts Report
*
*
*/
class mod_fluencybuilder_onefbquestion_report extends  mod_fluencybuilder_base_report {
	
	protected $report="onefbquestion";
	protected $fields = array('id','username','answer','correct','totaltime','timecreated');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'id':
						$ret = $record->id;
						break;
				
				case 'username':
						$user = $this->fetch_cache('user',$record->userid);
						$ret=fullname($user);
					break;
		
					
				case 'correct':
						$theuser = $this->fetch_cache('user',$record->partnerid);
						$ret=$record->correct ? get_string('yes') : get_string('no');
					break;
				
				case 'answer':
						$ret=$record->answerid;
					break;
				
				case 'totaltime':
						$ret= $this->fetch_formatted_milliseconds($record->duration);
						break;
						
				case 'timecreated':
						$ret = date("Y-m-d H:i:s",$record->timecreated);
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
		//$ec = $this->fetch_cache(MOD_FLUENCYBUILDER_TABLE,$record->englishcentralid);
		return get_string('onefbquestionheading',MOD_FLUENCYBUILDER_LANG);
		
	}
	
	public function process_raw_data($formdata,$moduleinstance){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		
		$emptydata = array();
		$alldata = $DB->get_records(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,array('fbquestionid'=>$formdata->fbquestionitemid,'course'=>$moduleinstance->course,'fluencybuilderid'=>$moduleinstance->id));
		if($alldata){
			$this->rawdata= $alldata;
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
}

