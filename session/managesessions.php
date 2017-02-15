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
 * Action for adding/editing a session. 
 * replace i) MOD_SESSIONMODULE eg MOD_CST, then ii) SESSIONMODULE eg cst, then iii) SESSION eg fbquestion, then iv) create a capability 
 *
 * @package mod_fluencybuilder
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../../config.php");
require_once($CFG->dirroot.'/mod/fluencybuilder/lib.php');
require_once($CFG->dirroot.'/mod/fluencybuilder/session/sessionforms.php');
require_once($CFG->dirroot.'/mod/fluencybuilder/session/sessionlocallib.php');
require_once($CFG->dirroot.'/mod/fluencybuilder/fbquestion/fbquestionlib.php');

global $USER,$DB;

// first get the nfo passed in to set up the page
$itemid = optional_param('itemid',0 ,PARAM_INT);
$id     = required_param('id', PARAM_INT);         // Course Module ID
$type  = optional_param('type', MOD_FLUENCYBUILDER_SESSION_TYPE_NORMAL, PARAM_INT);
$action = optional_param('action','edit',PARAM_TEXT);

// get the objects we need
$cm = get_coursemodule_from_id('fluencybuilder', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$fluencybuilder = $DB->get_record('fluencybuilder', array('id' => $cm->instance), '*', MUST_EXIST);

//make sure we are logged in and can see this form
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/fluencybuilder:itemedit', $context);

//set up the page object
$PAGE->set_url('/mod/fluencybuilder/fluencybuilder/session/managesessions.php', array('itemid'=>$itemid, 'id'=>$id, 'type'=>$type));
$PAGE->set_title(format_string($fluencybuilder->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');

//are we in new or edit mode?
if ($itemid) {
    $item = $DB->get_record(MOD_FLUENCYBUILDER_SESSION_TABLE, array('id'=>$itemid,'fluencybuilder' => $cm->instance), '*', MUST_EXIST);
	if(!$item){
		print_error('could not find item of id:' . $itemid);
	}
    $type = $item->type;
    $edit = true;
} else {
    $edit = false;
}


//get renderers
$renderer = $PAGE->get_renderer('mod_fluencybuilder');
$session_renderer = $PAGE->get_renderer('mod_fluencybuilder','session');

//we always head back to the fluencybuilder items page
$redirecturl = new moodle_url('/mod/fluencybuilder/session/sessions.php', array('id'=>$cm->id));

	//handle delete actions
    if($action == 'confirmdelete'){
		echo $renderer->header($fluencybuilder, $cm, 'session', null, get_string('confirmitemdeletetitle', 'fluencybuilder'));
		echo $session_renderer->confirm(get_string("confirmitemdelete","fluencybuilder",$item->name), 
			new moodle_url('/mod/fluencybuilder/session/managesessions.php', array('action'=>'delete','id'=>$cm->id,'itemid'=>$itemid)), 
			$redirecturl);
		echo $renderer->footer();
		return;

	/////// Delete item NOW////////
    }elseif ($action == 'delete'){
    	require_sesskey();
		$success = mod_fluencybuilder_session_delete_item($fluencybuilder,$itemid,$context);
        redirect($redirecturl);
	
    }

//get the mform for our item
$sortorder =  array();
$chosendata =  array();
$unchosendata =  array();

switch($type){
	case MOD_FLUENCYBUILDER_SESSION_TYPE_NORMAL:
		
		//In general we are doing more DB here than is ideal
		
		//first get existing selection if we are in edit mode
		//we have to get them into an SQL IN section, eack key quoted and between commas
		$fbquestion_SQL_IN  = '""';
		if($edit){
			$currentfbquestions = $item->fbquestionkeys;
			$fbquestion_SQL_IN = mod_fluencybuilder_create_sql_in($currentfbquestions);
		}
		//an empty string do not work so we use a dummy
		if($fbquestion_SQL_IN  == '""'){
			$fbquestion_SQL_IN  = '"@"';
		}

		//kill any duplicate keys that might have got in here
		mod_fluencybuilder_kill_duplicate_fbquestionkeys();

		//use the same sort ordering columns for all lists so we do not lose things
		$sqlsortcolumn = 'name';
		//use the same fields as output for all lists
		$sqlselectedfields = "fbquestionkey, CONCAT(mt.name, ' ', mst.name) as name ";		
		
		//need to get CHOSENDATA and UNCHOSEN data for our chooser component
		/*
		$chosendata= $DB->get_records_select_menu(MOD_FLUENCYBUILDER_FBQUESTION_TABLE,
				'fbquestionkey IN (' . $fbquestion_SQL_IN . ')',array(),$sqlsortcolumn,$sqlselectedfields);
		
		$unchosendata = $DB->get_records_select_menu(MOD_FLUENCYBUILDER_FBQUESTION_TABLE,
				'NOT fbquestionkey IN (' . $fbquestion_SQL_IN . ') ',array(),$sqlsortcolumn,$sqlselectedfields);
		*/		
		
		$chosendata= $DB->get_records_sql_menu('SELECT ' . $sqlselectedfields . ' FROM {' . MOD_FLUENCYBUILDER_FBQUESTION_TABLE . '} mst INNER JOIN {' . MOD_FLUENCYBUILDER_TABLE . '} mt ON mst.fluencybuilder = mt.id WHERE 
				 fbquestionkey IN (' . $fbquestion_SQL_IN . ') ORDER BY ' . $sqlsortcolumn ,array());		
		
		$unchosendata = $DB->get_records_sql_menu('SELECT ' . $sqlselectedfields . ' FROM {' . MOD_FLUENCYBUILDER_FBQUESTION_TABLE . '} mst INNER JOIN {' . MOD_FLUENCYBUILDER_TABLE . '} mt ON mst.fluencybuilder = mt.id WHERE 
				NOT fbquestionkey IN (' . $fbquestion_SQL_IN . ') ORDER BY ' . $sqlsortcolumn,array());		
				

		//sort order is kind of needed or else the items get all over the place
		//we fetch the sort order and pass it on to the chooser
		$sortorder_result = $DB->get_records_sql_menu('SELECT ' . $sqlselectedfields . ' FROM {' . MOD_FLUENCYBUILDER_FBQUESTION_TABLE . '} mst INNER JOIN {' . MOD_FLUENCYBUILDER_TABLE . '} mt ON mst.fluencybuilder = mt.id  ORDER BY ' . $sqlsortcolumn,array());
		$sortorder =array_keys($sortorder_result);
				
		$chooser = $session_renderer->fetch_chooser($chosendata,$unchosendata);
		$mform = new fluencybuilder_session_standard_form(null,array($chooser));
		break;

	default:
		print_error('No item type specified');

}

//if the cancel button was pressed, we are out of here
if ($mform->is_cancelled()) {
    redirect($redirecturl);
    exit;
}

//if we have data, then our job here is to save it and return to the quiz edit page
if ($data = $mform->get_data()) {
		require_sesskey();
		
		$theitem = new stdClass;
        $theitem->fluencybuilder = $fluencybuilder->id;
        $theitem->id = $data->itemid;
        $theitem->course = $data->courseid;
        $theitem->active = $data->active;
		$theitem->name= $data->name;
		$theitem->type= $data->type;
		$theitem->fbquestionkeys= $data->fbquestionkeys;
		$theitem->displayorder = $data->displayorder;
		$theitem->modifiedby=$USER->id;
		$theitem->timemodified=time();
		
		//first insert a new item if we need to
		//that will give us a itemid, we need that for saving files
		if(!$edit){

			$theitem->timecreated=time();				
			if (!$theitem->id = $DB->insert_record(MOD_FLUENCYBUILDER_SESSION_TABLE,$theitem)){
					error("Could not insert new session!");
					redirect($redirecturl);
			}
		}			

		
		//now update the db once we have saved files and stuff
		if (!$DB->update_record(MOD_FLUENCYBUILDER_SESSION_TABLE,$theitem)){
				print_error("Could not update session!");
				redirect($redirecturl);
		}
		
		//if this session is set to be the active one, disable the others.
		//TO DO use set_field_select and redice this to one sql call
		if($theitem->active){
			$DB->set_field(MOD_FLUENCYBUILDER_SESSION_TABLE,'active',0,array('fluencybuilder'=>$theitem->fluencybuilder));
			$DB->set_field(MOD_FLUENCYBUILDER_SESSION_TABLE,'active',1,array('id'=>$theitem->id));
		}

		
		//go back to edit quiz page
		redirect($redirecturl);
}

//if  we got here, there was no cancel, and no form data, so we are showing the form
//if edit mode load up the item into a data object
if ($edit) {
	$data = $item;		
	$data->itemid = $item->id;
	$data->courseid = $course->id;
	$data->id = $cm->id;
	$data->type=$item->type;
}else{
	$data=new stdClass;
	$data->itemid = null;
	$data->courseid = $course->id;
	$data->id=$cm->id;;
	$data->type=$type;
}
		

	//Set up the item type specific parts of the form data
	switch($type){
		case MOD_FLUENCYBUILDER_SESSION_TYPE_NORMAL:			
			//get our javascript all ready to go
			$jsmodule = array(
				'name'     => 'mod_fluencybuilder',
				'fullpath' => '/mod/fluencybuilder/module.js',
				'requires' => array('io','json','button','array-extras')
			);
			$opts =Array();
			$opts['chosen'] =MOD_FLUENCYBUILDER_SESSION_CHOSEN;
			$opts['unchosen'] =MOD_FLUENCYBUILDER_SESSION_UNCHOSEN;
			$opts['updatefield'] =MOD_FLUENCYBUILDER_SESSION_UPDATEFIELD;
			$opts['chosendata'] =$chosendata;
			$opts['unchosendata'] =$unchosendata;
			$opts['sortorder']=implode(',',$sortorder);
			$PAGE->requires->js_init_call('M.mod_fluencybuilder_session.init', array($opts),false,$jsmodule);
			break;
		default:
	}
    $mform->set_data($data);
    $PAGE->navbar->add(get_string('edit'), new moodle_url('/mod/fluencybuilder/session/sessions.php', array('id'=>$id)));
    $PAGE->navbar->add(get_string('editingitem', 'fluencybuilder', get_string($mform->typestring, 'fluencybuilder')));
	$renderer = $PAGE->get_renderer('mod_fluencybuilder');
	$mode='sessions';
	echo $renderer->header($fluencybuilder, $cm,$mode, null, get_string('edit', 'fluencybuilder'));
	$mform->display();
	echo $renderer->footer();