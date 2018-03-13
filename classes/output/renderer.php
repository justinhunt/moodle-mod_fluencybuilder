<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:33
 */

namespace mod_fluencybuilder\output;


class renderer extends \plugin_renderer_base {
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
        $context = \context_module::instance($cm->id);

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

    /*
     * Show the list of recorders and dialogs for display on the activity page
     * Most will be hidden until it is their turn to e displayed
     *
     */
    public function show_items($cm,$fluencybuilder){

        $ret='';


        $fluencytest = new \mod_fluencybuilder\fluencytest($cm);
        $items = $fluencytest->fetch_items();
        $itemcount=count($items);
        $currentitem=0;
        foreach($items as $item) {
            $currentitem++;
            //$showorhide= $currentitem==1 '' : 'hide';
            $showorhide= 'hide';

            //recorder
            $resourceurl = $fluencytest->fetch_media_url(\mod_fluencybuilder\fbquestion\constants::AUDIOPROMPT_FILEAREA, $item);
            $modelurl = $fluencytest->fetch_media_url(\mod_fluencybuilder\fbquestion\constants::AUDIOMODEL_FILEAREA, $item);
            $recorder = $fluencytest->prepare_recorder_tool($resourceurl, $modelurl, $item);
            $itemprogress =  \html_writer::tag('h3',$currentitem . '/' . $itemcount, array('class' => MOD_FLUENCYBUILDER_CLASS  . '_itemprogress'));
            $itemheader =  \html_writer::tag('div',$fluencybuilder->questionheader, array('class' => MOD_FLUENCYBUILDER_CLASS  . '_itemtext'));
            $itemtext =  \html_writer::tag('div',$item->{\mod_fluencybuilder\fbquestion\constants::TEXTQUESTION}, array('class' => MOD_FLUENCYBUILDER_CLASS  . '_itemtext'));

            //post record dialog
            $ret.=  \html_writer::tag('div',$itemprogress . $itemheader . $itemtext . $recorder, array('id' => 'mod_fluencybuilder_dplaceholder_' . $currentitem, 'class' => MOD_FLUENCYBUILDER_CLASS  . '_itemholder ' . $showorhide));
            $opts=array('itemid' => $item->id, 'currentitem'=>$currentitem,'itemcount'=>$itemcount,'cmid'=>$cm->id);
            $this->page->requires->js_call_amd("mod_fluencybuilder/postrecorddialog", 'init', array($opts));
        }

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

    public function show_attempt_summary($attempt){

        $heading = $this->output->heading(get_string('attemptsummary_header',MOD_FLUENCYBUILDER_LANG),3);
        $score = \html_writer::div(get_string('summarysessionscore',MOD_FLUENCYBUILDER_LANG,$attempt->sessionscore ),'col-md-4 col-xs-12 col-sm-6 ' . MOD_FLUENCYBUILDER_CLASS  . '_sessionscore');
        $date = \html_writer::div(date("Y-m-d H:i:s",$attempt->timecreated),'col-md-5 col-xs-12 col-sm-6  ' . MOD_FLUENCYBUILDER_CLASS  . '_sessiondate');
        $summary= \html_writer::div($date . $score,'row ' . MOD_FLUENCYBUILDER_CLASS  . '_attemptsummary');
        return $heading . $summary;
    }

    public function show_attempt_review($cm){

        global $USER;

        $fluencytest = new \mod_fluencybuilder\fluencytest($cm);
        $items = $fluencytest->fetch_items();
        $latestattempt = $fluencytest->fetch_latest_attempt($USER->id);
        $attemptitems = $fluencytest->fetch_attemptitems($USER->id,$latestattempt->id);

        $attempt_summary = $this->show_attempt_summary($latestattempt);

        $rowtemplate = \html_writer::div('@@itemorder@@','col-md-4 col-xs-4 col-sm-4 ' . MOD_FLUENCYBUILDER_CLASS  . '_reviewrow_itemorder');
        $rowtemplate .= \html_writer::div('@@itemname@@','col-md-4 col-xs-4 col-sm-4 ' . MOD_FLUENCYBUILDER_CLASS  . '_reviewrow_itemname');
        $rowtemplate .= \html_writer::div('@@answer@@', 'col-md-4 col-xs-4 col-sm-4 ' .  MOD_FLUENCYBUILDER_CLASS  . '_reviewrow_answer');
        $rowtemplate = \html_writer::div($rowtemplate,'row ' .  MOD_FLUENCYBUILDER_CLASS  . '_reviewrow');

        //initialise rows
        $rows='';

        //details header
        $row = str_replace('@@itemorder@@','<strong>' .get_string('item',MOD_FLUENCYBUILDER_LANG) . '</strong>',$rowtemplate);
        $row = str_replace('@@itemname@@','<strong>' .get_string('itemname',MOD_FLUENCYBUILDER_LANG) . '</strong>',$row);
        $row = str_replace('@@answer@@','<strong>' . get_string('correct',MOD_FLUENCYBUILDER_LANG) . '</strong>',$row);
        $rows .= $row;

        //order items in same sequence as they were attempted
        $ordereditems = array();
        foreach ($attemptitems as $index=>$attemptitem){
            $found=false;
            foreach ($items as $item){
                if($item->id==$index){
                    $ordereditems[]=$item;
                    $found=true;
                    break;
                }
            }
            if(!$found){
                //this indicates an item has been deleted
                //what to do?
            }
        }

        //search for not attempted items
        $notattempteditems = array();
        foreach ($items as $item){
            $found=false;
            foreach ($ordereditems as $ordereditem) {
                if ($ordereditem->id == $item->id) {
                    $found=true;
                    break;
                }
            }
            if(!$found){$notattempteditems[]=$item;}
        }


        //build report of attempt
        $attemptorder=0;
        foreach ($ordereditems as $item){
            $attemptorder++;
            $row = str_replace('@@itemorder@@',$attemptorder,$rowtemplate);
            $row = str_replace('@@itemname@@',$item->name,$row);

            if(array_key_exists($item->id,$attemptitems)){
                $answer = $attemptitems[$item->id] ? 'O' : 'X';
            }else{
                $answer = '--';
            }
            $row = str_replace('@@answer@@',$answer,$row);

            $rows .= $row;
        }

        //build list of not attempted
        $narows ='';
        foreach ($notattempteditems as $item){
            $row = str_replace('@@itemorder@@','--',$rowtemplate);
            $row = str_replace('@@itemname@@',$item->name,$row);
            $row = str_replace('@@answer@@','',$row);
            $narows .= $row;
        }

        //results to this point
        $ret = $attempt_summary . $rows;

        //if we have not attempted items add those
        $notattemptedheader = $this->output->heading(get_string('notattempted_header',MOD_FLUENCYBUILDER_LANG),5);
        if(count($notattempteditems)>0){
            $ret .= $notattemptedheader . $narows;
        }
        return $ret;
    }


    public function containerwrap($content,$center=false){
        $centerclass='';
        if($center){$centerclass =  MOD_FLUENCYBUILDER_CLASS  . '_container_center';}
        return \html_writer::div($content, 'container ' .  MOD_FLUENCYBUILDER_CLASS  . '_container ' . $centerclass);
    }


    /**
     * Return HTML to display limited header
     */
    public function notabsheader(){
        return $this->output->header();
    }


    public function fetch_newsession_button($fluencybuilder,$caption) {
        global $CFG;
        $urlparams = array('n'=>$fluencybuilder->id,);
        $link = new \moodle_url($CFG->wwwroot . '/mod/fluencybuilder/activity.php',$urlparams);
        $ret =  \html_writer::link($link, $caption,array('class'=>'btn btn-primary ' . MOD_FLUENCYBUILDER_CLASS  . '_newsessionbutton'));
        return $ret;

    }

    public function fetch_cancel_button($cm){
        //cancel button
        $cancelid= \html_writer::random_id(MOD_FLUENCYBUILDER_CLASS . '_cancelholder_') ;
        $opts=array('holderid' => $cancelid,'cmid'=>$cm->id);
        $this->page->requires->js_call_amd("mod_fluencybuilder/canceldialog", 'init', array($opts));
        $cancelbutton =  \html_writer::link('#', get_string('cancelui_cancelactivity',MOD_FLUENCYBUILDER_LANG),array('class'=>MOD_FLUENCYBUILDER_CLASS  . '_dbutton ' . MOD_FLUENCYBUILDER_CLASS  . '_cancelbutton'));
        $ret =  \html_writer::tag('div',$cancelbutton, array('id' => $cancelid, 'class' => MOD_FLUENCYBUILDER_CLASS  . '_cancelholder'));
        return $ret;
    }


    public function fetch_start_button() {
        $buttonid = MOD_FLUENCYBUILDER_CLASS  . '_startbutton';
        $caption =  get_string('gotoactivity',MOD_FLUENCYBUILDER_LANG);
        $ret =  \html_writer::link('#', $caption,array('id'=>$buttonid,'class'=>'btn btn-primary ' . MOD_FLUENCYBUILDER_CLASS  . '_startbutton'));
        $opts=array('startbuttonid' => $buttonid);
        $this->page->requires->js_call_amd("mod_fluencybuilder/startactivitybutton", 'init', array($opts));
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
}