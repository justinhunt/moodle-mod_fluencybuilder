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
 * Reports for fluencybuilder
 *
 *
 * @package    mod_fluencybuilder
 * @copyright  fluencybuilder
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

header("Access-Control-Allow-Origin: *");
$id = optional_param('cmid', 0, PARAM_INT); // course_module ID, or
$eval= optional_param('eval', '', PARAM_TEXT); // data baby yeah
$attemptid= optional_param('attemptid', 0, PARAM_INT); // data baby yeah
$itemid= optional_param('itemid', 0, PARAM_INT); // data baby yeah


if ($id) {
    $cm         = get_coursemodule_from_id(MOD_FLUENCYBUILDER_MODNAME, $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance  = $DB->get_record(MOD_FLUENCYBUILDER_TABLE, array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

//can't require login for this page. nodejs app and moodle cant share cookies . hmmmmmmmmm
//require_sesskey();
require_login($course, false, $cm);


$modulecontext = context_module::instance($cm->id);
$PAGE->set_context($modulecontext);

$fluencytest = new \mod_fluencybuilder\fluencytest($cm);
$items = $fluencytest->fetch_items();



/// Set up the page header

//Get an admin settings 
$config = get_config(MOD_FLUENCYBUILDER_FRANKY);
//get a holder for success/fails of DB updates
$dbresults=array();

//add an attempts object
if($attemptid ==0) {
    $attemptdata = new stdClass;
    $attemptdata->course = $course->id;
    $attemptdata->userid = $USER->id;
    $attemptdata->fluencybuilderid = $cm->instance;
    $attemptdata->mode = 0;
    $attemptdata->sessionscore = 0;
    $attemptdata->timecreated = time();
    $attemptdata->timemodified = 0;
    $attemptid = $DB->insert_record(MOD_FLUENCYBUILDER_ATTEMPTTABLE, $attemptdata);
}

//prepare our update object for adding summmary from items to attempt
$update_data = new stdClass();
$update_data->id=$attemptid;
$update_data->sessionscore=0;

//add all our item to DB and build return data.
	$itemdata = new stdClass;
	$itemdata->course =$course->id;
	$itemdata->fluencybuilderid =$cm->instance;
	$itemdata->attemptid =$attemptid;
    $itemdata->itemid =$itemid;
    $itemdata->userid =$USER->id;
    $itemdata->correct=$eval=='ok';
	$itemdata->points = 0;
	$itemdata->timecreated =time();
	$itemdata->timemodified =0;

    //if user later updates their eval entry we just update too.
	$rec = $DB->get_record(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,
        array('course'=>$itemdata->course,
        'fluencybuilderid'=>$itemdata->fluencybuilderid,
            'attemptid'=>$itemdata->attemptid,
            'itemid'=>$itemdata->itemid,
            'userid'=>$itemdata->userid));

	if($rec) {
        $dbresult= $DB->update_record(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,array('id'=>$rec->id,'correct'=>$itemdata->correct,'timemodified'=>time()));
    }else {
        $dbresult= $DB->insert_record(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,$itemdata);
    }


//Lets turn session score into a percentage
//best to do it here, in case in future the number of items changes
$sessiontotalpoints = $DB->count_records(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,array('attemptid'=>$attemptid,'correct'=>1));
$rawpercent =  (100 * $sessiontotalpoints/count($items));
$update_data->sessionscore = round($rawpercent,0);

//update attempt table
$DB->update_record(MOD_FLUENCYBUILDER_ATTEMPTTABLE,$update_data);

//update the gradebook
fluencybuilder_update_grades($moduleinstance, $USER->id);


//return JSON to javascript
//slightly over kill but in future we might do more and we already had the json renderer.
$jsonrenderer = $PAGE->get_renderer(MOD_FLUENCYBUILDER_FRANKY,'json');
echo $jsonrenderer->render_result($attemptid,'noted');
