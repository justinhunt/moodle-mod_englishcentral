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
 * @package    mod_englishcentral
 * @copyright  2018 Gordon Bateson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

/** Include required files */
require_once('../../config.php');

// check we are a valid user
require_sesskey();

// get expected input params
$id = optional_param('id', 0, PARAM_INT); // course_modules id
$data = optional_param('data', '', PARAM_RAW);
$action = optional_param('action', '', PARAM_ALPHA);

// extract key records from DB
$cm = get_coursemodule_from_id('englishcentral', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$instance = $DB->get_record('englishcentral', array('id' => $cm->instance), '*', MUST_EXIST);

// check we are logged in
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// initialize EC activity/auth objects
$ec = \mod_englishcentral\activity::create($instance, $cm, $course, $context);
$auth = \mod_englishcentral\auth::create($ec);

// check we have suitable capability
if ($action=='storeresults') {
    $ec->req('view'); // student
} else {
    $ec->req('manage'); // teacher
}

switch ($action) {

    case 'addvideo':
        if (is_array($data) && array_key_exists('dialogId', $data)) {
            $data = (object)$data;
            $table = 'englishcentral_videos';
            $record = array('ecid' => $ec->id,
                            'videoid' => intval($data->dialogId));
            if ($record['id'] = $DB->get_field($table, 'id', $record)) {
                // video is already in our database - unexpected !!
            } else {
                unset($record['id']);
                $record['id'] = $DB->insert_record($table, $record);
            }

            $ec = \mod_englishcentral\activity::create($instance, $cm, $course, $context);
            $auth = \mod_englishcentral\auth::create($ec);

            $renderer = $PAGE->get_renderer($ec->plugin);
            $renderer->attach_activity_and_auth($ec, $auth);
            echo $renderer->show_video($data);
        }
        break;
}