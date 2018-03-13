<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:53
 */

namespace mod_fluencybuilder\report;


class latestattemptsummary extends basereport
{
    protected $report="latestattemptsummary";
    protected $fields = array();//this is set in process raw data
    protected $headingdata = null;
    protected $qcache=array();
    protected $ucache=array();

    public function fetch_head(){
        $head=array();
        foreach($this->fields as $field){
            if(strpos($field,'item_correct_')===0){
                $itemid = str_replace('item_correct_','',$field);
                $fbquestion =$this->fetch_cache('fluencybuilder_fbquestions',$itemid);
                if($fbquestion){
                    $head[]=$fbquestion->name . ':correct' ;
                }else{
                    $head[]='item:correct';
                }

            }else{
                $head[]=get_string($field,MOD_FLUENCYBUILDER_LANG);
            }
        }
        return $head;
    }

    public function fetch_formatted_field($field,$record,$withlinks){
        global $DB;
        switch($field){

            case 'username':
                $theuser = $this->fetch_cache('user',$record->username);
                $ret=fullname($theuser);
                break;

            case 'fluencybuilder':
                $thefluencybuilder = $this->fetch_cache('fluencybuilder',$record->fluencybuilder);
                $ret=$thefluencybuilder->name;
                break;

            default:
                //put logic here if need to format item correct or time
                if(strpos($field,'item_correct_')===0){
                    //do something
                }

                if(property_exists($record,$field)){
                    $ret=$record->{$field};
                }else{
                    $ret = '';
                }
        }
        return $ret;
    }

    public function fetch_formatted_heading(){
        return get_string('latestattemptsummary',MOD_FLUENCYBUILDER_LANG,$this->headingdata->name );
    }

    public function process_raw_data($formdata,$moduleinstance){
        global $DB;

        //heading data for report header, add moodle cst name
        $this->headingdata = new \stdClass();
        $this->headingdata = $this->fetch_cache('fluencybuilder',$moduleinstance->id);

        $emptydata = array();

        $itemarray= $DB->get_fieldset_select(MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE,
            'itemid', 'fluencybuilderid = ?',array($moduleinstance->id));
        $items = array_unique($itemarray);

        //print_r($items);

        $sql ='SELECT *, MAX(attemptid) as maxattemptid FROM {' . MOD_FLUENCYBUILDER_ATTEMPTITEMTABLE . '} ';
        $sql .= 'WHERE fluencybuilderid =? AND itemid IN ('. implode(',',$items) .') GROUP BY userid,itemid';

        //echo $sql;
        //die;

        $itemsbyuser = $DB->get_records_sql($sql,array($moduleinstance->id));

        //update the fields since each run of the report may have diff fields in it
        $this->fields = array('username');
        foreach($items as $item){
            $this->fields[]='item_correct_' . $item;
        }

        //sometimes we get a userid of 0 ... this is odd
        //how does that happen. Anyway default is -1 which means the first
        //pass of data processing will detect a new user data set
        $currentuserid=-1;

        $rawdatarow = false;
        foreach($itemsbyuser as $useritem){
            //data is a series of rows each of a diff fbquestion grouped by user
            //so we group data till the user changes, then we stash it
            if($useritem->userid!=$currentuserid){
                if($rawdatarow){
                    $this->rawdata[]= $rawdatarow;
                }
                $currentuserid = $useritem->userid;
                $rawdatarow= new \stdClass;
                $rawdatarow->username=$useritem->userid;
                $rawdatarow->fluencybuilder=$moduleinstance->id;
                foreach($items as $item){
                    $rawdatarow->{'item_correct_' . $item}='-';
                }
            }
            //stash the slide pair data
            $rawdatarow->{'item_correct_' . $useritem->itemid}=$useritem->correct;
        }
        if($rawdatarow){
            $this->rawdata[]= $rawdatarow;
        }

        if(!$rawdatarow){
            $this->rawdata= $emptydata;
        }
        return true;
    }

}