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
 * Defines all the backup steps that will be used by {@link backup_fluencybuilder_activity_task}
 *
 * @package     mod_fluencybuilder
 * @category    backup
 * @copyright   fluencybuilder
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/fluencybuilder/lib.php');
 require_once($CFG->dirroot . '/mod/fluencybuilder/fbquestion/fbquestionlib.php');

/**
 * Defines the complete webquest structure for backup, with file and id annotations
 *
 */
class backup_fluencybuilder_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the structure of the fluencybuilder element inside the webquest.xml file
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // are we including userinfo?
        $userinfo = $this->get_setting_value('userinfo');

        ////////////////////////////////////////////////////////////////////////
        // XML nodes declaration - non-user data
        ////////////////////////////////////////////////////////////////////////

        // root element describing fluencybuilder instance
        $oneactivity = new backup_nested_element(MOD_FLUENCYBUILDER_MODNAME, array('id'), array(
            'course','name','intro','introformat','someinstancesetting','grade','gradeoptions','maxattempts','mingrade',
			'mode','partnermode','sessionsize','timecreated','timemodified'
			));
			
		// fbquestion	
		$fbquestions = new backup_nested_element('fbquestions');
		$fbquestion = new backup_nested_element('fbquestion', array('id'),array(
			MOD_FLUENCYBUILDER_MODNAME, 'name', 'type','visible','itemtext', 'itemformat','itemaudiofname', 'answertext1', 'answertext1format','answertext2', 'answertext2format','answertext3', 'answertext3format','answertext4', 'answertext4format',
		  'correctanswer','shuffleanswers','answercount','answersinrow','answerwidth','createdby','modifiedby','timecreated','timemodified','difficulty','timebound1','timegrade1','timebound2','timegrade2','timebound3','timegrade3','timebound4','timegrade4','timebound5','timegrade5','fbquestionkey'));
		
		
		// sessions	
		$sessions = new backup_nested_element('sessions');
		$session = new backup_nested_element('session', array('id'),array('course', MOD_FLUENCYBUILDER_MODNAME , 'name', 'type','active','displayorder', 'fbquestionkeys','timecreated', 'timemodified')); 
		
		//attempts
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', array('id'),array(
			MOD_FLUENCYBUILDER_MODNAME ."id","course","userid","status","mode","sessionscore","timecreated","timemodified"
		));
		
		//items
        $items = new backup_nested_element('items');
        $item = new backup_nested_element('item', array('id'),array(
			MOD_FLUENCYBUILDER_MODNAME ."id","course","userid","attemptid","partnerid","fbquestionid","sessionid","correct","duration","timecreated","timemodified"
		));

		
		// Build the tree.
		$oneactivity->add_child($fbquestions);
		$fbquestions->add_child($fbquestion);
		$oneactivity->add_child($sessions);
		$sessions->add_child($session);
        $oneactivity->add_child($attempts);
        $attempts->add_child($attempt);
		$oneactivity->add_child($items);
		$items->add_child($item);
		


        // Define sources.
        $oneactivity->set_source_table(MOD_FLUENCYBUILDER_TABLE, array('id' => backup::VAR_ACTIVITYID));
		$fbquestion->set_source_table(MOD_FLUENCYBUILDER_FBQUESTION_TABLE,
                                        array(MOD_FLUENCYBUILDER_MODNAME => backup::VAR_PARENTID));

		$session->set_source_table(MOD_FLUENCYBUILDER_SESSION_TABLE,
                                        array(MOD_FLUENCYBUILDER_MODNAME => backup::VAR_PARENTID));

        //sources if including user info
        if ($userinfo) {
			$attempt->set_source_table(MOD_FLUENCYBUILDER_ATTEMPTTABLE,
											array(MOD_FLUENCYBUILDER_MODNAME . 'id' => backup::VAR_PARENTID));
			$item->set_source_table(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,
											array('attemptid' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $attempt->annotate_ids('user', 'userid');
        $item->annotate_ids('user', 'userid');


        // Define file annotations.
        // intro file area has 0 itemid.
        $oneactivity->annotate_files(MOD_FLUENCYBUILDER_FRANKY, 'intro', null);
		
		//other file areas use fluencybuilderid
		$fbquestion->annotate_files(MOD_FLUENCYBUILDER_FRANKY, MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION_FILEAREA, 'id');
		$fbquestion->annotate_files(MOD_FLUENCYBUILDER_FRANKY, MOD_FLUENCYBUILDER_FBQUESTION_PICTUREQUESTION_FILEAREA, 'id');
		$fbquestion->annotate_files(MOD_FLUENCYBUILDER_FRANKY, MOD_FLUENCYBUILDER_FBQUESTION_AUDIOQUESTION_FILEAREA, 'id');
		for($i=1;$i<=MOD_FLUENCYBUILDER_FBQUESTION_MAXANSWERS;$i++){
			$fbquestion->annotate_files(MOD_FLUENCYBUILDER_FRANKY, MOD_FLUENCYBUILDER_FBQUESTION_TEXTANSWER_FILEAREA.$i, 'id');
			$fbquestion->annotate_files(MOD_FLUENCYBUILDER_FRANKY, MOD_FLUENCYBUILDER_FBQUESTION_PICTUREANSWER_FILEAREA.$i, 'id');
			$fbquestion->annotate_files(MOD_FLUENCYBUILDER_FRANKY, MOD_FLUENCYBUILDER_FBQUESTION_AUDIOANSWER_FILEAREA.$i, 'id');
		}

        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($oneactivity);
		

    }
}