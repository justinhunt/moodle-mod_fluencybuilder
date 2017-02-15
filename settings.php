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
 * fluencybuilder module admin settings and defaults
 *
 * @package    mod
 * @subpackage fluencybuilder
 * @copyright  fluencybuilder
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/mod/fluencybuilder/lib.php');

if ($ADMIN->fulltree) {


	 $settings->add(new admin_setting_configtext('mod_fluencybuilder/listheight',
        get_string('listheight', 'fluencybuilder'), get_string('listheightdetails', 'fluencybuilder'), 12, PARAM_INT));
		
	//General Instructions at beginning of activity
	$defaultInstructions =	"Teacher instructions";
	
	 $settings->add(new admin_setting_configtextarea('mod_fluencybuilder/generalinstructions_teacher',
				get_string('generalinstructions_teacher', MOD_FLUENCYBUILDER_LANG),
				get_string('generalinstructions_teacher_desc', MOD_FLUENCYBUILDER_LANG),$defaultInstructions));
	
	$defaultInstructions =	"Student instructions";
	$settings->add(new admin_setting_configtextarea('mod_fluencybuilder/generalinstructions_student',
				get_string('generalinstructions_student', MOD_FLUENCYBUILDER_LANG),
				get_string('generalinstructions_student_desc', MOD_FLUENCYBUILDER_LANG),''));

}
