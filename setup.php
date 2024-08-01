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
 * Setup Tab for Poodll englishcentral
 *
 * @package    mod_englishcentral
 * @copyright  2020 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/course/modlib.php');
require_once($CFG->dirroot.'/mod/englishcentral/mod_form.php');

use mod_englishcentral\constants;
use mod_englishcentral\utils;

global $DB;

// Course module ID.
$id = optional_param('id',0, PARAM_INT); // course_module ID, or
$n = optional_param('n', 0, PARAM_INT); // EnglishCentral instance ID

// Course and course module data.
if ($id) {
    $cm = get_coursemodule_from_id(constants::M_MODNAME, $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $instance = $DB->get_record(constants::M_TABLE, array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $instance = $DB->get_record(constants::M_MODNAME, array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $instance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance(constants::M_TABLE, $instance->id, $course->id, false, MUST_EXIST);
    $id = $cm->id;
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

$context = context_module::instance($cm->id);
require_capability('mod/englishcentral:manage', $context);

// Set page login data.
$PAGE->set_url(constants::M_URL . '/setup.php',array('id' => $id));
require_login($course, true, $cm);

// Set page meta data.
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

//Page layout if setup is enabled is always popup
$PAGE->set_pagelayout('popup');

// Render template and display page.
$renderer = $PAGE->get_renderer(constants::M_COMPONENT);

$mform = new \mod_englishcentral\setupform(null, [
    'context' => $context,
    'instance' => $instance,
    'cm' => $cm,
    'course' => $course,
]);

$redirecturl = new moodle_url('/mod/englishcentral/view.php', array('id' => $cm->id));

// If the cancel button was pressed, we go back to the main view page.
if ($mform->is_cancelled()) {
    redirect($redirecturl);
    exit;
}

// If any form data was sent we process that now.
if ($data = $mform->get_data()) {
    $data->id = $data->n;
    $data->timemodified = time();

    //now update the db once we have saved files and stuff
    if ($DB->update_record(constants::M_TABLE, $data)) {
        redirect($redirecturl);
        exit;
    }
}

// If we got here we are loading up data form.
$instance->n = $instance->id;
$mform->set_data((array)$instance);

echo $renderer->header(get_string('setup', constants::M_COMPONENT), 'setup');
$mform->display();
echo $renderer->footer();
