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

define('MOD_FLUENCYBUILDER_FBQUESTION_NONE', 0);
define('MOD_FLUENCYBUILDER_FBQUESTION_TYPE_PICTUREPROMPT', 1);
define('MOD_FLUENCYBUILDER_FBQUESTION_TYPE_AUDIOPROMPT', 2);
define('MOD_FLUENCYBUILDER_FBQUESTION_TYPE_TEXTPROMPT', 4);
define('MOD_FLUENCYBUILDER_FBQUESTION_TYPE_INSTRUCTIONS', 6);
define('MOD_FLUENCYBUILDER_FBQUESTION_TEXTCHOICE', 'textchoice');
define('MOD_FLUENCYBUILDER_FBQUESTION_PICTURECHOICE', 'picturechoice');
define('MOD_FLUENCYBUILDER_FBQUESTION_TRANSLATE', 'translate');
define('MOD_FLUENCYBUILDER_FBQUESTION_AUDIOFNAME', 'itemaudiofname');
define('MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT', 'audioitem');
define('MOD_FLUENCYBUILDER_FBQUESTION_AUDIOANSWER', 'audioanswer');
define('MOD_FLUENCYBUILDER_FBQUESTION_AUDIOMODEL', 'audiomodel');
define('MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT_FILEAREA', 'audioitem');
define('MOD_FLUENCYBUILDER_FBQUESTION_AUDIOMODEL_FILEAREA', 'audiomodel');
define('MOD_FLUENCYBUILDER_FBQUESTION_AUDIOANSWER_FILEAREA', 'audioanswer');
define('MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT', 'pictureitem');
define('MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT_FILEAREA', 'pictureitem');
define('MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION', 'itemtext');
define('MOD_FLUENCYBUILDER_FBQUESTION_TEXTANSWER', 'customtext');
define('MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION_FILEAREA', 'itemarea');
define('MOD_FLUENCYBUILDER_FBQUESTION_TEXTANSWER_FILEAREA', 'answerarea');
define('MOD_FLUENCYBUILDER_FBQUESTION_TABLE','fluencybuilder_fbquestions');


//creates a "unique" slide pair key so that backups and restores won't stuff things
function mod_fluencybuilder_create_fbquestionkey(){
	global $CFG;
	$prefix = $CFG->wwwroot . '@';
	return uniqid($prefix, true); 
}

//kill duplicate fbquestionkeys, that might arise from a restore
function mod_fluencybuilder_kill_duplicate_fbquestionkeys(){
	global $DB;
	$sql ='SELECT MAX(id) as maxid , COUNT(id) as duplicatecount, ww.* ';
	$sql .= ' FROM {' . MOD_FLUENCYBUILDER_FBQUESTION_TABLE . '} ww ' ;
	$sql .= ' GROUP BY fbquestionkey HAVING duplicatecount > 1';

	$duplicatekeys = $DB->get_records_sql($sql);
	if($duplicatekeys){
		foreach($duplicatekeys as $dkey){
			$newkey = mod_fluencybuilder_create_fbquestionkey();
			$DB->set_field(MOD_FLUENCYBUILDER_FBQUESTION_TABLE,
				'fbquestionkey',
				$newkey,
				array('id'=>$dkey->maxid));
		}
	}
}

//create a sql 'IN' series of quoted ids 
function mod_fluencybuilder_create_sql_in($csvlist){
			$temparray = explode(',',$csvlist);
			$sql_in = '""';
			foreach($temparray as $onekey){	
				if($sql_in == '""'){
					$sql_in ='';
				}else{
					$sql_in .=',';
				} 
				$sql_in .= '"' . $onekey . '"' ;
			}
			return $sql_in;
}

//Fetch the total possible grade of a set of fbquestions
function mod_fluencybuilder_fetch_maxpossiblescore($fbquestionids){
	global $DB;
	$total = 1;
	return $total;
}

//Fetch the item score of a fbquestion depending on users answer and how long took.
function mod_fluencybuilder_fetch_itemscore($fbquestionid, $duration, $correct){
	global $CFG,$DB;
	$ret = 0;
	
	//if we were not even correct, just return 0.
	if($correct){
		$ret=1;
	}else{
		$ret=0;
	}
	return $ret;
}