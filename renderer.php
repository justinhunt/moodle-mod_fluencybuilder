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


defined('MOODLE_INTERNAL') || die();
require_once('fbquestion/fbquestionrenderer.php');


/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package mod_fluencybuilder
 * @copyright fluencybuilder
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_fluencybuilder_renderer extends plugin_renderer_base {
		  /**
     * Returns the header for the module
     *
     * @param mod $instance
     * @param string $currenttab current tab that is shown.
     * @param int    $item id of the anything that needs to be displayed.
     * @param string $extrapagetitle String to append to the page title.
     * @return string
     */
    public function header($moduleinstance, $cm, $currenttab = '', $itemid = null, $extrapagetitle = null) {
        global $CFG;

        $activityname = format_string($moduleinstance->name, true, $moduleinstance->course);
        if (empty($extrapagetitle)) {
            $title = $this->page->course->shortname.": ".$activityname;
        } else {
            $title = $this->page->course->shortname.": ".$activityname.": ".$extrapagetitle;
        }

        // Build the buttons
        $context = context_module::instance($cm->id);

    /// Header setup
        $this->page->set_title($title);
        $this->page->set_heading($this->page->course->fullname);
        $output = $this->output->header();

        if (has_capability('mod/fluencybuilder:manage', $context)) {
         //   $output .= $this->output->heading_with_help($activityname, 'overview', MOD_FLUENCYBUILDER_LANG);

            if (!empty($currenttab)) {
                ob_start();
                include($CFG->dirroot.'/mod/fluencybuilder/tabs.php');
                $output .= ob_get_contents();
                ob_end_clean();
            }
        } else {
            $output .= $this->output->heading($activityname);
        }
	

        return $output;
    }
	

	public function show_items($cm,$fluencybuilder){


        $ret='';
        $fluencytest = new \mod_fluencybuilder\fluencytest($cm);
        $items = $fluencytest->fetch_items();
        $itemcount=count($items);
        $currentitem=0;
        foreach($items as $item) {
            $currentitem++;
            $showorhide= $currentitem==1 ? '' : 'hide';

            //recorder
            $resourceurl = $fluencytest->fetch_media_url(MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT_FILEAREA, $item);
            $modelurl = $fluencytest->fetch_media_url(MOD_FLUENCYBUILDER_FBQUESTION_AUDIOMODEL_FILEAREA, $item);
            $recorder = $fluencytest->prepare_tool($resourceurl, $modelurl, $item);
            $itemprogress =  \html_writer::tag('h3',$currentitem . '/' . $itemcount, array('class' => MOD_FLUENCYBUILDER_CLASS  . '_itemprogress'));
            $itemtext =  \html_writer::tag('div',$fluencybuilder->questionheader, array('class' => MOD_FLUENCYBUILDER_CLASS  . '_itemtext'));

            //post record dialog
            $ret.=  \html_writer::tag('div',$itemprogress . $itemtext . $recorder, array('id' => 'mod_fluencybuilder_dplaceholder_' . $currentitem, 'class' => MOD_FLUENCYBUILDER_CLASS  . '_itemholder ' . $showorhide));
            $opts=array('itemid' => $item->id, 'currentitem'=>$currentitem,'itemcount'=>$itemcount,'cmid'=>$cm->id);
            $this->page->requires->js_call_amd("mod_fluencybuilder/postrecorddialog", 'init', array($opts));
        }

        //cancel button
        $cancelid= \html_writer::random_id(MOD_FLUENCYBUILDER_CLASS . '_cancelholder_') ;
        $ret.=  \html_writer::tag('div','', array('id' => $cancelid, 'class' => MOD_FLUENCYBUILDER_CLASS  . '_cancelholder'));
        $opts=array('holderid' => $cancelid,'cmid'=>$cm->id);
        $this->page->requires->js_call_amd("mod_fluencybuilder/canceldialog", 'init', array($opts));

        //strings for JS
        $this->page->requires->strings_for_js(array(
            'cancelui_cancelactivity',
            'cancelui_reallycancel',
            'cancelui_iwantquit',
            'cancelui_inoquit',
            'recui_howwasit',
            'recui_next'
        ),
            'mod_fluencybuilder');

        return $ret;
    }

	/**
     * Return HTML to display limited header
     */
      public function notabsheader(){
      	return $this->output->header();
      }


	public function fetch_newsessionlink($cm, $fluencybuilder) {
		global $CFG,$USER;
		//$activityid = $cm->id;
		//$sesskey = $USER->sesskey;
		//$userid = $USER->id;

		$urlparams = array('n'=>$fluencybuilder->id,);

        $link = new moodle_url($CFG->wwwroot . '/mod/fluencybuilder/activity.php',$urlparams);
        $ret =  html_writer::link($link, get_string('gotoactivity',MOD_FLUENCYBUILDER_LANG));
        return $ret;

    }


    /**
     *
     */
    public function show_something($showtext) {
		$ret = $this->output->box_start();
		$ret .= $this->output->heading($showtext, 4, 'main');
		$ret .= $this->output->box_end();
        return $ret;
    }

	 /**
     *
     */
	public function show_intro($fluencybuilder,$cm){
		$ret = "";
		if (trim(strip_tags($fluencybuilder->intro))) {
			echo $this->output->box_start('mod_introbox');
			echo format_module_intro('fluencybuilder', $fluencybuilder, $cm->id);
			echo $this->output->box_end();
		}
	}
  
}//end of class

class mod_fluencybuilder_report_renderer extends plugin_renderer_base {


	public function render_reportmenu($moduleinstance,$cm, $reports) {
		$reportbuttons = array();
		foreach($reports as $report){
			$button = new single_button(
				new moodle_url(MOD_FLUENCYBUILDER_URL . '/reports.php',array('report'=>$report,'id'=>$cm->id,'n'=>$moduleinstance->id)), 
				get_string($report .'report',MOD_FLUENCYBUILDER_LANG), 'get');
			$reportbuttons[] = $this->render($button);
		}

		$ret = html_writer::div(implode('<br />',$reportbuttons) ,MOD_FLUENCYBUILDER_CLASS  . '_listbuttons');

		return $ret;
	}

	public function render_delete_allattempts($cm){
		$deleteallbutton = new single_button(
				new moodle_url(MOD_FLUENCYBUILDER_URL . '/manageattempts.php',array('id'=>$cm->id,'action'=>'confirmdeleteall')), 
				get_string('deleteallattempts',MOD_FLUENCYBUILDER_LANG), 'get');
		$ret =  html_writer::div( $this->render($deleteallbutton) ,MOD_FLUENCYBUILDER_CLASS  . '_actionbuttons');
		return $ret;
	}

	public function render_reporttitle_html($course,$username) {
		$ret = $this->output->heading(format_string($course->fullname),2);
		$ret .= $this->output->heading(get_string('reporttitle',MOD_FLUENCYBUILDER_LANG,$username),3);
		return $ret;
	}

	public function render_empty_section_html($sectiontitle) {
		global $CFG;
		return $this->output->heading(get_string('nodataavailable',MOD_FLUENCYBUILDER_LANG),3);
	}
	
	public function render_exportbuttons_html($cm,$formdata,$showreport){
		//convert formdata to array
		$formdata = (array) $formdata;
		$formdata['id']=$cm->id;
		$formdata['report']=$showreport;
		/*
		$formdata['format']='pdf';
		$pdf = new single_button(
			new moodle_url(MOD_FLUENCYBUILDER_URL . '/reports.php',$formdata),
			get_string('exportpdf',MOD_FLUENCYBUILDER_LANG), 'get');
		*/
		$formdata['format']='csv';
		$excel = new single_button(
			new moodle_url(MOD_FLUENCYBUILDER_URL . '/reports.php',$formdata), 
			get_string('exportexcel',MOD_FLUENCYBUILDER_LANG), 'get');

		return html_writer::div( $this->render($excel),MOD_FLUENCYBUILDER_CLASS  . '_actionbuttons');
	}
	

	
	public function render_section_csv($sectiontitle, $report, $head, $rows, $fields) {

        // Use the sectiontitle as the file name. Clean it and change any non-filename characters to '_'.
        $name = clean_param($sectiontitle, PARAM_FILE);
        $name = preg_replace("/[^A-Z0-9]+/i", "_", trim($name));
		$quote = '"';
		$delim= ",";//"\t";
		$newline = "\r\n";

		header("Content-Disposition: attachment; filename=$name.csv");
		header("Content-Type: text/comma-separated-values");

		//echo header
		$heading="";	
		foreach($head as $headfield){
			$heading .= $quote . $headfield . $quote . $delim ;
		}
		echo $heading. $newline;
		
		//echo data rows
        foreach ($rows as $row) {
			$datarow = "";
			foreach($fields as $field){
				$datarow .= $quote . $row->{$field} . $quote . $delim ;
			}
			 echo $datarow . $newline;
		}
        exit();
	}

	public function render_section_html($sectiontitle, $report, $head, $rows, $fields) {
		global $CFG;
		if(empty($rows)){
			return $this->render_empty_section_html($sectiontitle);
		}
		
		//set up our table and head attributes
		$tableattributes = array('class'=>'generaltable '. MOD_FLUENCYBUILDER_CLASS .'_table');
		$headrow_attributes = array('class'=>MOD_FLUENCYBUILDER_CLASS . '_headrow');
		
		$htmltable = new html_table();
		$htmltable->attributes = $tableattributes;
		
		
		$htr = new html_table_row();
		$htr->attributes = $headrow_attributes;
		foreach($head as $headcell){
			$htr->cells[]=new html_table_cell($headcell);
		}
		$htmltable->data[]=$htr;
		
		foreach($rows as $row){
			$htr = new html_table_row();
			//set up descrption cell
			$cells = array();
			foreach($fields as $field){
				$cell = new html_table_cell($row->{$field});
				$cell->attributes= array('class'=>MOD_FLUENCYBUILDER_CLASS . '_cell_' . $report . '_' . $field);
				$htr->cells[] = $cell;
			}

			$htmltable->data[]=$htr;
		}
		$html = $this->output->heading($sectiontitle, 4);
		$html .= html_writer::table($htmltable);
		return $html;
		
	}
	
	function show_reports_footer($moduleinstance,$cm,$formdata,$showreport){
		// print's a popup link to your custom page
		$link = new moodle_url(MOD_FLUENCYBUILDER_URL . '/reports.php',array('report'=>'menu','id'=>$cm->id,'n'=>$moduleinstance->id));
		$ret =  html_writer::link($link, get_string('returntoreports',MOD_FLUENCYBUILDER_LANG));
		$ret .= $this->render_exportbuttons_html($cm,$formdata,$showreport);
		return $ret;
	}

}

/**
 * A custom renderer class that outputs JSON representation for CST
 *
 * @package mod_fluencybuilder
 * @copyright COPYRIGHTNOTICE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_fluencybuilder_json_renderer extends plugin_renderer_base {


	 /**
	 * Return json for sessions (session = array of taskids)
	 * Depending on the settings for the fluencybuilder instance
	 * we add screens for consent and session selection etc
	 *
	 * @param string $title
	 * @param string $context
	 * @param array $items
	 * @param stdClass $fluencybuilder
	 * @return stdClass
	 */
	 public function render_session($items) {
		$session = new stdClass;
		$session->items = $items;
		$session->itemcount = count($items);

		return json_encode($session);
	 }

	
	
	/**
	 * Return HTML to display add first page links
	 * @param lesson $lesson
	 * @return string
	 */
	 public function render_tasks_json($title,$context,$items,$fluencybuilder,$cm) {
		$config  = get_config(MOD_FLUENCYBUILDER_FRANKY);
		
		/*
		$partnerconfirmitem= new stdClass();
		$partnerconfirmitem->id = self::PARTNERCONFIRMID;
		$partnerconfirmitem->type =MOD_FLUENCYBUILDER_FBQUESTION_TYPE_PARTNERCONFIRM;
		$partnerconfirmitem->{MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION}= '';
		array_unshift($items,$partnerconfirmitem);
		*/
		
		
		//process our tasks
		$tasks = array();
		foreach($items as $item){
			$tasks[] = $this->render_fbquestion($context,$item,$cm);
		}
		$test->tasks = $tasks;
		
		//build our return object
		$ret = new stdClass;
		$ret->test = $test;
		
		return json_encode($ret);
	 }
	 
	 public function fetch_item_id($item){
		switch($item->type){
			case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_PICTUREPROMPT:
				//$return = 'picture_' . $item->id;
				$return  = $item->id;
				break;
			case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_TEXTPROMPT:
				//$return = 'listen_' . $item->id;
				$return  = $item->id;
				break;
            case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_AUDIOPROMPT:
                //$return = 'listen_' . $item->id;
                $return  = $item->id;
                break;
			default:
				$return  = $item->id;
		}
		return $return;
	 }

	/**
	 * Return HTML to display add first page links
	 * @param lesson $lesson
	 * @return string
	 */
	 public function render_fbitem($context,$item,$cm) {
	 global $DB;
	 
		$theitem = new stdClass;
		$theitem->id = $this->fetch_item_id($item->type, $item);
		if(!isset($item->timetarget)){
			$item->timetarget = 0;
		}
		$theitem->timetarget=$item->timetarget;


	//Items from a different fluencybuilder will have a different context, so we need to fetch
	//this needs to be optimised: JUSTIN 20160922
	if(property_exists($item,'fluencybuilder')){
		$moduleid = $cm->module;
		$item_cm_id = $DB->get_field('course_modules',
		'id',array('module'=>$moduleid,'instance'=>$item->fluencybuilder),
		IGNORE_MULTIPLE);
		
		//we have a problem of orphan fbquestions when fluencybuilders are deleted (restored?)
		//This will stop errors being thrown, but won't deliver the correct context
		//TO DO Justin 20160922
		if($item_cm_id){
			$item_context = context_module::instance($item_cm_id);
		}else{
			$item_context = $context;
		}
	}else{
		$item_context = $context;
	}

		switch($item->type){
			case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_PICTUREPROMPT:
				$theitem->pictureprompt=$this->fetch_media_url($item_context,MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT_FILEAREA,$item);
                $theitem->audiomodel=$this->fetch_media_url($item_context,MOD_FLUENCYBUILDER_FBQUESTION_AUDIOMODEL_FILEAREA,$item);
				break;
				
			case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_TEXTPROMPT:
                $theitem->audioprompt=$this->fetch_media_url($item_context,MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT_FILEAREA,$item);
                $theitem->audiomodel=$this->fetch_media_url($item_context,MOD_FLUENCYBUILDER_FBQUESTION_AUDIOMODEL_FILEAREA,$item);
				break;
			
			case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_AUDIOPROMPT:
				$theitem->audioprompt=$this->fetch_media_url($item_context,MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT_FILEAREA,$item);
                $theitem->audiomodel=$this->fetch_media_url($item_context,MOD_FLUENCYBUILDER_FBQUESTION_AUDIOMODEL_FILEAREA,$item);
				break;

				
		}
		return $theitem;
	 }

}


