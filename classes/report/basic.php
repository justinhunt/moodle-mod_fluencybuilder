<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:52
 */

namespace mod_fluencybuilder\report;


class basic extends basereport
{

    protected $report="basic";
    protected $fields = array('id','name','timecreated');
    protected $headingdata = null;
    protected $qcache=array();
    protected $ucache=array();

    public function fetch_formatted_field($field,$record,$withlinks){
        switch($field){
            case 'id':
                $ret = $record->id;
                break;

            case 'name':
                $ret = $record->name;
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
        return get_string('basicheading',MOD_FLUENCYBUILDER_LANG);

    }

    public function process_raw_data($formdata,$moduleinstance){
        global $DB;

        //heading data
        $this->headingdata = new \stdClass();

        $emptydata = array();
        $alldata = $DB->get_records(MOD_FLUENCYBUILDER_TABLE,array());
        if($alldata){
            $this->rawdata= $alldata;
        }else{
            $this->rawdata= $emptydata;
        }
        return true;
    }

}