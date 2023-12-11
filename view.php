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
require_once($CFG->dirroot.'/mod/englishcentral/lib.php');

use \mod_englishcentral\constants;
use \mod_englishcentral\mobile_auth;

$id = optional_param('id', 0, PARAM_INT); // course_module ID
$ecid = optional_param('ecid', 0, PARAM_INT);  // englishcentral instance ID
$embed = optional_param('embed', 0, PARAM_INT); // course_module ID, or

// Allow login through an authentication token.
$userid = optional_param('user_id', null, PARAM_ALPHANUMEXT);
$secret  = optional_param('secret', null, PARAM_RAW);
//formerly had !isloggedin() check, but we want tologin afresh on each embedded access
if(!empty($userid) && !empty($secret) ) {
    if (mobile_auth::has_valid_token($userid, $secret)) {
        $user = get_complete_user_data('id', $userid);
        complete_user_login($user);
        $embed = 2;
    }
}
$ismobile=$embed==2;

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

// Trigger module viewed event.
$event = \mod_englishcentral\event\course_module_viewed::create(array(
   'objectid' => $instance->id,
   'context' => $context
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('englishcentral', $instance);
$event->trigger();

//if we got this far, we can consider the activity "viewed"
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

/// Set up the page header
$PAGE->set_url('/mod/englishcentral/view.php', array('id' => $cm->id));
$PAGE->set_context($context);

$config = get_config(constants::M_COMPONENT);
if($config->enablesetuptab|| $embed==2){
    $PAGE->set_pagelayout('popup');
    $PAGE->add_body_class('poodll-ec-embed');
    $hidetabs=true;
}else{
    $PAGE->set_pagelayout('course');
    $hidetabs=false;
}

// Add standard JS keep the session alive (Moodle >= 2.9).
\core\session\manager::keepalive();

$ec = \mod_englishcentral\activity::create($instance, $cm, $course, $context);
$auth = \mod_englishcentral\auth::create($ec);

$renderer = $PAGE->get_renderer($ec->plugin);
$renderer->attach_activity_and_auth($ec, $auth);

echo $renderer->header($ec->get_string('view'),$hidetabs);

// Check that either EC config exists
// or Poodll config exists and is valid
if ($msg = $auth->missing_config()) {
    if ($msg = $auth->missing_poodllapi_config()) {
        echo $renderer->show_missingconfig($msg);
        die;
    }
    if ($msg = $auth->invalid_poodllapi_config()) {
        echo $renderer->show_invalidconfig($msg);
        die;
    }
}

if ($msg = $auth->invalid_config()) {
    echo $renderer->show_invalidconfig($msg);
    die;
}

if ($ec->not_available()) {
    echo $renderer->show_notavailable();
    die;
}
//instructions /intro if less then Moodle 4.0 show
if($CFG->version<2022041900) {
    echo $renderer->show_intro();
}

//echo $renderer->show_dates_available();

// Because of the limit on the number of options passed,
// more options are passed via "getoptions" in view.ajax.php
$opts = array('cmid'          => $ec->cm->id,
              'moodlesesskey' => sesskey(),
              'viewajaxurl'   => $ec->get_viewajax_url(false),
              'videoinfourl'  => $ec->get_videoinfo_url(false),
              'targetwindow'  => 'EC');
$PAGE->requires->js_call_amd("$ec->plugin/view", 'init', array($opts));

// Displays the student's learning progress charts
if($config->progressdials == constants::M_PROGRESSDIALS_TOP && !$ismobile) {
    echo $renderer->show_progress();
}

if ($ec->viewable) {
    /*
    $firstthumbnail = 'https://cdna.englishcentral.com/dialogs/12320/thumb_99214_20120928134621.jpg';
    echo $renderer->show_player($firstthumbnail);
    */
    if($ec->get_videoids()){
        $hidden=false;
    } else{
        $hidden=true;
    }
    echo $renderer->show_player($hidden);
    echo $renderer->show_videos();
    echo $renderer->show_search();
} else {
    echo $renderer->show_notviewable($ec);
}

// Displays the student's learning progress charts
if($config->progressdials == constants::M_PROGRESSDIALS_BOTTOM || $ismobile) {
    echo $renderer->show_progress();
}
echo $renderer->footer();
