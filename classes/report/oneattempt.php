<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:54
 */

namespace mod_fluencybuilder\report;


class oneattempt extends basereport
{
    protected $report="oneattempt";
    protected $fields = array('id','fbquestionname','correct','timecreated');
    protected $headingdata = null;
    protected $qcache=array();
    protected $ucache=array();

    public function fetch_formatted_field($field,$record,$withlinks){
        global $DB;
        switch($field){
            case 'id':
                $ret = $record->id;
                if($withlinks && false){
                    $oneattempturl = new \moodle_url('/mod/fluencybuilder/reports.php',
                        array('n'=>$record->fluencybuilderid,
                            'report'=>'oneattempt',
                            'itemid'=>$record->id));
                    $ret = \html_writer::link($oneattempturl,$ret);
                }
                break;

            case 'fbquestionname':
                $thefbquestion = $this->fetch_cache(\mod_fluencybuilder\fbquestion\constants::TABLE,$record->itemid);
                $ret=$thefbquestion->name;
                break;

            case 'correct':
                $ret=$record->correct ? get_string('yes') : get_string('no');
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
        return get_string('oneattemptheading',MOD_FLUENCYBUILDER_LANG);

    }

    public function process_raw_data($formdata,$moduleinstance){
        global $DB;

        //heading data
        $this->headingdata = new \stdClass();

        $emptydata = array();
        $alldata = $DB->get_records(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,array('attemptid'=>$formdata->attemptid,'course'=>$moduleinstance->course,'fluencybuilderid'=>$moduleinstance->id));
        if($alldata){
            $this->rawdata= $alldata;
        }else{
            $this->rawdata= $emptydata;
        }
        return true;
    }
}