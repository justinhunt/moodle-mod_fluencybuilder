<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:55
 */

namespace mod_fluencybuilder\report;


class onefbquestion
{
    protected $report="onefbquestion";
    protected $fields = array('id','username','answer','correct','totaltime','timecreated');
    protected $headingdata = null;
    protected $qcache=array();
    protected $ucache=array();

    public function fetch_formatted_field($field,$record,$withlinks){
        global $DB;
        switch($field){
            case 'id':
                $ret = $record->id;
                break;

            case 'username':
                $user = $this->fetch_cache('user',$record->userid);
                $ret=fullname($user);
                break;


            case 'correct':
                $theuser = $this->fetch_cache('user',$record->partnerid);
                $ret=$record->correct ? get_string('yes') : get_string('no');
                break;

            case 'answer':
                $ret=$record->answerid;
                break;

            case 'totaltime':
                $ret= $this->fetch_formatted_milliseconds($record->duration);
                break;

            case 'timecreated':
                $ret = date("Y-m-d H:i:s",$record->timecreated);
                break;

            default:
                if(property_exists($record,$field)){
                    $ret=$record->{$field};
                }else{
                    $ret = '';
                }
        }
        return $ret;
    }

    public function fetch_formatted_heading(){
        $record = $this->headingdata;
        $ret='';
        if(!$record){return $ret;}
        //$ec = $this->fetch_cache(MOD_FLUENCYBUILDER_TABLE,$record->englishcentralid);
        return get_string('onefbquestionheading',MOD_FLUENCYBUILDER_LANG);

    }

    public function process_raw_data($formdata,$moduleinstance){
        global $DB;

        //heading data
        $this->headingdata = new \stdClass();

        $emptydata = array();
        $alldata = $DB->get_records(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,array('itemid'=>$formdata->itemid,'course'=>$moduleinstance->course,'fluencybuilderid'=>$moduleinstance->id));
        if($alldata){
            $this->rawdata= $alldata;
        }else{
            $this->rawdata= $emptydata;
        }
        return true;
    }

}