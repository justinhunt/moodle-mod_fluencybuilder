<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:54
 */

namespace mod_fluencybuilder\report;


class allfbquestions extends basereport
{
    protected $report="allfbquestions";
    protected $fields = array('id','fbquestionname','count','avgcorrect');
    protected $headingdata = null;
    protected $qcache=array();
    protected $ucache=array();

    public function fetch_formatted_field($field,$record,$withlinks){
        global $DB;
        switch($field){
            case 'id':
                $ret = $record->itemid;
                if($withlinks){
                    $onefbquestionurl = new \moodle_url('/mod/fluencybuilder/reports.php',
                        array('n'=>$record->fluencybuilderid,
                            'report'=>'onefbquestion',
                            'itemid'=>$record->itemid));
                    $ret = \html_writer::link($onefbquestionurl,$ret);
                }
                break;
                break;

            case 'fbquestionname':
                $thefbquestion = $this->fetch_cache(\mod_fluencybuilder\fbquestion\constants::TABLE,$record->itemid);
                $ret=$thefbquestion->name;
                break;

            case 'count':
                $ret=$record->cntitemid;
                break;

            case 'avgcorrect':
                $ret= round($record->avgcorrect,2);
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
        return get_string('allfbquestionsheading',MOD_FLUENCYBUILDER_LANG);

    }

    public function process_raw_data($formdata,$moduleinstance){
        global $DB;

        //heading data
        $this->headingdata = new \stdClass();

        $emptydata = array();
        $alldata= $DB->get_records_sql('SELECT itemid,fluencybuilderid,COUNT(itemid) AS cntitemid, AVG(correct) AS avgcorrect  FROM {'.	MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE.'} WHERE fluencybuilderid=:fluencybuilderid GROUP BY itemid',array('fluencybuilderid'=>$moduleinstance->id));

        if($alldata){
            $this->rawdata= $alldata;
        }else{
            $this->rawdata= $emptydata;
        }
        return true;
    }

}