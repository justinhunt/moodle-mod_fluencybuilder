<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:26
 */

namespace mod_fluencybuilder\output;


class report_renderer extends \plugin_renderer_base
{
    public function render_reportmenu($moduleinstance,$cm, $reports) {
        $reportbuttons = array();
        foreach($reports as $report){
            $button = new \single_button(
                new \moodle_url(MOD_FLUENCYBUILDER_URL . '/reports.php',array('report'=>$report,'id'=>$cm->id,'n'=>$moduleinstance->id)),
                get_string($report .'report',MOD_FLUENCYBUILDER_LANG), 'get');
            $reportbuttons[] = $this->render($button);
        }

        $ret = \html_writer::div(implode('<br />',$reportbuttons) ,MOD_FLUENCYBUILDER_CLASS  . '_listbuttons');

        return $ret;
    }

    public function render_delete_allattempts($cm){
        $deleteallbutton = new \single_button(
            new \moodle_url(MOD_FLUENCYBUILDER_URL . '/manageattempts.php',array('id'=>$cm->id,'action'=>'confirmdeleteall')),
            get_string('deleteallattempts',MOD_FLUENCYBUILDER_LANG), 'get');
        $ret =  \html_writer::div( $this->render($deleteallbutton) ,MOD_FLUENCYBUILDER_CLASS  . '_actionbuttons');
        return $ret;
    }

    public function render_reporttitle_html($course,$username) {
        $ret = $this->output->heading(format_string($course->fullname),2);
        $ret .= $this->output->heading(get_string('reporttitle',MOD_FLUENCYBUILDER_LANG,$username),3);
        return $ret;
    }

    public function render_empty_section_html($sectiontitle) {
        return $this->output->heading(get_string('nodataavailable',MOD_FLUENCYBUILDER_LANG),3);
    }

    public function render_exportbuttons_html($cm,$formdata,$showreport){
        //convert formdata to array
        $formdata = (array) $formdata;
        $formdata['id']=$cm->id;
        $formdata['report']=$showreport;
        /*
        $formdata['format']='pdf';
        $pdf = new \single_button(
            new \moodle_url(MOD_FLUENCYBUILDER_URL . '/reports.php',$formdata),
            get_string('exportpdf',MOD_FLUENCYBUILDER_LANG), 'get');
        */
        $formdata['format']='csv';
        $excel = new \single_button(
            new \moodle_url(MOD_FLUENCYBUILDER_URL . '/reports.php',$formdata),
            get_string('exportexcel',MOD_FLUENCYBUILDER_LANG), 'get');

        return \html_writer::div( $this->render($excel),MOD_FLUENCYBUILDER_CLASS  . '_actionbuttons');
    }



    public function render_section_csv($sectiontitle, $report, $head, $rows, $fields) {

        // Use the sectiontitle as the file name. Clean it and change any non-filename characters to '_'.
        $name = clean_param($sectiontitle, PARAM_FILE);
        $name = preg_replace("/[^A-Z0-9]+/i", "_", trim($name));
        $quote = '"';
        $delim= ",";//"\t";
        $newline = "\r\n";

        header("Content-Disposition: attachment; filename=$name.csv");
        header("Content-Type: text/comma-separated-values");

        //echo header
        $heading="";
        foreach($head as $headfield){
            $heading .= $quote . $headfield . $quote . $delim ;
        }
        echo $heading. $newline;

        //echo data rows
        foreach ($rows as $row) {
            $datarow = "";
            foreach($fields as $field){
                $datarow .= $quote . $row->{$field} . $quote . $delim ;
            }
            echo $datarow . $newline;
        }
        exit();
    }

    public function render_section_html($sectiontitle, $report, $head, $rows, $fields) {
        global $CFG;
        if(empty($rows)){
            return $this->render_empty_section_html($sectiontitle);
        }

        //set up our table and head attributes
        $tableattributes = array('class'=>'generaltable '. MOD_FLUENCYBUILDER_CLASS .'_table');
        $headrow_attributes = array('class'=>MOD_FLUENCYBUILDER_CLASS . '_headrow');

        $htmltable = new \html_table();
        $htmltable->attributes = $tableattributes;


        $htr = new \html_table_row();
        $htr->attributes = $headrow_attributes;
        foreach($head as $headcell){
            $htr->cells[]=new \html_table_cell($headcell);
        }
        $htmltable->data[]=$htr;

        foreach($rows as $row){
            $htr = new \html_table_row();
            //set up descrption cell
            $cells = array();
            foreach($fields as $field){
                $cell = new \html_table_cell($row->{$field});
                $cell->attributes= array('class'=>MOD_FLUENCYBUILDER_CLASS . '_cell_' . $report . '_' . $field);
                $htr->cells[] = $cell;
            }

            $htmltable->data[]=$htr;
        }
        $html = $this->output->heading($sectiontitle, 4);
        $html .= \html_writer::table($htmltable);
        return $html;

    }

    function show_reports_footer($moduleinstance,$cm,$formdata,$showreport){
        // print's a popup link to your custom page
        $link = new \moodle_url(MOD_FLUENCYBUILDER_URL . '/reports.php',array('report'=>'menu','id'=>$cm->id,'n'=>$moduleinstance->id));
        $ret =  \html_writer::link($link, get_string('returntoreports',MOD_FLUENCYBUILDER_LANG));
        $ret .= $this->render_exportbuttons_html($cm,$formdata,$showreport);
        return $ret;
    }

}