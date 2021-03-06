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


global $USER,$DB;

// first get the nfo passed in to set up the page
$itemid = optional_param('itemid',0 ,PARAM_INT);
$id     = required_param('id', PARAM_INT);         // Course Module ID
$type  = optional_param('type', \mod_fluencybuilder\fbquestion\constants::NONE, PARAM_INT);
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
    $item = $DB->get_record(\mod_fluencybuilder\fbquestion\constants::TABLE, array('id'=>$itemid,'fluencybuilder' => $cm->instance), '*', MUST_EXIST);
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
    	$usecount = $DB->count_records(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,array('itemid'=>$itemid));
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
		$success = \mod_fluencybuilder\fbquestion\helper::delete_item($fluencybuilder,$itemid,$context);
        redirect($redirecturl);
    }elseif($action=="moveup" || $action=="movedown"){
        \mod_fluencybuilder\fbquestion\helper::move_item($fluencybuilder,$itemid,$action);
        redirect($redirecturl);
    }



//get filechooser and html editor options
//get filechooser and html editor options
$editoroptions = \mod_fluencybuilder\fbquestion\helper::fetch_editor_options($course, $context);
$filemanageroptions = \mod_fluencybuilder\fbquestion\helper::fetch_filemanager_options($course,1);


//get the mform for our item
switch($type){
	case \mod_fluencybuilder\fbquestion\constants::TYPE_PICTUREPROMPT:
		$mform = new \mod_fluencybuilder\fbquestion\picturepromptform(null,
			array('editoroptions'=>$editoroptions, 
			'filemanageroptions'=>$filemanageroptions,
                'timetarget'=>$fluencybuilder->timetarget)
		);
		break;
	case \mod_fluencybuilder\fbquestion\constants::TYPE_AUDIOPROMPT:
		$mform = new \mod_fluencybuilder\fbquestion\audiopromptform(null,
			array('editoroptions'=>$editoroptions, 
			'filemanageroptions'=>$filemanageroptions,
                'timetarget'=>$fluencybuilder->timetarget)
		);
		break;

	case \mod_fluencybuilder\fbquestion\constants::TYPE_TEXTPROMPT:
		$mform = new \mod_fluencybuilder\fbquestion\textpromptform(null,
			array('editoroptions'=>$editoroptions, 
			'filemanageroptions'=>$filemanageroptions,
                'timetarget'=>$fluencybuilder->timetarget)
		);
		break;

	case \mod_fluencybuilder\fbquestion\constants::NONE:
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
		$theitem->timetarget = $data->timetarget;
		$theitem->itemorder = $data->itemorder;
		$theitem->type = $data->type;
		$theitem->name = $data->name;
		$theitem->modifiedby=$USER->id;
		$theitem->timemodified=time();
		
		//first insert a new item if we need to
		//that will give us a itemid, we need that for saving files
		if(!$edit){
			
			$theitem->{\mod_fluencybuilder\fbquestion\constants::TEXTQUESTION} = '';
			$theitem->{\mod_fluencybuilder\fbquestion\constants::TEXTQUESTION.'format'} = 0;
			$theitem->timecreated=time();			
			$theitem->createdby=$USER->id;

			//get itemorder
            $fluencytest = new \mod_fluencybuilder\fluencytest($cm);
            $currentitems = $fluencytest->fetch_items();
            if(count($currentitems)>0){
                $lastitem = array_pop($currentitems);
                $itemorder = $lastitem->itemorder +1;
            } else{
                $itemorder=1;
            }
            $theitem->itemorder=$itemorder;

			//create a fbquestionkey
			$theitem->fbquestionkey = \mod_fluencybuilder\fbquestion\helper::create_fbquestionkey();
			
			//try to insert it
			if (!$theitem->id = $DB->insert_record(\mod_fluencybuilder\fbquestion\constants::TABLE,$theitem)){
					error("Could not insert fluencybuilder item!");
					redirect($redirecturl);
			}
		}			
		
		//handle all the files
		//save the item text editor files (common to all types)
		$data = file_postupdate_standard_editor( $data, \mod_fluencybuilder\fbquestion\constants::TEXTQUESTION, $editoroptions, $context,
								'mod_fluencybuilder', \mod_fluencybuilder\fbquestion\constants::TEXTQUESTION_FILEAREA, $theitem->id);
		$theitem->{\mod_fluencybuilder\fbquestion\constants::TEXTQUESTION} = $data->{\mod_fluencybuilder\fbquestion\constants::TEXTQUESTION} ;
		$theitem->{\mod_fluencybuilder\fbquestion\constants::TEXTQUESTION.'format'} = $data->{\mod_fluencybuilder\fbquestion\constants::TEXTQUESTION.'format'} ;
		
		//save audio prompt files
		if(property_exists($data,\mod_fluencybuilder\fbquestion\constants::AUDIOPROMPT)){
			file_save_draft_area_files($data->{\mod_fluencybuilder\fbquestion\constants::AUDIOPROMPT}, $context->id, 'mod_fluencybuilder', \mod_fluencybuilder\fbquestion\constants::AUDIOPROMPT_FILEAREA,
				   $theitem->id, $filemanageroptions);
		}

        //save audio model files
        if(property_exists($data,\mod_fluencybuilder\fbquestion\constants::AUDIOMODEL)){
            file_save_draft_area_files($data->{\mod_fluencybuilder\fbquestion\constants::AUDIOMODEL}, $context->id, 'mod_fluencybuilder', \mod_fluencybuilder\fbquestion\constants::AUDIOMODEL_FILEAREA,
                $theitem->id, $filemanageroptions);
        }

        //save item picture files
		if(property_exists($data,\mod_fluencybuilder\fbquestion\constants::PICTUREPROMPT)){
			file_save_draft_area_files($data->{\mod_fluencybuilder\fbquestion\constants::PICTUREPROMPT}, $context->id, 'mod_fluencybuilder', \mod_fluencybuilder\fbquestion\constants::PICTUREPROMPT_FILEAREA,
				   $theitem->id, $filemanageroptions);
		}

		//now update the db once we have saved files and stuff
		if (!$DB->update_record(\mod_fluencybuilder\fbquestion\constants::TABLE,$theitem)){
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
    $data = file_prepare_standard_editor($data, \mod_fluencybuilder\fbquestion\constants::TEXTQUESTION, $editoroptions, $context, 'mod_fluencybuilder',
		\mod_fluencybuilder\fbquestion\constants::TEXTQUESTION_FILEAREA,  $data->itemid);
		
	//prepare audio file areas
	$draftitemid = file_get_submitted_draft_itemid(\mod_fluencybuilder\fbquestion\constants::AUDIOPROMPT);
	file_prepare_draft_area($draftitemid, $context->id, 'mod_fluencybuilder', \mod_fluencybuilder\fbquestion\constants::AUDIOPROMPT_FILEAREA, $data->itemid,
						$filemanageroptions);
	$data->{\mod_fluencybuilder\fbquestion\constants::AUDIOPROMPT} = $draftitemid;

$draftitemid = file_get_submitted_draft_itemid(\mod_fluencybuilder\fbquestion\constants::AUDIOPROMPT);
file_prepare_draft_area($draftitemid, $context->id, 'mod_fluencybuilder', \mod_fluencybuilder\fbquestion\constants::AUDIOMODEL_FILEAREA, $data->itemid,
    $filemanageroptions);
$data->{\mod_fluencybuilder\fbquestion\constants::AUDIOMODEL} = $draftitemid;
	
	//prepare picture file areas
	$draftitemid = file_get_submitted_draft_itemid(\mod_fluencybuilder\fbquestion\constants::PICTUREPROMPT);
	file_prepare_draft_area($draftitemid, $context->id, 'mod_fluencybuilder', \mod_fluencybuilder\fbquestion\constants::PICTUREPROMPT_FILEAREA, $data->itemid,
						$filemanageroptions);
	$data->{\mod_fluencybuilder\fbquestion\constants::PICTUREPROMPT} = $draftitemid;
	
	
	//Set up the item type specific parts of the form data
	switch($type){
		case \mod_fluencybuilder\fbquestion\constants::TYPE_TEXTPROMPT:
			//prepare answer areas
			$data = file_prepare_standard_editor($data, \mod_fluencybuilder\fbquestion\constants::TEXTANSWER, $editoroptions, $context, 'mod_fluencybuilder', \mod_fluencybuilder\fbquestion\constants::TEXTANSWER_FILEAREA,  $data->itemid);

			
			break;
		case \mod_fluencybuilder\fbquestion\constants::TYPE_AUDIOPROMPT:
			//prepare answer area
			//audio editor
			$draftitemid = file_get_submitted_draft_itemid(\mod_fluencybuilder\fbquestion\constants::AUDIOPROMPT);
			file_prepare_draft_area($draftitemid, $context->id, 'mod_fluencybuilder', \mod_fluencybuilder\fbquestion\constants::AUDIOPROMPT_FILEAREA, $data->itemid,
								$filemanageroptions);
			$data->{\mod_fluencybuilder\fbquestion\constants::AUDIOPROMPT} = $draftitemid;
			
			break;
		case \mod_fluencybuilder\fbquestion\constants::TYPE_PICTUREPROMPT:
			
			
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