<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:53
 */

namespace mod_fluencybuilder\report;


class allattempts extends basereport
{
    protected $report="allattempts";
    protected $fields = array('id','username','sessionscore','timecreated', 'delete');
    protected $headingdata = null;
    protected $qcache=array();
    protected $ucache=array();

    public function fetch_formatted_field($field,$record,$withlinks){
        global $DB;
        switch($field){
            case 'id':
                $ret = $record->id;
                if($withlinks){
                    $oneattempturl = new \moodle_url('/mod/fluencybuilder/reports.php',
                        array('n'=>$record->fluencybuilderid,
                            'report'=>'oneattempt',
                            'itemid'=>$record->id));
                    $ret = \html_writer::link($oneattempturl,$ret);
                }
                break;

            case 'username':
                $theuser = $this->fetch_cache('user',$record->userid);
                $ret=fullname($theuser);
                break;


            case 'timecreated':
                $ret = date("Y-m-d H:i:s",$record->timecreated);
                break;


            case 'delete':
                if($withlinks){
                    $actionurl = '/mod/fluencybuilder/manageattempts.php';
                    $deleteurl = new \moodle_url($actionurl, array('id'=>$record->cmid,'attemptid'=>$record->id,'action'=>'confirmdelete'));
                    $ret = \html_writer::link($deleteurl, get_string('deleteattempt', 'fluencybuilder'));
                }else{
                    $ret="";
                }
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
        return get_string('allattemptsheading',MOD_FLUENCYBUILDER_LANG);

    }

    public function process_raw_data($formdata,$moduleinstance){
        global $DB;

        //heading data
        $this->headingdata = new \stdClass();

        $emptydata = array();
        $alldata = $DB->get_records(MOD_FLUENCYBUILDER_ATTEMPTTABLE,array('course'=>$moduleinstance->course,'fluencybuilderid'=>$moduleinstance->id));


        foreach($alldata as $adata){
            $adata->cmid = $formdata->cmid;
        }

        if($alldata){
            $this->rawdata= $alldata;
        }else{
            $this->rawdata= $emptydata;
        }
        return true;
    }

}