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
 * The main fluencybuilder configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_fluencybuilder
 * @copyright  fluencybuilder
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/fluencybuilder/lib.php');

/**
 * Module instance settings form
 */
class mod_fluencybuilder_mod_form extends moodleform_mod
{

    /**
     * Defines forms elements
     */
    public function definition()
    {
        global $CFG;

        $mform = $this->_form;

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('fluencybuildername', MOD_FLUENCYBUILDER_LANG), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'fluencybuildername', MOD_FLUENCYBUILDER_LANG);

        // Adding the standard "intro" and "introformat" fields
        if ($CFG->version < 2015051100) {
            $this->add_intro_editor();
        } else {
            $this->standard_intro_elements();
        }


        //attempts
        /*
        $attemptoptions = array(0 => get_string('unlimited', MOD_FLUENCYBUILDER_LANG),
                            1 => '1',2 => '2',3 => '3',4 => '4',5 => '5',);
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', MOD_FLUENCYBUILDER_LANG), $attemptoptions);
        */
        $mform->addElement('hidden', 'maxattempts');
        $mform->setType('maxattempts', PARAM_INT);
        $mform->setDefault('maxattempts', 0);


        //Question header
        $mform->addElement('textarea', 'questionheader', get_string('questionheader', MOD_FLUENCYBUILDER_LANG), array('wrap' => 'virtual', 'rows' => '5', 'cols' => '50'));
        $mform->setType('questionheader', PARAM_TEXT);
        $mform->setDefault('questionheader', get_config('mod_fluencybuilder', 'questionheader'));
        $mform->addRule('questionheader', null, 'required', null, 'client');


        //time target
        $duration_options = array('defaultunit'=>1,'optional'=>0);
        $mform->addElement('duration', 'timetarget', get_string('timetarget','fluencybuilder'),$duration_options);
        $mform->setDefault('timetarget',get_config('mod_fluencybuilder', 'timetarget'));


        //In fluency builder we will not be grading.
        /*
        // Grade.
        $this->standard_grading_coursemodule_elements();


        //grade options
        $gradeoptions = array(MOD_FLUENCYBUILDER_GRADEHIGHEST => get_string('gradehighest',MOD_FLUENCYBUILDER_LANG),
                            MOD_FLUENCYBUILDER_GRADELOWEST => get_string('gradelowest', MOD_FLUENCYBUILDER_LANG),
                            MOD_FLUENCYBUILDER_GRADELATEST => get_string('gradelatest', MOD_FLUENCYBUILDER_LANG),
                            MOD_FLUENCYBUILDER_GRADEAVERAGE => get_string('gradeaverage', MOD_FLUENCYBUILDER_LANG),
                            MOD_FLUENCYBUILDER_GRADENONE => get_string('gradenone', MOD_FLUENCYBUILDER_LANG));
        $mform->addElement('select', 'gradeoptions', get_string('gradeoptions', MOD_FLUENCYBUILDER_LANG), $gradeoptions);
        */

        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }

    /**
     * Add elements for setting the custom completion rules.
     *
     * @category completion
     * @return array List of added element names, or names of wrapping group elements.
     */
    public function add_completion_rules() {

        $mform =& $this->_form;
        $mform->addElement('checkbox', 'completeonfinish',
            get_string('completeonfinish', MOD_FLUENCYBUILDER_LANG));
        $mform->setDefault('completeonfinish',true);
        $mform->setType('completeonfinish', PARAM_BOOL);



        return array('completeonfinish');
    }

    /**
     * Called during validation to see whether some module-specific completion rules are selected.
     *
     * @param array $data Input data not yet validated.
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completeonfinish']);
    }
}