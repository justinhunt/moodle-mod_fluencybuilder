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

require_once($CFG->dirroot .'/mod/fluencybuilder/session/sessionforms.php');

//require_once($CFG->dirroot.'/mod/fluencybuilder/locallib.php');

/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package mod_fluencybuilder
 * @copyright COPYRIGHTNOTICE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_fluencybuilder_session_renderer extends plugin_renderer_base {

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

        $addnormalsessionurl = new moodle_url('/mod/fluencybuilder/session/managesessions.php',
			array('id'=>$this->page->cm->id, 'itemid'=>$itemid, 'type'=>MOD_FLUENCYBUILDER_SESSION_TYPE_NORMAL));
        $links[] = html_writer::link($addnormalsessionurl, get_string('addnormalsession', 'fluencybuilder'));
        

        return $this->output->box($output.'<p>'.implode('</p><p>', $links).'</p>', 'generalbox firstpageoptions');
    }
	
	/**
	 * Return the html table of homeworks for a group  / course
	 * @param array homework objects
	 * @param integer $courseid
	 * @return string html of table
	 */
	function show_items_list($items,$fluencybuilder,$cm){
	
		if(!$items){
			return $this->output->heading(get_string('nosessions','fluencybuilder'), 3, 'main');
		}
	
		$table = new html_table();
		$table->id = 'mod_fluencybuilder_qpanel';
		$table->head = array(
			get_string('sessionname', 'fluencybuilder'),
			get_string('sessiontype', 'fluencybuilder'),
			get_string('active', 'fluencybuilder'),
			get_string('fbquestioncount', 'fluencybuilder'),
			get_string('actions', 'fluencybuilder')
		);
		$table->headspan = array(1,1,1,1,3);
		$table->colclasses = array(
			'itemname', 'itemtype','active','fbquestioncount', 'edit','preview','delete'
		);

		//sort by start date
		core_collator::asort_objects_by_property($items,'timecreated',core_collator::SORT_NUMERIC);

		//loop through the sessions and add to table
		foreach ($items as $item) {
			$row = new html_table_row();
		
	        //session name	
			$itemnamecell = new html_table_cell($item->name);	
			//session type
			switch($item->type){
				case MOD_FLUENCYBUILDER_SESSION_TYPE_NORMAL:
				default:
					$itemtype = get_string('normal','fluencybuilder'); 
			} 
			$itemtypecell = new html_table_cell($itemtype);
			
			//session is active?
			$itemactive = $item->active ? get_string('yes') : get_string('no');
			$itemactivecell= new html_table_cell($itemactive);
		
			//session fbquestion item count
			$fbquestioncount = 0;
			if(strlen($item->fbquestionkeys)>0){
				$fbquestioncount = substr_count($item->fbquestionkeys, ',')+1; 
			}
			$itemfbquestioncountcell= new html_table_cell($fbquestioncount);
			
		
			//actions
			$actionurl = '/mod/fluencybuilder/session/managesessions.php';
			$editurl = new moodle_url($actionurl, array('id'=>$cm->id,'itemid'=>$item->id));
			$editlink = html_writer::link($editurl, get_string('editsession', 'fluencybuilder'));
			$editcell = new html_table_cell($editlink);
			
			//$previewurl = new moodle_url($actionurl, array('id'=>$cm->id,'itemid'=>$item->id, 'action'=>'previewitem'));
			//$previewlink = html_writer::link($previewurl, get_string('previewitem', 'fluencybuilder'));
			$previewlink = $this->fetch_preview_link($item->id,$fluencybuilder->id);
			$previewcell = new html_table_cell($previewlink);
		
			$deleteurl = new moodle_url($actionurl, array('id'=>$cm->id,'itemid'=>$item->id,'action'=>'confirmdelete'));
			$deletelink = html_writer::link($deleteurl, get_string('deletesession', 'fluencybuilder'));
			$deletecell = new html_table_cell($deletelink);

			$row->cells = array(
				$itemnamecell, $itemtypecell, $itemactivecell, $itemfbquestioncountcell, $editcell, $previewcell, $deletecell
			);
			$table->data[] = $row;
		}

		return html_writer::table($table);

	}
	
	public function fetch_preview_link($itemid,$fluencybuilderid){
		return '-';
	
	}
	
	public function fetch_chooser($chosen,$unchosen){
		//select lists
		$config= get_config('fluencybuilder');
		//$listheight=$config->listheight;
		$listheight = 10;
		if(!$listheight){$listheight=MOD_FLUENCYBUILDER_SESSION_LISTSIZE;}
		 $listboxopts = array('class'=>MOD_FLUENCYBUILDER_SESSION_SELECT, 'size'=>$listheight,'multiple'=>true);
		 $chosenbox =	html_writer::select($chosen,MOD_FLUENCYBUILDER_SESSION_CHOSEN,'',false,$listboxopts);
		 $unchosenbox =	html_writer::select($unchosen,MOD_FLUENCYBUILDER_SESSION_UNCHOSEN,'',false,$listboxopts);

		 
		 //buttons
		 $choosebutton = html_writer::tag('button',get_string('choose','fluencybuilder'),  
					array('type'=>'button','class'=>'mod_fluencybuilder_session_button yui3-button',
					'id'=>'mod_fluencybuilder_session_choosebutton','onclick'=>'M.mod_fluencybuilder_session.choose()'));
		$unchoosebutton = html_writer::tag('button',get_string('unchoose','fluencybuilder'),  
					array('type'=>'button','class'=>'mod_fluencybuilder_session_button yui3-button',
					'id'=>'mod_fluencybuilder_session_unchoosebutton','onclick'=>'M.mod_fluencybuilder_session.unchoose()'));
		$buttonbox = html_writer::tag('div', $choosebutton . '<br/>' . $unchoosebutton, array('class'=>'mod_fluencybuilder_session_buttoncontainer','id'=>'mod_fluencybuilder_session_buttoncontainer'));
		 
		 //filters
		 $chosenfilter = html_writer::tag('input','',  
					array('type'=>'text','class'=>'mod_fluencybuilder_session_text',
					'id'=>'mod_fluencybuilder_session_chosenfilter','onkeyup'=>'M.mod_fluencybuilder_session.filter_chosen()'));
		 $unchosenfilter = html_writer::tag('input','',  
					array('type'=>'text','class'=>'mod_fluencybuilder_session_text',
					'id'=>'mod_fluencybuilder_session_unchosenfilter','onkeyup'=>'M.mod_fluencybuilder_session.filter_unchosen()'));
		
		//the field to update for form submission
		$chosenkeys = array_keys($chosen);
		$usekeys='';
		if(!empty($chosenkeys)){
			$usekeys = implode(',',$chosenkeys);
		}
		
		//choose component container
		$htmltable = new html_table();
		$htmltable->attributes = array('class'=>'generaltable mod_fluencybuilder_session_choosertable');
		
		//heading row
		$htr = new html_table_row();
		$htr->cells[] = get_string('chosenlabel','fluencybuilder');
		$htr->cells[] = '';
		$htr->cells[] = get_string('unchosenlabel','fluencybuilder');
		$htmltable->data[]=$htr;
		
		
		//chooser components
		$listcellattributes = array('class'=>'listcontainer');
		$buttoncellattributes = array('class'=>'buttoncontainer');
		
		$ftr = new html_table_row();
		$cell = new html_table_cell($chosenbox . '<br/>' . $chosenfilter);
		$cell->attributes =$listcellattributes;
		$ftr->cells[] = $cell;
		$cell = new html_table_cell($buttonbox);
		$cell->attributes =$buttoncellattributes;
		$ftr->cells[] = $cell;
		$cell = new html_table_cell($unchosenbox . '<br/>' . $unchosenfilter);
		$cell->attributes =$listcellattributes;
		$ftr->cells[] = $cell;
		$htmltable->data[]=$ftr;
		$chooser = html_writer::table($htmltable);
		
		return $chooser;
	}

}