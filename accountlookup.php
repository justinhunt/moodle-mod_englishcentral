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


global $USER,$DB;

// first get the info passed in to set up the page
$id     = required_param('id', PARAM_INT);         // Course Module ID

// get the objects we need
$cm = get_coursemodule_from_id('englishcentral', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('englishcentral', array('id' => $cm->instance), '*', MUST_EXIST);

//make sure we are logged in and can see this form
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/englishcentral:manageattempts', $context);

//set up the page object
$PAGE->set_url('/mod/englishcentral/accountlookup.php', array('id'=>$id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');

//get EC class
$ec = \mod_englishcentral\activity::create($moduleinstance, $cm, $course, $context);
$auth = \mod_englishcentral\auth::create($ec);
$renderer = $PAGE->get_renderer($ec->plugin);
$renderer->attach_activity_and_auth($ec, $auth);



//fetch enrolled users
$users=get_enrolled_users($context);

echo $renderer->header($ec->get_string('account_lookup'));

$mform = new \mod_englishcentral\lookupform(null,
        array('users'=>$users));

echo $renderer->show_box_text($ec->get_string('lookup_instructions'));
$data = $mform->get_data();
$mform->set_data(array('id'=>$id));
$mform->display();

//handle user request
if($data && $users && array_key_exists($data->user,$users)) {

    $accountinfo = $DB->get_record('englishcentral_accountids', array('userid' => $data->user));
    $a = new \stdClass();
    $a->fullname = fullname($users[$data->user]);
    if ($accountinfo) {
        $a->accountid = $accountinfo->accountid;
        echo $renderer->show_box_text($ec->get_string('lookup_results',$a));
    }else{
        echo $renderer->show_box_text( $ec->get_string('lookup_empty_result',$a));
    }
}

echo $renderer->footer();