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
 * Provides the JSON return
 *
 * @package mod_fluencybuilder
 * @copyright  2014 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/fluencybuilder/lib.php');
require_once($CFG->dirroot.'/mod/fluencybuilder/fbquestion/fbquestionlib.php');
require_once($CFG->dirroot.'/mod/fluencybuilder/session/sessionlib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('fluencybuilder', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
//$fluencybuilder = new fluencybuilder($DB->get_record('fluencybuilder', array('id' => $cm->instance), '*', MUST_EXIST));
$fluencybuilder = $DB->get_record('fluencybuilder', array('id' => $cm->instance), '*', MUST_EXIST);

//get the items in the currently active session 
$items = mod_fluencybuilder_get_session_items($fluencybuilder->id);

header("Access-Control-Allow-Origin: *");

//can't require login for this page. nodejs app and moodle cant share cookies . hmmmmmmmmm
//require_sesskey();
//require_login($course, false, $cm);

$modulecontext = context_module::instance($cm->id);
$PAGE->set_context($modulecontext);
$jsonrenderer = $PAGE->get_renderer('mod_fluencybuilder','json');
echo $jsonrenderer->render_tasks_json('The CST Test',$modulecontext,$items,$fluencybuilder,$cm);