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
 * 
 *
 *
 * @package    mod_englishcentral
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');


$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$ec_action = optional_param('ecaction', '', PARAM_RAW); //what to do
$action_data = optional_param('actiondata', '', PARAM_RAW); // JSON Data relayed by mod from EC

//call so that we know we are who we said we are
require_sesskey();

if ($id) {
    $cm         = get_coursemodule_from_id('englishcentral', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $englishcentral  = $DB->get_record('englishcentral', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

global $DB,$USER;

$ec = new \mod_englishcentral\englishcentral('test');

$actiondata=json_decode($action_data);
$ret='';
switch($ec_action){

    case 'dialogprogress':
        $actionurl = 'https://reportcard.' . $ec->domain . '/rest/report/dialog/' . $actiondata->dialogID . '/progress';
        $ret = $ec->doGet($actiondata->sdkToken,$actionurl, \mod_englishcentral\englishcentral::ACCEPT_V2 );

        $newformat_dd = json_decode($ret);
        $oldformat_dd = translate_results($ret);

        //return a success/failure flag to browser
        $return =array('success'=>true,'message'=>$oldformat_dd);
        echo json_encode($return);
        //update attempt
        update_attempt($englishcentral,$oldformat_dd, $actiondata->dialogID);

        break;


    default:

}
return;

function translate_results($dd){
    $ret = [];
    foreach($dd->activities as $activity){
       switch($activity->activityTypeID){
           case \mod_englishcentral\englishcentral::ACTIVITYTYPE_LEARNING:
               $ret['learnComplete']= $activity->completed;
               break;
           case \mod_englishcentral\englishcentral::ACTIVITYTYPE_WATCHING:
               $ret['watchedComplete']= $activity->completed;
               $ret['linesWatched'] = count($activity->watchedDialogLines);
               break;
           case \mod_englishcentral\englishcentral::ACTIVITYTYPE_SPEAKING:
               $ret['recordingComplete']= $activity->completed;
               $ret['linesRecorded'] = count($activity->spokenDialogLines);
               $ret['linesTotal'] = $ret['linesRecorded'];
               $ret['sessionScore'] = $activity->score;
               $ret['sessionGrade'] = $activity->grade;
               break;
       }
        $ret['totalActiveTime'] = 0;
        $ret['activeTime'] = 0;
        $ret['totalpoints'] = $activity->totalPoints;
    }
    return $ret;
}

function update_attempt($englishcentral, $dd, $dialogID){
    global $DB, $USER;

    $updatetime = time();
    //flag the current attempt, by resetting old attempts to 0 (and current attempt to 1)
    $wheresql = "englishcentralid=? AND userid=?";
    $params   = array($englishcentral->id, $USER->id);
    $DB->set_field_select('englishcentral_attempt', 'status',0, $wheresql, $params);

    //create a new attempt
    $attempt = new stdClass();
    $attempt->status=1;//This is the current, ie most recent, attempt
    $attempt->englishcentralid=$englishcentral->id;
    $attempt->userid=$USER->id;
    $attempt->datecompleted=$updatetime;
    $attempt->watchedcomplete=$dd['watchedComplete'];
    $attempt->points=$dd['totalpoints'];

    //aka speaking complete
    $attempt->recordingcomplete=$dd['recordingComplete'];
    //new
    $attempt->learncomplete=$dd['learnComplete'];
    $attempt->videoid=$dialogID;
    $attempt->timecreated=$updatetime;
    $attempt->linestotal= $dd['linesTotal'];
    $attempt->totalactivetime=$dd['totalActiveTime'];

     $attempt->activetime=$dd['activeTime'];
    //$ec_data['dateCompleted'];
   $attempt->linesrecorded=$dd['linesRecorded'];
   $attempt->lineswatched=$dd['linesWatched'];

    $attempt->sessiongrade=$dd['sessionGrade'];
    $attempt->sessionscore=(100* $dd['sessionScore']);

    $attemptid = $DB->insert_record('englishcentral_attempt',$attempt,true);
    if($attemptid){
        $attempt->id = $attemptid;
    }else{
        $attempt =false;
    }


//update the gradebook
    if($attempt){
        englishcentral_update_grades($englishcentral, $attempt->userid);
    }

    return;

}