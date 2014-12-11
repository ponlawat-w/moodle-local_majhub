<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


defined('MOODLE_INTERNAL') || die();


/**
 * Renderer for tquiz reports.
 *
 * @package    mod_tquiz
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_majhub_report_renderer extends plugin_renderer_base {


	public function render_reportmenu() {
		
		$allusers = new single_button(
			new moodle_url('/local/majhub/admin/reports.php',array('report'=>'allusers')), 
			get_string('allusersreport','local_majhub'), 'get');
		$points = new single_button(
			new moodle_url('/local/majhub/admin/reports.php',array('report'=>'points')), 
			get_string('pointsreport','local_majhub'), 'get');
			
		$ret = html_writer::div($this->render($allusers) .'<br />' . $this->render($points) ,'local_majhub_listbuttons');

		return $ret;
	}


	public function render_reporttitle_html($course,$username) {
		$ret = $this->output->heading(format_string($course->fullname),2);
		$ret .= $this->output->heading(get_string('reporttitle','local_majhub',$username),3);
		return $ret;
	}

	public function render_empty_section_html($sectiontitle) {
		global $CFG;
		return $this->output->heading(get_string('nodataavailable','local_majhub'),3);
	}
	
	public function render_exportbuttons_html($formdata,$showreport){
		//convert formdata to array
		$formdata = (array) $formdata;
		$formdata['report']=$showreport;
		
		$formdata['format']='pdf';
		$pdf = new single_button(
			new moodle_url('/local/majhub/admin/reports.php',$formdata),
			get_string('exportpdf','local_majhub'), 'get');
		
		$formdata['format']='csv';
		$excel = new single_button(
			new moodle_url('/local/majhub/admin/reports.php',$formdata), 
			get_string('exportexcel','local_majhub'), 'get');

		//return html_writer::div( $this->render($pdf) . $this->render($excel),'local_majhub_actionbuttons');
		return html_writer::div( $this->render($excel),'local_majhub_actionbuttons');
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
        break;
	}

	public function render_section_html($sectiontitle, $report, $head, $rows, $fields) {
		global $CFG;
		if(empty($rows)){
			return $this->render_empty_section_html($sectiontitle);
		}
		
		//set up our table and head attributes
		$tableattributes = array('class'=>'generaltable majhub_table');
		$headrow_attributes = array('class'=>'majhub_headrow');
		
		$htmltable = new html_table();
		$htmltable->attributes = $tableattributes;
		
		
		$htr = new html_table_row();
		$htr->attributes = $headrow_attributes;
		foreach($head as $headcell){
			$htr->cells[]=new html_table_cell($headcell);
		}
		$htmltable->data[]=$htr;
		
		foreach($rows as $row){
			$htr = new html_table_row();
			//set up descrption cell
			$cells = array();
			foreach($fields as $field){
				$cell = new html_table_cell($row->{$field});
				$cell->attributes= array('class'=>'majhub_cell_' . $report . '_' . $field);
				$htr->cells[] = $cell;
			}

			$htmltable->data[]=$htr;
		}
		$html = $this->output->heading($sectiontitle, 4);
		$html .= html_writer::table($htmltable);
		return $html;
		
	}
	
	function show_reports_footer($formdata,$showreport){
		// print's a popup link to your custom page
		$link = new moodle_url('/local/majhub/admin/reports.php');
		$ret =  html_writer::link($link, get_string('returntoreports','local_majhub'));
		$ret .= $this->render_exportbuttons_html($formdata,$showreport);
		return $ret;
	}

}


