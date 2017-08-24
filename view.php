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


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // englishcentral instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('englishcentral', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $englishcentral  = $DB->get_record('englishcentral', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $englishcentral  = $DB->get_record('englishcentral', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $englishcentral->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('englishcentral', $englishcentral->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

global $USER;

//Diverge logging logic at Moodle 2.7
if($CFG->version<2014051200){ 
	add_to_log($course->id, 'englishcentral', 'view', "view.php?id={$cm->id}", $englishcentral->name, $cm->id);
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


//if we got this far, we can consider the activity "viewed"
$completion = new completion_info($course);
$completion->set_module_viewed($cm);


/// Set up the page header
$PAGE->set_url('/mod/englishcentral/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($englishcentral->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

//authenticate with English Central, and get our API ready
	$config = get_config('englishcentral');

    //change 'production' to test when developing here AND in ajaxhelper.php
    $ec = new \mod_englishcentral\englishcentral('production');
	$ec_user = $USER;
	$jwt = $ec->build_authorize_token($ec_user);

	$sdk_token = $ec->login_and_auth($jwt,$ec_user);
//print_r($sdk_token);
//die;//

//get our javascript all ready to go
$jsmodule = array(
	'name'     => 'mod_englishcentral',
	'fullpath' => '/mod/englishcentral/module.js',
	'requires' => array('io','json','button')
);
//here we set up any info we need to pass into javascript
$opts =Array();
$opts['consumerkey'] =$config->consumerkey;
$opts['cmid'] = $cm->id;
$opts['sdktoken'] =$sdk_token;
$opts['videoid'] =$englishcentral->videoid; 
$opts['watchmode'] =$englishcentral->watchmode==1;
$opts['speakmode'] =$englishcentral->speakmode==1; 
$opts['speaklitemode'] =$englishcentral->speaklitemode==1; 
$opts['learnmode'] =$englishcentral->learnmode==1; 
$opts['hiddenchallengemode'] =$englishcentral->hiddenchallengemode==1; 
$opts['lightbox'] =$englishcentral->lightboxmode==1;
$opts['simpleui'] =$englishcentral->simpleui==1;
$opts['resultsmode'] ='ajax';
$opts['playerdiv'] ='mod_englishcentral_playercontainer';
$opts['resultsdiv'] ='mod_englishcentral_resultscontainer';

//this inits the M.mod_englishcentral thingy, after the page has loaded.
//$PAGE->requires->js_init_call('M.mod_englishcentral.playerhelper.init', array($opts),false,$jsmodule);

/*
 *  20170819 Basically what we have done is to swap out init with a new function angular_init (just above)
 * And just below we swapped out the API call to ec.js to the sdk.js so we are loading a new library
 *
 * Going forward we need to load with AMD in JS and use the firebase JWT sign on system to auth with EC
 *
 */

$PAGE->requires->js_init_call('M.mod_englishcentral.playerhelper.init', array($opts),false,$jsmodule);



//this loads the strings we need into JS
$PAGE->requires->strings_for_js(array('sessionresults','sessionscore','sessiongrade','lineswatched',
						'linesrecorded','compositescore','activetime','totalactivetime'), 'englishcentral');

//this loads any external JS libraries we need to call
////$PAGE->requires->js("/mod/englishcentral/js/ec.js");
//$PAGE->requires->js(new moodle_url('https://www.englishcentral.com/platform/ec.js'),true);
$ec_js_url = $ec->fetch_js_url();
$PAGE->requires->js(new moodle_url($ec_js_url),true);

//This puts all our display logic into the renderer.php file in this plugin
//theme developers can override classes there, so it makes it customizable for others
//to do it this way.
$renderer = $PAGE->get_renderer('mod_englishcentral');

echo $renderer->header($englishcentral, $cm, 'view',null, get_string('view', 'englishcentral'));


//From here we actually display the page.
//echo "RT:" . $requesttoken . '<BR />';
//echo "AT:" . $accesstoken . '<BR />';
echo $renderer->show_intro($englishcentral, $cm);

//if we have attempts and we are not a manager/teacher then lets show a summary of them
$hasattempts=false;
//if(!has_capability('mod/englishcentral:manageattempts', $module_context)){
	$attempts = $DB->get_records('englishcentral_attempt',array('userid'=>$USER->id,'englishcentralid'=>$englishcentral->id));
	if($attempts){
		$hasattempts=true;
		echo $renderer->show_myattempts($englishcentral, $attempts);
	}
//}

// Replace the following lines with your own code
//echo $renderer->show_ec_options();
//$thumburl="http://demo.poodll.com/pluginfile.php/10/course/summary/pdclogo.jpg";
//echo $renderer->show_ec_link($englishcentral->videotitle, $thumburl, $englishcentral->videoid);
if($englishcentral->maxattempts == 0|| count($attempts)<$englishcentral->maxattempts){
	echo $renderer->show_bigbutton($hasattempts);
	echo $renderer->show_ec_box();
}else{
	echo $renderer->show_exceededattempts($englishcentral,$attempts);
}

// Finish the page
echo $renderer->footer();
