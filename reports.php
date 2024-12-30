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
 * Reports for englishcentral
 *
 *
 * @package    mod_englishcentral
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

use mod_englishcentral\constants;
use mod_englishcentral\utils;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n = optional_param('n', 0, PARAM_INT);  // englishcentral instance ID
$format = optional_param('format', 'combined', PARAM_TEXT); // export format csv/tabular/graphical/combined
$showreport = optional_param('report', 'menu', PARAM_TEXT); // report type
$userid = optional_param('userid', 0, PARAM_INT); // user id
$dayslimit = optional_param('dayslimit', 0, PARAM_INT); // no. of days data to show

// paging details
$paging = new stdClass();
$paging->perpage = optional_param('perpage', -1, PARAM_INT);
$paging->pageno = optional_param('pageno', 0, PARAM_INT);
$paging->sort = optional_param('sort', 'iddsc', PARAM_TEXT);

if ($id) {
    $cm = get_coursemodule_from_id(constants::M_MODNAME, $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record(constants::M_TABLE, ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($n) {
    $moduleinstance = $DB->get_record(constants::M_TABLE, ['id' => $n], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance(constants::M_TABLE, $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

$PAGE->set_url(constants::M_URL . '/reports.php',
        ['id' => $cm->id, 'report' => $showreport, 'format' => $format,
             'userid' => $userid, 'dayslimit' => $dayslimit]);
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

require_capability('mod/englishcentral:viewreports', $modulecontext);

// Get an admin settings
$config = get_config(constants::M_COMPONENT);

// set per page according to admin setting
if ($config->reportstable == constants::M_USE_DATATABLES) {
    $paging = false;
} else if ($paging->perpage == -1) {
    $paging->perpage = 20; //$config->attemptsperpage;
}


// Trigger module viewed event.
$event = \mod_englishcentral\event\course_module_viewed::create([
        'objectid' => $moduleinstance->id,
        'context' => $modulecontext,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot(constants::M_MODNAME, $moduleinstance);
$event->trigger();

/// Set up the page header
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

if ($config->enablesetuptab) {
    $PAGE->set_pagelayout('popup');
} else {
    $PAGE->set_pagelayout('incourse');
}

$PAGE->requires->jquery();

// This puts all our display logic into the renderer.php files in this plugin
$ec = \mod_englishcentral\activity::create($moduleinstance, $cm, $course, $modulecontext);
$auth = \mod_englishcentral\auth::create($ec);
$renderer = $PAGE->get_renderer(constants::M_COMPONENT);
$renderer->attach_activity_and_auth($ec, $auth);
$reportrenderer = $PAGE->get_renderer(constants::M_COMPONENT, 'report');

// From here we actually display the page.
// this is core renderer stuff
$mode = "reports";
$extraheader = "";
switch ($showreport) {

    // not a true report, separate implementation in renderer
    case 'menu':
        echo $renderer->header(get_string('reports', constants::M_COMPONENT));
        echo $reportrenderer->show_user_report_options($PAGE->url, $dayslimit, $format);
        echo $reportrenderer->render_reportmenu($moduleinstance, $cm, $dayslimit, $format);
        // Finish the page
        echo $renderer->footer();
        return;

    case 'basic':
        $report = new \mod_englishcentral\report\basic($cm);
        //formdata should only have simple values, not objects
        //later it gets turned into urls for the export buttons
        $formdata = new stdClass();
        break;

    case 'attemptssummary':
        $report = new \mod_englishcentral\report\attemptssummary($cm);
        $formdata = new stdClass();
        $formdata->ecid = $moduleinstance->id;
        $formdata->modulecontextid = $modulecontext->id;
        $formdata->groupmenu = true;
        $formdata->dayslimit = $dayslimit;
        $formdata->format = $format;
        break;

    case 'attempts':
        $report = new \mod_englishcentral\report\attempts($cm);
        $formdata = new stdClass();
        $formdata->ecid = $moduleinstance->id;
        $formdata->modulecontextid = $modulecontext->id;
        $formdata->groupmenu = true;
        $formdata->dayslimit = $dayslimit;
        $formdata->format = $format;
        break;

    case 'userattempts':
        $report = new \mod_englishcentral\report\userattempts($cm);
        $formdata = new stdClass();
        $formdata->ecid = $moduleinstance->id;
        $formdata->userid = $userid;
        $formdata->modulecontextid = $modulecontext->id;
        $formdata->dayslimit = $dayslimit;
        $formdata->format = $format;
        break;

    case 'videoperformance':
        $report = new \mod_englishcentral\report\videoperformance($cm);
        $formdata = new stdClass();
        $formdata->ecid = $moduleinstance->id;
        $formdata->courseid = $moduleinstance->course;
        $formdata->modulecontextid = $modulecontext->id;
        $formdata->dayslimit = $dayslimit;
        $formdata->format = $format;
        break;

    case 'courseattempts':
        $report = new \mod_englishcentral\report\courseattempts($cm);
        $formdata = new stdClass();
        $formdata->course = $moduleinstance->course;
        $formdata->modulecontextid = $modulecontext->id;
        $formdata->groupmenu = true;
        $formdata->dayslimit = $dayslimit;
        $formdata->format = $format;
        break;

    case 'usercourseattempts':
        $report = new \mod_englishcentral\report\usercourseattempts($cm);
        $formdata = new stdClass();
        $formdata->course = $moduleinstance->course;
        $formdata->modulecontextid = $modulecontext->id;
        $formdata->userid = $userid;
        $formdata->dayslimit = $dayslimit;
        $formdata->format = $format;
        break;

    default:
        echo $renderer->header(get_string('reports', constants::M_COMPONENT));
        echo "unknown report type.";
        echo $renderer->footer();
        return;
}

/*
1) load the class
2) call report->process_raw_data
3) call $rows=report->fetch_formatted_records($withlinks=true(html) false(print/excel))
5) call $reportrenderer->render_report_tabular($sectiontitle, $report->name, $report->get_head, $rows, $report->fields);
*/

$groupmenu = '';
if (isset($formdata->groupmenu)) {
    // Fetch groupmode/menu/id for this activity.
    if ($groupmode = groups_get_activity_groupmode($cm)) {
        $groupmenu = groups_print_activity_menu($cm, $PAGE->url, true);
        $groupmenu .= ' ';
        $formdata->groupid = groups_get_activity_group($cm);
    } else {
        $formdata->groupid  = 0;
    }
} else {
    $formdata->groupid  = 0;
}

$report->process_raw_data($formdata);
$reportheading = $report->fetch_formatted_heading();
$reportdescription = $report->fetch_formatted_description();

switch ($format) {
    case 'csv':
        $reportrows = $report->fetch_formatted_rows(false);
        $reportrenderer->render_report_csv($reportheading, $report->fetch_name(), $report->fetch_head(), $reportrows,
                $report->fetch_fields());
        exit;

    case 'graphical':
        // its too unprdictable to set these colors
        // $CFG->chart_colorset = ['#ceb9df', '#a9dbef', '#f7c1a1', '#d3e9af', '#a7d2e8', '#e3b9d9', '#f2d7a4', '#c7d9a3'];
        echo $renderer->header(get_string('reports', constants::M_COMPONENT));
        echo $reportrenderer->show_user_report_options($PAGE->url, $dayslimit, $format);
        echo $extraheader;
        echo $groupmenu;
        echo $reportrenderer->heading($reportheading, 4, 'clearfix');
        echo $reportrenderer->heading($reportdescription, 5);
        echo $report->fetch_chart($reportrenderer, true);
        echo $reportrenderer->show_reports_footer($moduleinstance, $cm, $formdata, $showreport);
        echo $renderer->footer();
        break;

    case 'tabular':
    case 'combined':
    default:

        $reportrows = $report->fetch_formatted_rows(true, $paging);
        $allrowscount = $report->fetch_all_rows_count();
        if ($config->reportstable == constants::M_USE_DATATABLES) {

            // css must be required before header sent out
            $PAGE->requires->css( new \moodle_url('https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css'));
            echo $renderer->header(get_string('reports', constants::M_COMPONENT));
            echo $reportrenderer->show_user_report_options($PAGE->url, $dayslimit, $format);
            echo $extraheader;
            echo $groupmenu;
            echo $reportrenderer->heading($reportheading, 4, 'clearfix');
            echo $reportrenderer->heading($reportdescription, 5);
            // First the chart.
            if ($format == 'combined') {
                // its too unprdictable to set these colors
                //$CFG->chart_colorset = ['#ceb9df', '#a9dbef', '#f7c1a1', '#d3e9af', '#a7d2e8', '#e3b9d9', '#f2d7a4', '#c7d9a3'];
                echo $report->fetch_chart($reportrenderer, false);
            }

            // Then the table.
            echo $reportrenderer->render_report_tabular( $report->fetch_name(), $report->fetch_head(), $reportrows,
                $report->fetch_fields());

        } else {
            $pagingbar = $reportrenderer->show_paging_bar($allrowscount, $paging, $PAGE->url);
            echo $renderer->header(get_string('reports', constants::M_COMPONENT));
            echo $reportrenderer->show_user_report_options($PAGE->url, $dayslimit, $format);
            echo $extraheader;
            echo $groupmenu;
            echo $reportrenderer->heading($reportheading, 4, 'clearfix');
            echo $reportrenderer->heading($reportdescription, 5);
            // First the chart.
            if ($format == 'combined') {
                  // its too unprdictable to set these colors
                //$CFG->chart_colorset = ['#ceb9df', '#a9dbef', '#f7c1a1', '#d3e9af', '#a7d2e8', '#e3b9d9', '#f2d7a4', '#c7d9a3'];
                echo $report->fetch_chart($reportrenderer, false);
            }
            // Then the table.
            echo $pagingbar;
            echo $reportrenderer->render_report_tabular( $report->fetch_name(), $report->fetch_head(), $reportrows,
                $report->fetch_fields());
            echo $pagingbar;
        }
        echo $reportrenderer->show_reports_footer($moduleinstance, $cm, $formdata, $showreport);
        echo $renderer->footer();
}
