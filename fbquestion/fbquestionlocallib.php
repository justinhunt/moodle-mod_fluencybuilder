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
 * Internal library of functions for module fluencybuilder
 *
 * All the fluencybuilder specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_fluencybuilder
 * @copyright  COPYRIGHTNOTICE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();



    function mod_fluencybuilder_fbquestion_move_item($fluencybuilder, $moveitemid, $direction)
    {
        global $DB;

        switch($direction){
            case 'moveup':
                $sort = 'itemorder ASC';
                break;
            case 'movedown':
                $sort = 'itemorder DESC';
                break;
            default:
                //inconceivable that we should ever arrive here.
                return;
        }

        if (!$items = $DB->get_records(MOD_FLUENCYBUILDER_FBQUESTION_TABLE, array('fluencybuilder' => $fluencybuilder->id),$sort)) {
            print_error("Could not fetch items for ordering");
            return;
        }

        $prioritem = null;
        foreach($items as $item){
            if($item->id == $moveitemid && $prioritem!=null){
                $currentitemorder =$item->itemorder;
                $item->itemorder=$prioritem->itemorder;
                $prioritem->itemorder=$currentitemorder;

                //Set the new sort order
                $DB->set_field(MOD_FLUENCYBUILDER_FBQUESTION_TABLE,'itemorder',$item->itemorder,array('id'=>$item->id));
                $DB->set_field(MOD_FLUENCYBUILDER_FBQUESTION_TABLE,'itemorder',$prioritem->itemorder,array('id'=>$prioritem->id));
                break;
            }//end of if
            $prioritem=$item;
        }//end of for each
    }//end of move item function

   function mod_fluencybuilder_fbquestion_delete_item($fluencybuilder, $itemid, $context) {
		global $DB;
		$ret = false;
		
        if (!$DB->delete_records(MOD_FLUENCYBUILDER_FBQUESTION_TABLE, array('id'=>$itemid))){
            print_error("Could not delete item");
			return $ret;
        }
		//remove files
		$fs= get_file_storage();
		
		$fileareas = array(MOD_FLUENCYBUILDER_FBQUESTION_TEXTPROMPT_FILEAREA,
		MOD_FLUENCYBUILDER_FBQUESTION_TEXTPROMPT_FILEAREA . '1',
		MOD_FLUENCYBUILDER_FBQUESTION_TEXTPROMPT_FILEAREA . '2',
		MOD_FLUENCYBUILDER_FBQUESTION_TEXTPROMPT_FILEAREA . '3',
		MOD_FLUENCYBUILDER_FBQUESTION_TEXTPROMPT_FILEAREA . '4',
		MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT_FILEAREA,
		MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT_FILEAREA . '1',
		MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT_FILEAREA . '2',
		MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT_FILEAREA . '3',
		MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT_FILEAREA . '4',
        MOD_FLUENCYBUILDER_FBQUESTION_AUDIOMODEL_FILEAREA,
        MOD_FLUENCYBUILDER_FBQUESTION_AUDIOMODEL_FILEAREA . '1',
        MOD_FLUENCYBUILDER_FBQUESTION_AUDIOMODEL_FILEAREA . '2',
        MOD_FLUENCYBUILDER_FBQUESTION_AUDIOMODEL_FILEAREA . '3',
        MOD_FLUENCYBUILDER_FBQUESTION_AUDIOMODEL_FILEAREA . '4',
		MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT_FILEAREA,
		MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT_FILEAREA . '1',
		MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT_FILEAREA . '2',
		MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT_FILEAREA . '3',
		MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT_FILEAREA . '4');
		foreach ($fileareas as $filearea){
			$fs->delete_area_files($context->id,'mod_fluencybuilder',$filearea,$itemid);
		}
		$ret = true;
		return $ret;
   } 
   
   
  
	function mod_fluencybuilder_fbquestion_fetch_editor_options($course, $modulecontext){
		$maxfiles=99;
		$maxbytes=$course->maxbytes;
		return  array('trusttext'=>true, 'subdirs'=>true, 'maxfiles'=>$maxfiles,
							  'maxbytes'=>$maxbytes, 'context'=>$modulecontext);
	}

	function mod_fluencybuilder_fbquestion_fetch_filemanager_options($course, $maxfiles=1){
		$maxbytes=$course->maxbytes;
		return array('subdirs'=>true, 'maxfiles'=>$maxfiles,'maxbytes'=>$maxbytes,'accepted_types' => array('audio','image'));
	}



