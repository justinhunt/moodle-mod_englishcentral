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
 * Prints a particular instance of englishcentral
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_englishcentral
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/englishcentral/lib.php');;

$id = optional_param('id', 0, PARAM_INT); // course_module ID
$ecid = optional_param('ecid', 0, PARAM_INT);  // englishcentral instance ID
$mode = optional_param('mode', '', PARAM_ALPHA);

if ($id) {
    $cm = get_coursemodule_from_id('englishcentral', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $instance = $DB->get_record('englishcentral', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($ecid) {
    $instance = $DB->get_record('englishcentral', array('id' => $ecid), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $instance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('englishcentral', $instance->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Trigger report viewed event.
$event = \mod_englishcentral\event\report_viewed::create(array(
    'context' => $context,
    'other' => array('ecid' => $instance->id, 'mode' => $mode)
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('englishcentral', $instance);
$event->trigger();

/// Set up the page header
$params = array('id' => $cm->id);
if ($mode) {
    $params['mode'] = $mode;
}
$PAGE->set_url('/mod/englishcentral/report.php', $params);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');

$ec = \mod_englishcentral\activity::create($instance, $cm, $course, $context);
$auth = \mod_englishcentral\auth::create($ec);

$PAGE->requires->js_call_amd("$ec->plugin/report", 'init');

$renderer = $PAGE->get_renderer($ec->plugin);
$renderer->attach_activity_and_auth($ec, $auth);

echo $renderer->header(get_string('report'));
echo $renderer->show_progress_report();
echo $renderer->footer();
