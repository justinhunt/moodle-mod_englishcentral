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
$ecresult = optional_param('ecresult', '', PARAM_RAW); // JSON Data relayed by mod from EC

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

//init a few vars
$result = false;
$message ='';
$updatetime = time();

//turn json data into php assoc array

$ec_data = json_decode($ecresult,true);
//error_log(print_r($ec_data,true));
if(!$ec_data){
	$message = 'failed to decode json';
	$return =array('success'=>false,'message'=>$message);
	return;
}

	
	//flag the current attempt, by resetting old attempts to 0 (and current attempt to 1)
	$wheresql = "englishcentralid=? AND userid=?";
	$params   = array($englishcentral->id, $USER->id);
	$DB->set_field_select('englishcentral_attempt', 'status',0, $wheresql, $params);

	//create a new attempt
	$attempt = new stdClass();
	$attempt->status=1;//This is the current, ie most recent, attempt
	$attempt->englishcentralid=$englishcentral->id;
	$attempt->userid=$USER->id;
	$attempt->linestotal=$ec_data['linesTotal'];
	$attempt->totalactivetime=$ec_data['totalActiveTime'];
	$attempt->watchedcomplete=$ec_data['watchedComplete'];
	$attempt->activetime=$ec_data['activeTime'];
	$attempt->datecompleted=$updatetime;//$ec_data['dateCompleted'];
	$attempt->linesrecorded=$ec_data['linesRecorded'];
	$attempt->lineswatched=$ec_data['linesWatched'];
	$attempt->points=$ec_data['points'];
	$attempt->recordingcomplete=$ec_data['recordingComplete'];
	$attempt->sessiongrade=$ec_data['sessionGrade'];
	$attempt->sessionscore=(100* $ec_data['sessionScore']);
	$attempt->videoid=$ec_data['videoid'];
	$attempt->timecreated=$updatetime;
	$attemptid = $DB->insert_record('englishcentral_attempt',$attempt,true);
	if($attemptid){
		$attempt->id = $attemptid;
	}else{
		$attempt =false;
		$message = 'failed to write attempt to db';
	}

if($attempt && $attempt->status==1){
	//add the phonemes
	$phonemes = json_decode($ec_data['phonemesCount'],true);
	//error_log(print_r($phonemes,true));
	$result=true;
	foreach($phonemes['phonemes'] as $sound=>$phoneme){
		$phobj = new stdClass();
		$phobj->attemptid = $attempt->id;
		$phobj->englishcentralid = $attempt->englishcentralid;
		$phobj->userid = $attempt->userid;
		$phobj->phoneme = $sound;
		$phobj->badcount = $phoneme['badCount'];
		$phobj->goodcount = $phoneme['goodCount'];
		$phobj->timecreated = $updatetime;
		$result = $DB->insert_record('englishcentral_phs', $phobj,true);
		if(!$result){
			$message = 'failed to write phenome data to db';
			break;
		}
	}
}
//update the gradebook
if($attempt){
	englishcentral_update_grades($englishcentral, $attempt->userid);
}

//return a success/failure flag to browser
if($attempt && $result){
	$message= "allgood";
	$return =array('success'=>true,'message'=>$message);
	echo json_encode($return);
}else{
	$return =array('success'=>false,'message'=>$message);
	echo json_encode($return);
}