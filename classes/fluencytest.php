<?php
/**
 * Created by PhpStorm.
 * User: justin
 * Date: 17/08/29
 * Time: 16:12
 */

namespace mod_fluencybuilder;


class fluencytest
{

    protected $cm;
    protected $context;
    protected $mod;
    protected $items;

    public function __construct($cm) {
        global $DB;
        $this->cm = $cm;
        $this->mod = $DB->get_record('fluencybuilder', ['id' => $cm->instance], '*', MUST_EXIST);
        $this->context = \context_module::instance($cm->id);
    }

    public function fetch_media_url($filearea,$item){
        //get question audio div (not so easy)
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'mod_fluencybuilder',$filearea,$item->id);
        foreach ($files as $file) {
            $filename = $file->get_filename();
            if($filename=='.'){continue;}
            $filepath = '/';
            $mediaurl = \moodle_url::make_pluginfile_url($this->context->id,'mod_fluencybuilder',
                $filearea, $item->id,
                $filepath, $filename);
            return $mediaurl->__toString();

        }
        //We always take the first file and if we have none, thats not good.
        return "";
       // return "$this->context->id pp $filearea pp $item->id";
    }

    public function fetch_items()
    {
        global $DB;
        if (!$this->items) {
            $this->items = $DB->get_records('fluencybuilder_fbquestions', ['fluencybuilder' => $this->mod->id],'itemorder ASC');
        }
        if($this->items){
            return $this->items;
        }else{
            return [];
        }
    }

    public function fetch_latest_attempt($userid){
        global $DB;

        $attempts = $DB->get_records(MOD_FLUENCYBUILDER_ATTEMPTTABLE,array('fluencybuilderid' => $this->mod->id,'userid'=>$userid),'id DESC');
        if($attempts){
            $attempt = array_shift($attempts);
            return $attempt;
        }else{
            return false;
        }
    }


    public function fetch_attemptitems($userid, $attemptid=0)
    {
        global $DB;

        if($attemptid==0){
            $attempt = $this->fetch_latest_attempt($userid);
            $attemptid = $attempt->id;
        }
        $attemptitems = $DB->get_records_menu(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE, array('fluencybuilderid' => $this->mod->id,'userid'=>$userid,'attemptid'=>$attemptid),'timecreated ASC', 'itemid,correct');
        if($attemptitems){
            return $attemptitems;
        }else{
            return [];
        }
    }



    public function prepare_recorder_tool($resourceurl, $modelurl, $item){
        global $USER;

        $oldfilename="";
        $itemid = $item->id;
        $usercontextid=\context_user::instance($USER->id)->id;

        $hints=Array();
        $hints['resource']=$resourceurl;
        $hints['resource2']=$modelurl;
        $hints['modulecontextid']=$this->context->id;
        $hints['mediaskin']='fluencybuilder';

        $text='';
        $options = Array();

        if ($itemid){

            $draftitemid = file_get_submitted_draft_itemid('field_'. $itemid);
            $text = file_prepare_draft_area($draftitemid, $this->context->id, 'mod_fluencybuilder', 'content', $itemid, $options, $text);
        } else {
            $draftitemid = file_get_unused_draft_itemid();
        }

        $updatecontrol = 'fluencyfile';
        $idcontrol = $updatecontrol  . '_itemid';

        $str = '<input type="hidden" id="'. $updatecontrol .'" name="'. $updatecontrol .'" value="' . $oldfilename . '" />';
        $str .= '<input type="hidden"  name="'. $idcontrol .'" value="'.$draftitemid.'" />';

        // $type = DBP_AUDIOMP3;

        $callbackjs=false;
        $mediatype='audio';
        $timelimit=$item->timetarget;


        $ret = \filter_poodll\poodlltools::fetchAMDRecorderCode($mediatype, $updatecontrol, $usercontextid, 'user', 'draft', $itemid, $timelimit, $callbackjs,$hints);
        return $str . $ret;

    }//end of function


    public function prepare_blank_recorder_tool(){
        global $USER;

        $oldfilename="";
        $itemid = 99;
        $usercontextid=\context_user::instance($USER->id)->id;

        $hints=Array();
        //$hints['resource']=$resourceurl;
        //$hints['resource2']=$modelurl;
        $hints['modulecontextid']=$this->context->id;
        $hints['mediaskin']='fluencybuilder';

        $text='';
        $options = Array();
        $draftitemid = file_get_unused_draft_itemid();


        $updatecontrol = 'fluencyfile';
        $idcontrol = $updatecontrol  . '_itemid';

        $str = '<input type="hidden" id="'. $updatecontrol .'" name="'. $updatecontrol .'" value="' . $oldfilename . '" />';
        $str .= '<input type="hidden"  name="'. $idcontrol .'" value="'.$draftitemid.'" />';

        // $type = DBP_AUDIOMP3;

        $callbackjs=false;
        $mediatype='audio';
        $timelimit=0;//$item->timetarget;


        $ret = \filter_poodll\poodlltools::fetchAMDRecorderCode($mediatype, $updatecontrol, $usercontextid, 'user', 'draft', $itemid, $timelimit, $callbackjs,$hints);
        return $str . $ret;

    }//end of function

    public function fetch_test_data_for_js(){

        $items = $this->fetch_items();

        //prepare data array for test
        $testitems=array();
        $currentitem=0;
        $itemcount=count($items);
        foreach($items as $item) {
            $currentitem++;
            $testitem= new \stdClass();
            $testitem->resourceurl = $this->fetch_media_url(\mod_fluencybuilder\fbquestion\constants::AUDIOPROMPT_FILEAREA, $item);
            $testitem->modelurl = $this->fetch_media_url(\mod_fluencybuilder\fbquestion\constants::AUDIOMODEL_FILEAREA, $item);
            $testitem->itemprogress =  $currentitem . '/' . $itemcount;
            $testitem->itemheader =  $this->mod->questionheader;
            $testitem->itemtext =  $item->{\mod_fluencybuilder\fbquestion\constants::TEXTQUESTION};
            $testitem->timetarget = $item->timetarget;
            $testitem->itemid = $item->id;
            $testitems[]=$testitem;
        }
        return $testitems;
    }


}//end of class