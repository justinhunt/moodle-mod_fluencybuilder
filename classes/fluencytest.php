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
            $this->items = $DB->get_records('fluencybuilder_fbquestions', ['fluencybuilder' => $this->mod->id]);
        }
        if($this->items){
            return $this->items;
        }else{
            return [];
        }
    }

    public function prepare_review_widget($resourceurl, $modelurl, $item){


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

}//end of class