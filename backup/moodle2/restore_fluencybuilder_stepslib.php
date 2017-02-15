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
 * @package   mod_fluencybuilder
 * @copyright 2014 Justin Hunt poodllsupport@gmail.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 require_once($CFG->dirroot . '/mod/fluencybuilder/lib.php');
 require_once($CFG->dirroot . '/mod/fluencybuilder/fbquestion/fbquestionlib.php');

/**
 * Define all the restore steps that will be used by the restore_fluencybuilder_activity_task
 */

/**
 * Structure step to restore one fluencybuilder activity
 */
class restore_fluencybuilder_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();

        $userinfo = $this->get_setting_value('userinfo'); // are we including userinfo?

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - non-user data
        ////////////////////////////////////////////////////////////////////////

        // root element describing fluencybuilder instance
        $oneactivity = new restore_path_element(MOD_FLUENCYBUILDER_MODNAME, '/activity/fluencybuilder');
        $paths[] = $oneactivity;
		
		//fbquestions
		$fbquestions = new restore_path_element(MOD_FLUENCYBUILDER_FBQUESTION_TABLE,
                                            '/activity/fluencybuilder/fbquestions/fbquestion');
		$paths[] = $fbquestions;

		

        // End here if no-user data has been selected
        if (!$userinfo) {
            return $this->prepare_activity_structure($paths);
        }

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - user data
        ////////////////////////////////////////////////////////////////////////
		//attempts
		 $attempts= new restore_path_element(MOD_FLUENCYBUILDER_ATTEMPTTABLE,
                                            '/activity/fluencybuilder/attempts/attempt');
		$paths[] = $attempts;
		 
		 //items
		 $attemptitems= new restore_path_element(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,
                                            '/activity/fluencybuilder/attempts/attempt/items/item');
		$paths[] = $attemptitems;


        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_fluencybuilder($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timecreated = $this->apply_date_offset($data->timecreated);


        // insert the activity record
        $newitemid = $DB->insert_record(MOD_FLUENCYBUILDER_TABLE, $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

	protected function process_fluencybuilder_fbquestions($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->timecreated = $this->apply_date_offset($data->timecreated);

		
        $data->{MOD_FLUENCYBUILDER_MODNAME} = $this->get_new_parentid(MOD_FLUENCYBUILDER_MODNAME);
        $newfbquestionid = $DB->insert_record(MOD_FLUENCYBUILDER_FBQUESTION_TABLE, $data);
       $this->set_mapping(MOD_FLUENCYBUILDER_FBQUESTION_TABLE, $oldid, $newfbquestionid, true); // Mapping with files
  }
  
  protected function process_fluencybuilder_sessions($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->timecreated = $this->apply_date_offset($data->timecreated);

		
        $data->{MOD_FLUENCYBUILDER_MODNAME} = $this->get_new_parentid(MOD_FLUENCYBUILDER_MODNAME);
        $newsessionid = $DB->insert_record(MOD_FLUENCYBUILDER_SESSION_TABLE, $data);
       $this->set_mapping(MOD_FLUENCYBUILDER_SESSION_TABLE, $oldid, $newsessionid, true); // Mapping with files ..dont need this
  }
	
	//note these function names are set above in the restore path element
	//we used the table name there, thats all
	protected function process_fluencybuilder_attempt($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->timecreated = $this->apply_date_offset($data->timecreated);

		
        $data->{MOD_FLUENCYBUILDER_MODNAME . 'id'} = $this->get_new_parentid(MOD_FLUENCYBUILDER_MODNAME);
        $newattemptid = $DB->insert_record(MOD_FLUENCYBUILDER_ATTEMPTTABLE, $data);
       $this->set_mapping(MOD_FLUENCYBUILDER_ATTEMPTTABLE, $oldid, $newattemptid, false); // Mapping without files
    }
    
	//note these function names are set above in the restore path element
	//we used the table name there, thats all	
	protected function process_fluencybuilder_attemptitem($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->timecreated = $this->apply_date_offset($data->timecreated);

		$data->{'fluencybuilderid'} = $this->get_new_parentid(MOD_FLUENCYBUILDER_MODNAME);
        $data->{'attemptid'} = $this->get_new_parentid(MOD_FLUENCYBUILDER_ATTEMPTTABLE);
        $newitemid = $DB->insert_record(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE, $data);
       $this->set_mapping(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE, $oldid, $newitemid, false); // Mapping without files
    }
	
    protected function after_execute() {

        // Add module related files, no need to match by itemname (just internally handled context)
        $this->add_related_files(MOD_FLUENCYBUILDER_FRANKY, 'intro', null);

		//do question areas
		$this->add_related_files(MOD_FLUENCYBUILDER_FRANKY, MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION_FILEAREA, MOD_FLUENCYBUILDER_FBQUESTION_TABLE);
		$this->add_related_files(MOD_FLUENCYBUILDER_FRANKY, MOD_FLUENCYBUILDER_FBQUESTION_AUDIOQUESTION_FILEAREA, MOD_FLUENCYBUILDER_FBQUESTION_TABLE);
		$this->add_related_files(MOD_FLUENCYBUILDER_FRANKY, MOD_FLUENCYBUILDER_FBQUESTION_PICTUREQUESTION_FILEAREA, MOD_FLUENCYBUILDER_FBQUESTION_TABLE);

		//do answer areas 
		for($i=1;$i<=MOD_FLUENCYBUILDER_FBQUESTION_MAXANSWERS;$i++){
			$this->add_related_files(MOD_FLUENCYBUILDER_FRANKY, MOD_FLUENCYBUILDER_FBQUESTION_TEXTANSWER_FILEAREA.$i, MOD_FLUENCYBUILDER_FBQUESTION_TABLE);
			$this->add_related_files(MOD_FLUENCYBUILDER_FRANKY, MOD_FLUENCYBUILDER_FBQUESTION_AUDIOANSWER_FILEAREA.$i, MOD_FLUENCYBUILDER_FBQUESTION_TABLE);
			$this->add_related_files(MOD_FLUENCYBUILDER_FRANKY, MOD_FLUENCYBUILDER_FBQUESTION_PICTUREANSWER_FILEAREA.$i, MOD_FLUENCYBUILDER_FBQUESTION_TABLE);

		}
    }
}
