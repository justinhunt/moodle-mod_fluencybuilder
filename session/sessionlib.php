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


defined('MOODLE_INTERNAL') || die();
define('MOD_FLUENCYBUILDER_SESSION_TYPE_NONE',0);
define('MOD_FLUENCYBUILDER_SESSION_TYPE_NORMAL',1);
define('MOD_FLUENCYBUILDER_SESSION_TABLE','fluencybuilder_sessions');
define('MOD_FLUENCYBUILDER_SESSION_ITEM_TABLE','fluencybuilder_sessionitem');
define('MOD_FLUENCYBUILDER_SESSION_SELECT','mod_fluencybuilder_session_select');
define('MOD_FLUENCYBUILDER_SESSION_CHOSEN','mod_fluencybuilder_session_chosen');
define('MOD_FLUENCYBUILDER_SESSION_UNCHOSEN','mod_fluencybuilder_session_unchosen');
define('MOD_FLUENCYBUILDER_SESSION_UPDATEFIELD','fbquestionkeys');
define('MOD_FLUENCYBUILDER_SESSION_LISTSIZE',10);


require_once($CFG->dirroot.'/mod/fluencybuilder/fbquestion/fbquestionlib.php');

//get session items
function mod_fluencybuilder_get_session_items($fluencybuilderid){
	global $DB;
	
	//kill all duplicate fbquestionkeys that might creep in during backup restore
	mod_fluencybuilder_kill_duplicate_fbquestionkeys();
	//run it twice ... just in case ...
	mod_fluencybuilder_kill_duplicate_fbquestionkeys();
	
	$usesession = $DB->get_record(MOD_FLUENCYBUILDER_SESSION_TABLE,
			array('fluencybuilder'=>$fluencybuilderid, 'active'=>1),'*', IGNORE_MULTIPLE); 
	if($usesession){
		$fbquestion_SQL_IN =mod_fluencybuilder_create_sql_in($usesession->fbquestionkeys);
		$items = $DB->get_records_select(MOD_FLUENCYBUILDER_FBQUESTION_TABLE, 
			'fbquestionkey IN (' . $fbquestion_SQL_IN   . ')' ,array(),'fluencybuilder, fbquestionkey ASC');
	}else{
		$items = $DB->get_records(MOD_FLUENCYBUILDER_FBQUESTION_TABLE,array('fluencybuilder'=>$fluencybuilderid),'name ASC');
	}
	return $items;
}
