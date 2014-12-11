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

/**
 * Prints Reports for MAJHUB
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    local_majhub
 * @copyright  2014 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(__FILE__).'/reportclasses.php');


//$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
//$n  = optional_param('n', 0, PARAM_INT);  // tquiz instance ID - it should be named as the first character of the module
$format = optional_param('format', 'html', PARAM_TEXT); //export format csv or html
$showreport = optional_param('report', 'menu', PARAM_TEXT); // report type
//$questionid = optional_param('questionid', 0, PARAM_INT); // report type
//$userid = optional_param('userid', 0, PARAM_INT); // report type
//$attemptid = optional_param('attemptid', 0, PARAM_INT); // report type

/*
if ($id) {
    $cm         = get_coursemodule_from_id('local_majhub', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $tquiz  = $DB->get_record('local_majhub', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $tquiz  = $DB->get_record('local_majhub', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $tquiz->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('local_majhub', $tquiz->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}
*/
require_login();
//$modulecontext = context_module::instance($cm->id);
$modulecontext = context_system::instance();

/// Set up the page header
$PAGE->set_url('/local/majhub/admin/reports.php');
//$PAGE->set_title(format_string($tquiz->name));
//$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

//This puts all our display logic into the renderer.php files in this plugin
$reportrenderer = $PAGE->get_renderer('local_majhub','report');

//From here we actually display the page.
//this is core renderer stuff
$mode = "reports";

switch ($showreport){

	
	case 'allusers':
		$report = new local_majhub_allusers_report();
		$formdata = new stdClass();
		break;	
	case 'points':
		$report = new local_majhub_points_report();
		$formdata = new stdClass();
		break;	
	case 'menu':
	default:
		echo $reportrenderer->header();
		echo $reportrenderer->render_reportmenu();
		// Finish the page
		echo $reportrenderer->footer();
		return;

	/*	
	default:
		echo $renderer->header($tquiz, $cm, $mode, null, get_string('reports', 'local_majhub'));
		echo "unknown report type.";
		echo $renderer->footer();
		return;
	*/
}

/*
1) load the class
2) call report->process_raw_data
3) call $rows=report->fetch_formatted_records($withlinks=true(html) false(print/excel))
5) call $reportrenderer->render_section_html($sectiontitle, $report->name, $report->get_head, $rows, $report->fields);
*/

$report->process_raw_data($formdata);
$reportheading = $report->fetch_formatted_heading();

switch($format){
	case 'csv':
		$reportrows = $report->fetch_formatted_rows(false);
		$reportrenderer->render_section_csv($reportheading, $report->fetch_name(), $report->fetch_head(), $reportrows, $report->fetch_fields());
		exit;
	default:
		
		$reportrows = $report->fetch_formatted_rows(true);
		echo $reportrenderer->header();
		echo $reportrenderer->render_section_html($reportheading, $report->fetch_name(), $report->fetch_head(), $reportrows, $report->fetch_fields());
		echo $reportrenderer->show_reports_footer($formdata,$showreport);
		echo $reportrenderer->footer();
}