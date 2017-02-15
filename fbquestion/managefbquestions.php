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
 * Action for adding/editing a fbquestion. 
 * replace i) MOD_fluencybuilder eg MOD_CST, then ii) fluencybuilder eg cst, then iii) fbquestion eg fbquestion
 *
 * @package mod_fluencybuilder
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../../config.php");
require_once($CFG->dirroot.'/mod/fluencybuilder/lib.php');
require_once($CFG->dirroot.'/mod/fluencybuilder/fbquestion/fbquestionforms.php');
require_once($CFG->dirroot.'/mod/fluencybuilder/fbquestion/fbquestionlocallib.php');

global $USER,$DB;

// first get the nfo passed in to set up the page
$itemid = optional_param('itemid',0 ,PARAM_INT);
$id     = required_param('id', PARAM_INT);         // Course Module ID
$type  = optional_param('type', MOD_FLUENCYBUILDER_FBQUESTION_NONE, PARAM_INT);
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
$PAGE->set_url('/mod/fluencybuilder/fbquestion/managefbquestions.php', array('itemid'=>$itemid, 'id'=>$id, 'type'=>$type));
$PAGE->set_title(format_string($fluencybuilder->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');

//are we in new or edit mode?
if ($itemid) {
    $item = $DB->get_record(MOD_FLUENCYBUILDER_FBQUESTION_TABLE, array('id'=>$itemid,'fluencybuilder' => $cm->instance), '*', MUST_EXIST);
	if(!$item){
		print_error('could not find item of id:' . $itemid);
	}
    $type = $item->type;
    $edit = true;
} else {
    $edit = false;
}

//we always head back to the fluencybuilder items page
$redirecturl = new moodle_url('/mod/fluencybuilder/fbquestion/fbquestions.php', array('id'=>$cm->id));

	//handle delete actions
    if($action == 'confirmdelete'){
    	$usecount = $DB->count_records(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,array('fbquestionid'=>$itemid));
    	if($usecount>0){
    		redirect($redirecturl,get_string('iteminuse','fluencybuilder'),10);
    	}

		$renderer = $PAGE->get_renderer('mod_fluencybuilder');
		$fbquestion_renderer = $PAGE->get_renderer('mod_fluencybuilder','fbquestion');
		echo $renderer->header($fluencybuilder, $cm, 'fbquestions', null, get_string('confirmitemdeletetitle', 'fluencybuilder'));
		echo $fbquestion_renderer->confirm(get_string("confirmitemdelete","fluencybuilder",$item->name), 
			new moodle_url('/mod/fluencybuilder/fbquestion/managefbquestions.php', array('action'=>'delete','id'=>$cm->id,'itemid'=>$itemid)), 
			$redirecturl);
		echo $renderer->footer();
		return;

	/////// Delete item NOW////////
    }elseif ($action == 'delete'){
    	require_sesskey();
		$success = mod_fluencybuilder_fbquestion_delete_item($fluencybuilder,$itemid,$context);
        redirect($redirecturl);
    }



//get filechooser and html editor options
$editoroptions = mod_fluencybuilder_fbquestion_fetch_editor_options($course, $context);
$filemanageroptions = mod_fluencybuilder_fbquestion_fetch_filemanager_options($course,1);


//get the mform for our item
switch($type){
	case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_PICTUREPROMPT:
		$mform = new fluencybuilder_add_item_form_picturechoice(null,
			array('editoroptions'=>$editoroptions, 
			'filemanageroptions'=>$filemanageroptions)
		);
		break;
	case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_AUDIOPROMPT:
		$mform = new fluencybuilder_add_item_form_audiochoice(null,
			array('editoroptions'=>$editoroptions, 
			'filemanageroptions'=>$filemanageroptions)
		);
		break;
	case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_TEXTPROMPT:
		$mform = new fluencybuilder_add_item_form_textchoice(null,
			array('editoroptions'=>$editoroptions, 
			'filemanageroptions'=>$filemanageroptions)
		);
		break;
	case MOD_FLUENCYBUILDER_FBQUESTION_NONE:
	default:
		print_error('No item type specifified');

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
		$theitem->visible = $data->visible;
		$theitem->tags = $data->tags;
		$theitem->timetarget = $data->timetarget;
		$theitem->order = $data->order;
		$theitem->difficulty = $data->difficulty;
		$theitem->type = $data->type;
		$theitem->name = $data->name;
		$theitem->modifiedby=$USER->id;
		$theitem->timemodified=time();
		
		//first insert a new item if we need to
		//that will give us a itemid, we need that for saving files
		if(!$edit){
			
			$theitem->{MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION} = '';
			$theitem->{MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION.'format'} = 0;
			$theitem->timecreated=time();			
			$theitem->createdby=$USER->id;


			//create a fbquestionkey
			$theitem->fbquestionkey = mod_fluencybuilder_create_fbquestionkey();
			
			//try to insert it
			if (!$theitem->id = $DB->insert_record(MOD_FLUENCYBUILDER_FBQUESTION_TABLE,$theitem)){
					error("Could not insert fluencybuilder item!");
					redirect($redirecturl);
			}
		}			
		
		//handle all the files
		//save the item text editor files (common to all types)
		$data = file_postupdate_standard_editor( $data, MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION, $editoroptions, $context,
								'mod_fluencybuilder', MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION_FILEAREA, $theitem->id);
		$theitem->{MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION} = $data->{MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION} ;
		$theitem->{MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION.'format'} = $data->{MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION.'format'} ;
		
		//save item files
		if(property_exists($data,MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT)){
			file_save_draft_area_files($data->{MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT}, $context->id, 'mod_fluencybuilder', MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT_FILEAREA,
				   $theitem->id, $filemanageroptions);
		}
			   
		//save item picture files
		if(property_exists($data,MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT)){
			file_save_draft_area_files($data->{MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT}, $context->id, 'mod_fluencybuilder', MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT_FILEAREA,
				   $theitem->id, $filemanageroptions);
		}
					
		//do things dependant on type
		switch($data->type){
			case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_TEXTPROMPT:
				
				// Save answertext/files data
				$data = file_postupdate_standard_editor( $data, MOD_FLUENCYBUILDER_FBQUESTION_TEXTANSWER, $editoroptions, $context,
                                        'mod_fluencybuilder', MOD_FLUENCYBUILDER_FBQUESTION_TEXTANSWER_FILEAREA, $theitem->id);
					$theitem->{MOD_FLUENCYBUILDER_FBQUESTION_TEXTANSWER} = $data->{MOD_FLUENCYBUILDER_FBQUESTION_TEXTANSWER} ;
					$theitem->{MOD_FLUENCYBUILDER_FBQUESTION_TEXTANSWER . 'format'} = $data->{MOD_FLUENCYBUILDER_FBQUESTION_TEXTANSWER . 'format'};	
				}
				break;
				
			case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_AUDIOPROMPT:
				// Save answer data
				file_save_draft_area_files($data->{MOD_FLUENCYBUILDER_FBQUESTION_AUDIOANSWER_FILEAREA}, $context->id, 'mod_fluencybuilder', MOD_FLUENCYBUILDER_FBQUESTION_AUDIOANSWER_FILEAREA,
					   $theitem->id, $filemanageroptions);
				}
			
				break;


			default:
				break;
		
		}

		//now update the db once we have saved files and stuff
		if (!$DB->update_record(MOD_FLUENCYBUILDER_FBQUESTION_TABLE,$theitem)){
				print_error("Could not update fluencybuilder item!");
				redirect($redirecturl);
		}

		
		//go back to edit quiz page
		redirect($redirecturl);
}


//if  we got here, there was no cancel, and no form data, so we are showing the form
//if edit mode load up the item into a data object
if ($edit) {
	$data = $item;		
	$data->itemid = $item->id;
	$data->timetarget = $item->timetarget;
}else{
	$data=new stdClass;
	$data->itemid = null;
	$data->visible = 1;
	$data->type=$type;
}
		
	//init our item, we move the id fields around a little 
    $data->id = $cm->id;
    $data = file_prepare_standard_editor($data, MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION, $editoroptions, $context, 'mod_fluencybuilder', 
		MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION_FILEAREA,  $data->itemid);	
		
	//prepare audio file areas
	$draftitemid = file_get_submitted_draft_itemid(MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT);
	file_prepare_draft_area($draftitemid, $context->id, 'mod_fluencybuilder', MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT_FILEAREA, $data->itemid,
						$filemanageroptions);
	$data->{MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT} = $draftitemid;
	
	//prepare picture file areas
	$draftitemid = file_get_submitted_draft_itemid(MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT);
	file_prepare_draft_area($draftitemid, $context->id, 'mod_fluencybuilder', MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT_FILEAREA, $data->itemid,
						$filemanageroptions);
	$data->{MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT} = $draftitemid;
	
	
	//Set up the item type specific parts of the form data
	switch($type){
		case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_TEXTPROMPT:			
			//prepare answer areas
			$data = file_prepare_standard_editor($data, MOD_FLUENCYBUILDER_FBQUESTION_TEXTANSWER, $editoroptions, $context, 'mod_fluencybuilder', MOD_FLUENCYBUILDER_FBQUESTION_TEXTANSWER_FILEAREA,  $data->itemid);

			
			break;
		case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_AUDIOPROMPT:
			//prepare answer area
			//audio editor
			$draftitemid = file_get_submitted_draft_itemid(MOD_FLUENCYBUILDER_FBQUESTION_AUDIOANSWER);
			file_prepare_draft_area($draftitemid, $context->id, 'mod_fluencybuilder', MOD_FLUENCYBUILDER_FBQUESTION_AUDIOANSWER_FILEAREA, $data->itemid,
								$filemanageroptions);
			$data->{MOD_FLUENCYBUILDER_FBQUESTION_AUDIOANSWER} = $draftitemid;
			
			break;
		case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_PICTUREPROMPT:
			
			
			break;
		default:
	}
    $mform->set_data($data);
    $PAGE->navbar->add(get_string('edit'), new moodle_url('/mod/fluencybuilder/fbquestion/fbquestions.php', array('id'=>$id)));
    $PAGE->navbar->add(get_string('editingitem', 'fluencybuilder', get_string($mform->typestring, 'fluencybuilder')));
	$renderer = $PAGE->get_renderer('mod_fluencybuilder');
	$mode='fbquestions';
	echo $renderer->header($fluencybuilder, $cm,$mode, null, get_string('edit', 'fluencybuilder'));
	$mform->display();
	echo $renderer->footer();