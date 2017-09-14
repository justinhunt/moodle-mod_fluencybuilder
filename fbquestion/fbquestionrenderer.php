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

require_once($CFG->dirroot .'/mod/fluencybuilder/fbquestion/fbquestionforms.php');

//require_once($CFG->dirroot.'/mod/fluencybuilder/locallib.php');

/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package mod_fluencybuilder
 * @copyright COPYRIGHTNOTICE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_fluencybuilder_fbquestion_renderer extends plugin_renderer_base {

 /**
 * Return HTML to display add first page links
 * @param lesson $lesson
 * @return string
 */
 public function add_edit_page_links($fluencybuilder) {
		global $CFG;
        $itemid = 0;

        $output = $this->output->heading(get_string("whatdonow", "fluencybuilder"), 3);
        $links = array();
/*
		$addtextchoiceitemurl = new moodle_url('/mod/fluencybuilder/fbquestion/managefbquestions.php',
			array('id'=>$this->page->cm->id, 'itemid'=>$itemid, 'type'=>MOD_FLUENCYBUILDER_FBQUESTION_TYPE_TEXTPROMPT));
        $links[] = html_writer::link($addtextchoiceitemurl, get_string('addtextpromptitem', 'fluencybuilder'));
		
        $addpicturechoiceitemurl = new moodle_url('/mod/fluencybuilder/fbquestion/managefbquestions.php',
			array('id'=>$this->page->cm->id, 'itemid'=>$itemid, 'type'=>MOD_FLUENCYBUILDER_FBQUESTION_TYPE_PICTUREPROMPT));
        $links[] = html_writer::link($addpicturechoiceitemurl, get_string('addpicturepromptitem', 'fluencybuilder'));
  */
		//for now we can this. Later lets fix it up.
        $addaudiopromptitemurl = new moodle_url('/mod/fluencybuilder/fbquestion/managefbquestions.php',
			array('id'=>$this->page->cm->id, 'itemid'=>$itemid, 'type'=>MOD_FLUENCYBUILDER_FBQUESTION_TYPE_AUDIOPROMPT));
            $links[] = html_writer::link($addaudiopromptitemurl, get_string('addaudiopromptitem', 'fluencybuilder'));
		

		
        return $this->output->box($output.'<p>'.implode('</p><p>', $links).'</p>', 'generalbox firstpageoptions');
    }
	
	/**
	 * Return the html table of items
	 * @param array homework objects
	 * @param integer $courseid
	 * @return string html of table
	 */
	function show_items_list($items,$fluencybuilder,$cm){
	
		if(!$items){
			return $this->output->heading(get_string('noitems','fluencybuilder'), 3, 'main');
		}
	
		$table = new html_table();
		$table->id = 'MOD_FLUENCYBUILDER_qpanel';
		$table->head = array(
			get_string('itemname', 'fluencybuilder'),
			get_string('itemtype', 'fluencybuilder'),
			get_string('actions', 'fluencybuilder')
		);
		$table->headspan = array(1,1,3);
		$table->colclasses = array(
			'itemname', 'itemtitle', 'order', 'edit','delete'
		);

		//sort by start date
		//core_collator::asort_objects_by_property($items,'timecreated',core_collator::SORT_NUMERIC);
		//core_collator::asort_objects_by_property($items,'name',core_collator::SORT_STRING);

		//loop through the items and add to table
        $currentitem=0;
		foreach ($items as $item) {
            $currentitem++;
            $row = new html_table_row();


            $itemnamecell = new html_table_cell($item->name);
            switch ($item->type) {
                case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_PICTUREPROMPT:
                    $itemtype = get_string('picturechoice', 'fluencybuilder');
                    break;
                case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_AUDIOPROMPT:
                    $itemtype = get_string('audioprompt', 'fluencybuilder');
                    break;
                case MOD_FLUENCYBUILDER_FBQUESTION_TYPE_TEXTPROMPT:
                    $itemtype = get_string('textchoice', 'fluencybuilder');
                    break;
                default:
            }
            $itemtypecell = new html_table_cell($itemtype);

            $actionurl = '/mod/fluencybuilder/fbquestion/managefbquestions.php';
            $editurl = new moodle_url($actionurl, array('id' => $cm->id, 'itemid' => $item->id));
            $editlink = html_writer::link($editurl, get_string('edititem', 'fluencybuilder'));
            $editcell = new html_table_cell($editlink);

            $movecell_content='';
            $spacer = '';
            if ($currentitem > 1) {
                $upurl = new moodle_url($actionurl, array('id' => $cm->id, 'itemid' => $item->id, 'action' => 'moveup'));
               // $uplink = html_writer::link($upurl,  new pix_icon('t/up', get_string('up'), '', array('class' => 'iconsmall')));
                $uplink = $this->output->action_icon($upurl,new pix_icon('t/up', get_string('up'), '', array('class' => 'iconsmall')));
                $movecell_content .= $uplink;
            }else{
                $movecell_content .= $spacer;
            }

            if ($currentitem < count($items)) {
                $downurl = new moodle_url($actionurl, array('id' => $cm->id, 'itemid' => $item->id, 'action' => 'movedown'));
                //$downlink = html_writer::link($downurl,  new pix_icon('t/down', get_string('down'), '', array('class' => 'iconsmall')));
                $downlink = $this->output->action_icon($downurl,new pix_icon('t/down', get_string('down'), '', array('class' => 'iconsmall')));
                $movecell_content .= $downlink;
            }
            $movecell = new html_table_cell($movecell_content);
		
			$deleteurl = new moodle_url($actionurl, array('id'=>$cm->id,'itemid'=>$item->id,'action'=>'confirmdelete'));
			$deletelink = html_writer::link($deleteurl, get_string('deleteitem', 'fluencybuilder'));
			$deletecell = new html_table_cell($deletelink);

			$row->cells = array(
				$itemnamecell, $itemtypecell, $movecell, $editcell, $deletecell
			);
			$table->data[] = $row;
		}

		return html_writer::table($table);

	}
}