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
if ($action=='storeresults' || $action=='showstatus') {
    $ec->req('view'); // student
} else {
    $ec->req('manage'); // teacher
}

// initialize the renderer
$renderer = $PAGE->get_renderer($ec->plugin);
$renderer->attach_activity_and_auth($ec, $auth);

switch ($action) {

    case 'storeresults':
        if (is_array($data) && array_key_exists('dialogID', $data)) {
            $data = (object)$data;
            $dialog = $auth->fetch_dialog_progress($data->dialogID, $data->sdk_token);
            $ec->update_progress($dialog);
            echo $renderer->show_progress();
        }
        break;

    case 'showstatus':
        if (is_array($data) && array_key_exists('dialogID', $data)) {
            $data = (object)$data;
            $attempts = $ec->get_attempts($data->dialogID);
            if ($video = reset($attempts)) {
                echo $renderer->show_video_status($video);
            }
        }
        break;

    case 'addvideo':
        if (is_array($data) && array_key_exists('dialogId', $data)) {
            $data = (object)$data;
            $table = 'englishcentral_videos';
            $record = array('ecid' => $ec->id,
                            'videoid' => intval($data->dialogId));
            if ($record['id'] = $DB->get_field($table, 'id', $record)) {
                // video is already in our database - unexpected !!
            } else {
                if ($sortorder = $DB->get_field($table, 'MAX(sortorder)', array('ecid' => $ec->id))) {
                    $sortorder++;
                } else {
                    $sortorder = 1;
                }
                unset($record['id']);
                $record['sortorder'] = $sortorder;
                $record['id'] = $DB->insert_record($table, $record);
            }
            echo $renderer->show_video($data);
        }
        break;

    case 'sortvideo':

        if (is_array($data) && array_key_exists('dialogId', $data) && array_key_exists('sortorder', $data)) {

            // sanity check on incoming values
            $data = (object)$data;
            $targetvideoid = intval($data->dialogId);
            $targetsortorder = intval($data->sortorder);

            if ($targetvideoid && $targetsortorder) {

                // define DB table name
                $table = 'englishcentral_videos';

                // set all sort orders to negative
                // we need to do this because the DB index requies unique (ecid, sortorder)
                $params = array('ecid' => $ec->id);
                $DB->execute('UPDATE {'.$table.'} SET sortorder = -sortorder WHERE ecid = :ecid', $params);

                if ($videos = $DB->get_records($table, $params, 'sortorder DESC')) {
                    $sortorder = 1;
                    foreach ($videos as $video) {
                        $params = array('id' => $video->id);
                        if (intval($video->videoid)==$targetvideoid) {
                            $DB->set_field($table, 'sortorder', $targetsortorder, $params);
                        } else if ($sortorder >= $targetsortorder) {
                            $DB->set_field($table, 'sortorder', $sortorder + 1, $params);
                            $sortorder++;
                        } else {
                            $DB->set_field($table, 'sortorder', $sortorder, $params);
                            $sortorder++;
                        }
                    }
                }
            }
            // there's nothing to return because the element has been dragged
            // to the correct position in the browser
        }
        break;

    case 'removevideo':
        if (is_array($data) && array_key_exists('dialogId', $data)) {
            $data = (object)$data;
            $table = 'englishcentral_videos';
            $DB->delete_records($table, array('ecid' => $ec->id,'videoid' => intval($data->dialogId)));
        }
        break;
}