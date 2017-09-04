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
 * Provides the interface for overall managing of items
 *
 * @package mod_fluencybuilder
 * @copyright  2014 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once('../../../config.php');
require_once($CFG->dirroot.'/mod/fluencybuilder/lib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('fluencybuilder', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
//$fluencybuilder = new fluencybuilder($DB->get_record('fluencybuilder', array('id' => $cm->instance), '*', MUST_EXIST));
$fluencybuilder = $DB->get_record('fluencybuilder', array('id' => $cm->instance), '*', MUST_EXIST);
$items = $DB->get_records(MOD_FLUENCYBUILDER_FBQUESTION_TABLE,array('fluencybuilder'=>$fluencybuilder->id),'name ASC');

//mode is necessary for tabs
$mode='fbquestions';
//Set page url before require login, so post login will return here
$PAGE->set_url('/mod/fluencybuilder/fbquestion/fbquestions.php', array('id'=>$cm->id,'mode'=>$mode));

//require login for this page
require_login($course, false, $cm);
$context = context_module::instance($cm->id);

$renderer = $PAGE->get_renderer('mod_fluencybuilder');
$fbquestion_renderer = $PAGE->get_renderer('mod_fluencybuilder','fbquestion');
$PAGE->navbar->add(get_string('fbquestions', 'fluencybuilder'));
echo $renderer->header($fluencybuilder, $cm, $mode, null, get_string('fbquestions', 'fluencybuilder'));


    // We need view permission to be here
    require_capability('mod/fluencybuilder:itemview', $context);
    
    //if have edit permission, show edit buttons
    if(has_capability('mod/fluencybuilder:itemview', $context)){
    	echo $fbquestion_renderer ->add_edit_page_links($fluencybuilder);
    }

//if we have items, show em
if($items){
	echo $fbquestion_renderer->show_items_list($items,$fluencybuilder,$cm);
}
echo $renderer->footer();
