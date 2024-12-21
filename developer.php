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
 * Developer tools for Poodll EnglishCentral
 *
 *
 * @package    mod_englishcentral
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

use mod_englishcentral\constants;
use mod_englishcentral\utils;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // solo instance ID

$action = optional_param('action', 'none', PARAM_TEXT); // report type




if ($id) {
    $cm         = get_coursemodule_from_id(constants::M_MODNAME, $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance  = $DB->get_record(constants::M_TABLE, ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($n) {
    $moduleinstance  = $DB->get_record(constants::M_TABLE, ['id' => $n], '*', MUST_EXIST);
    $course     = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance(constants::M_TABLE, $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(0, 'You must specify a course_module ID or an instance ID');
}

$PAGE->set_url(constants::M_URL . '/developer.php',
    ['id' => $cm->id, 'action' => $action]);
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

require_capability('mod/englishcentral:viewreports', $modulecontext);

// Get the admin settings.
$config = get_config(constants::M_COMPONENT);

/// Set up the page header
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('incourse');
$PAGE->requires->jquery();

// Set up renderer and activity
$ec = \mod_englishcentral\activity::create($moduleinstance, $cm, $course, $modulecontext);
$auth = \mod_englishcentral\auth::create($ec);
$renderer = $PAGE->get_renderer($ec->plugin);
$renderer->attach_activity_and_auth($ec, $auth);

// Process Actions.
switch ($action){

    case 'updategrades':
        englishcentral_update_grades($moduleinstance);
        redirect(new \moodle_url(constants::M_URL . '/developer.php',
            ['id' => $cm->id]), 'Grades Updated', 5);
               break;

    // not a true report, separate implementation in renderer
    case 'generatedata':
        $completeattempts = $DB->get_records(constants::M_ATTEMPTSTABLE,
            ['ecid' => $moduleinstance->id, 'watchcomplete' => 1, 'learncomplete' => 1, 'speakcomplete' => 1, 'chatcomplete' => 1],
                'timecompleted DESC', '*', 0, 1);
           $videos = $DB->get_fieldset(constants::M_VIDEOSTABLE, 'id', ['ecid' => $moduleinstance->id]);

        if(!$completeattempts) {
               echo $renderer->header(get_string('developertools', constants::M_COMPONENT));
               echo '<h3>No attempt to generate data from. Please create a compelete attempt at the activity.</h3>';
               echo $renderer->footer();
               return;
        } else if(!$videos || count($videos) < 1){
            echo $renderer->header(get_string('developertools', constants::M_COMPONENT));
            echo '<h3>Activity contains no videos. Please add some videos.</h3>';
            echo $renderer->footer();
            return;
        }else{
            $latestattempt = array_shift($completeattempts);
            $users = get_enrolled_users($modulecontext);
            // reindex array
            $users = array_values($users);
            $created = 0;
            for($x = 0; $x < count($users); $x++){
                $ouruser = $users[$x];
                foreach ($videos as $videoid){
                    // randomly skip some
                    if(random_int(0, 2) == 0){continue;
                    }
                    $ret = copyAttempt($videoid, $latestattempt, $ouruser);
                    if($ret){$created++;
                    }
                }//end of video loop
            }//end of user loop
            redirect(new \moodle_url(constants::M_URL . '/developer.php',
            ['id' => $cm->id]), 'Created Attempts:' . $created);

        }

        return;

    case 'none':
    default:
}

// output the page
$header = $renderer->header(get_string('developertools', constants::M_COMPONENT));
echo $header;
$items = $renderer->developerpage($cm->id, $moduleinstance->id);
foreach($items as $item){
    echo $item;
}
echo $renderer->footer();

function copyattempt($videoid, $attempt, $user) {
    global $DB;
    $newatt = clone $attempt;

    // attempt
    $newatt->id = null;
    $newatt->timecompleted = time();
    $newatt->videoid = $videoid;
    // counts
    $newatt->watchcount = random_int(1, (int) $newatt->watchcount);
    $newatt->learncount = random_int(1, (int) $newatt->learncount);
    $newatt->speakcount = random_int(1, (int) $newatt->speakcount);
    $newatt->chatcount = random_int(1, (int) $newatt->chatcount);
    // line ids
    $newatt->watchlineids = implode(',', (array_slice(explode(',', $newatt->watchlineids), 0, $newatt->watchcount)));
    $newatt->learnwordids = implode(',', (array_slice(explode(',', $newatt->learnwordids), 0, $newatt->learncount)));
    $newatt->speaklineids = implode(',', (array_slice(explode(',', $newatt->speaklineids), 0, $newatt->speakcount)));
    $newatt->chatquestionids = implode(',', (array_slice(explode(',', $newatt->chatquestionids), 0, $newatt->chatcount)));
    // points total
    $newatt->totalpoints = $newatt->watchcount + $newatt->learncount + $newatt->speakcount + $newatt->chatcount;

    if($user->id !== $newatt->userid ){
        $newatt->userid = $user->id;
        $attemptid = $DB->insert_record(constants::M_ATTEMPTSTABLE, $newatt);
        if(!$attemptid){return false;
        }
    }

    return true;
}
