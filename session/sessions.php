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
$fluencybuilder = $DB->get_record(MOD_FLUENCYBUILDER_TABLE, array('id' => $cm->instance), '*', MUST_EXIST);
$items = $DB->get_records(MOD_FLUENCYBUILDER_SESSION_TABLE,array('fluencybuilder'=>$fluencybuilder->id));

//set mode for tabs later on
$mode='sessions';
//Set page url before require login, so we know where to return to, if bumped off to login
$PAGE->set_url('/mod/fluencybuilder/session/sessions.php', array('id'=>$cm->id,'mode'=>$mode));
require_login($course, false, $cm);

//get module context
$context = context_module::instance($cm->id);

//set up renderer and nav
$renderer = $PAGE->get_renderer('mod_fluencybuilder');
$session_renderer = $PAGE->get_renderer('mod_fluencybuilder','session');
$PAGE->navbar->add(get_string('sessions','fluencybuilder'));
echo $renderer->header($fluencybuilder, $cm, $mode, null, get_string('sessions', 'fluencybuilder'));


    // Need view permission to be here
    require_capability('mod/fluencybuilder:itemview', $context);
    
    //show edit links if can edit
    if(has_capability('mod/fluencybuilder:itemedit', $context)){
    	echo $session_renderer->add_edit_page_links($fluencybuilder);
    }


echo $session_renderer->show_items_list($items,$fluencybuilder,$cm);

echo $renderer->footer();
