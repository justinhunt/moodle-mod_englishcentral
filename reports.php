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
 * Reports for English Central Mod
 *
 *
 * @package    mod_englishcentral
 * @copyright  COPYRIGHTNOTICE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/reportclasses.php');


$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$ecid  = optional_param('ecid', 0, PARAM_INT);  // englishcentral instance ID - it should be named as the first character of the module
$format = optional_param('format', 'html', PARAM_TEXT); //export format csv or html
$showreport = optional_param('report', 'menu', PARAM_TEXT); // report type
$questionid = optional_param('questionid', 0, PARAM_INT); // report type
$userid = optional_param('userid', 0, PARAM_INT); // report type
$attemptid = optional_param('attemptid', 0, PARAM_INT); // report type


if ($id) {
    $cm         = get_coursemodule_from_id('englishcentral', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $englishcentral  = $DB->get_record('englishcentral', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($ecid) {
    $englishcentral  = $DB->get_record('englishcentral', array('id' => $ecid), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $englishcentral->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('englishcentral', $englishcentral->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

//Diverge logging logic at Moodle 2.7
if($CFG->version<2014051200){
	add_to_log($course->id, 'englishcentral', 'reports', "reports.php?id={$cm->id}", $englishcentral->name, $cm->id);
}else{
	// Trigger module viewed event.
	$event = \mod_englishcentral\event\course_module_viewed::create(array(
	   'objectid' => $englishcentral->id,
	   'context' => $modulecontext
	));
	$event->add_record_snapshot('course_modules', $cm);
	$event->add_record_snapshot('course', $course);
	$event->add_record_snapshot('englishcentral', $englishcentral);
	$event->trigger();
}


/// Set up the page header
$PAGE->set_url('/mod/englishcentral/reports.php', array('id' => $cm->id));
$PAGE->set_title(format_string($englishcentral->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

	//Get an admin settings
	$config = get_config('mod_englishcentral');


//This puts all our display logic into the renderer.php files in this plugin
$renderer = $PAGE->get_renderer('mod_englishcentral');
$reportrenderer = $PAGE->get_renderer('mod_englishcentral','report');

//From here we actually display the page.
//this is core renderer stuff
$mode = "reports";
$extraheader="";
switch ($showreport){

	//not a true report, separate implementation in renderer
	case 'menu':
		//$questions = $DB->get_records('englishcentral_questions',array('englishcentral'=>$englishcentral->id));
		echo $renderer->header($englishcentral, $cm, $mode, null, get_string('reports', 'englishcentral'));
		echo $reportrenderer->render_reportmenu($englishcentral,$cm);
		// Finish the page
		echo $renderer->footer();
		return;

	case 'phonemes':
		$report = new mod_englishcentral_phonemes_report();
		$formdata = new stdClass();
		$formdata->ecid=$englishcentral->id;
		$formdata->attemptid=$attemptid;
		$formdata->userid=$userid;
		break;

	case 'allattempts':
		$report = new mod_englishcentral_allattempts_report();
		$formdata = new stdClass();
		$formdata->ecid=$englishcentral->id;
		$formdata->cmid=$cm->id;
		$extraheader = $reportrenderer->render_delete_allattempts($cm);
		break;


	case 'allusers':
		$report = new mod_englishcentral_allusers_report();
		$formdata = new stdClass();
		$formdata->ecid=$englishcentral->id;
		break;

	case 'attemptdetails':
		$report = new mod_englishcentral_attemptdetails_report();
		$formdata = new stdClass();
		$formdata->ecid=$englishcentral->id;
		$formdata->attemptid=$attemptid;
		$formdata->userid=$userid;
		break;


	default:
		echo $renderer->header($englishcentral, $cm, $mode, null, get_string('reports', 'englishcentral'));
		echo "unknown report type.";
		echo $renderer->footer();
		return;
}

/*
1) load the class
2) call report->process_raw_data
3) call $rows=report->fetch_formatted_records($withlinks=true(html) false(print/excel))
5) call $reportrenderer->render_section_html($sectiontitle, $report->name, $report->get_head, $rows, $report->fields);
*/

$report->process_raw_data($formdata, $englishcentral);
$reportheading = $report->fetch_formatted_heading();

switch($format){
	case 'csv':
		$reportrows = $report->fetch_formatted_rows(false);
		$reportrenderer->render_section_csv($reportheading, $report->fetch_name(), $report->fetch_head(), $reportrows, $report->fetch_fields());
		exit;
	default:

		$reportrows = $report->fetch_formatted_rows(true);
		echo $renderer->header($englishcentral, $cm, $mode, null, get_string('reports', 'englishcentral'));
		echo $extraheader;
		echo $reportrenderer->render_section_html($reportheading, $report->fetch_name(), $report->fetch_head(), $reportrows, $report->fetch_fields());
		echo $reportrenderer->show_reports_footer($englishcentral,$cm,$formdata,$showreport);
		echo $renderer->footer();
}