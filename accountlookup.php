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
 * Lookup a user's id
 *
 * @package mod_englishcentral;
 * @copyright  2019 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../config.php");

// get the objects we need
$id = required_param('id', PARAM_INT);  // Course Module ID
$cm = get_coursemodule_from_id('englishcentral', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$instance = $DB->get_record('englishcentral', array('id' => $cm->instance), '*', MUST_EXIST);

//make sure we are logged in and can see this form
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/englishcentral:manageattempts', $context);

// TODO: trigger event "viewed account lookup"?

//set up the page object
$PAGE->set_url('/mod/englishcentral/accountlookup.php', array('id' => $id));
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');

//get EC class
$ec = \mod_englishcentral\activity::create($instance, $cm, $course, $context);
$auth = \mod_englishcentral\auth::create($ec);

$renderer = $PAGE->get_renderer($ec->plugin);
$renderer->attach_activity_and_auth($ec, $auth);

if ($users = get_enrolled_users($context)) {
    $mform = new \mod_englishcentral\lookupform($PAGE->url->out(), array('users' => $users));
    if ($mform->is_cancelled()) {
        redirect($ec->get_view_url());
    }
}

echo $renderer->header($ec->get_string('accountlookup'));
if ($users) {
    $mform->display();
} else {
    echo $renderer->notification(get_string('nousersfound'), 'notifyproblem');
    echo $renderer->continue_button($ec->get_view_url());
}
echo $renderer->footer();