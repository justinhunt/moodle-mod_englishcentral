<?php

// This file is part of Moodle - http://moodle.org/

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants for module englishcentral
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the englishcentral specific functions, needed to implement all the module
 * logic, should go to classes/activity.php. This will save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_englishcentral
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('MOD_ENGLISHCENTRAL_GRADEHIGHEST', 0);
define('MOD_ENGLISHCENTRAL_GRADELOWEST',  1);
define('MOD_ENGLISHCENTRAL_GRADELATEST',  2);
define('MOD_ENGLISHCENTRAL_GRADEAVERAGE', 3);
define('MOD_ENGLISHCENTRAL_GRADENONE',    4);

//////////////////////////////////////////////////////////////////////////////
// Moodle core API
//////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function englishcentral_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:         return true;
        case FEATURE_SHOW_DESCRIPTION:  return true;
        case FEATURE_GRADE_HAS_GRADE:   return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_OUTCOMES:    return true;
        case FEATURE_BACKUP_MOODLE2:    return true;
        default:                        return null;
    }
}

//////////////////////////////////////////////////////////////////////////////
// API to add/edit/delete instance
//////////////////////////////////////////////////////////////////////////////

/**
 * Saves a new instance of the englishcentral into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $englishcentral An object from the form in mod_form.php
 * @param mod_englishcentral_mod_form $mform
 * @return int The id of the newly inserted englishcentral record
 */
function englishcentral_add_instance(stdClass $formdata, mod_englishcentral_mod_form $mform = null) {
    return englishcentral_process_formdata($formdata, $mform);
}

/**
 * Updates an instance of the englishcentral in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $formdata An object from the form in mod_form.php
 * @param mod_englishcentral_mod_form $mform
 * @return boolean Success/Fail
 */
function englishcentral_update_instance(stdClass $data, mod_englishcentral_mod_form $mform = null) {
    return englishcentral_process_formdata($data, $mform);
}

/**
 * update fields in recently submitted form data
 *
 * @param stdClass $data recently submitted formdata
 * @return boolean Success/Failure
 */
function englishcentral_process_formdata(stdClass $data, mod_englishcentral_mod_form $mform) {
    global $DB;

    // add/update record in main EC table
    $table = 'englishcentral';
    $update_grades = false;
    if (empty($data->instance)) {
        // add new instance
        $data->timecreated = time();
        $data->timemodified = $data->timecreated;
        $data->id = $DB->insert_record($table, $data);
    } else {
        // update exisiting instance
        $data->id = $data->instance;
        $data->timemodified = time();
        $DB->update_record($table, $data);

        $params = array('id' => $data->instance);
        $grade = $DB->get_field($table, 'grade', $params);
        $update_grades = ($data->grade == $grade ? false : true);
    }

    englishcentral_grade_item_update($data);

    if ($update_grades) {
        englishcentral_update_grades($data, 0, false);
    }

    return $data->id;
}

/**
 * Removes an instance of the englishcentral from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function englishcentral_delete_instance($id) {
    global $DB;
    $params = array('ecid' => $id);
    $DB->delete_records('englishcentral_videos', $params);
    $DB->delete_records('englishcentral_attempts', $params);
    $DB->delete_records('englishcentral_phonemes', $params);
    $DB->delete_records('englishcentral', array('id' => $id));
    return true;
}

//////////////////////////////////////////////////////////////////////////////
// API to update/select grades
//////////////////////////////////////////////////////////////////////////////

/**
 * Create grade item for given Englsh Central
 *
 * @category grade
 * @uses GRADE_TYPE_VALUE
 * @uses GRADE_TYPE_NONE
 * @param object $englishcentral object with extra cmidnumber
 * @param array|object $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function englishcentral_grade_item_update($englishcentral, $grades=null) {
    global $CFG;
    require_once($CFG->dirroot.'/lib/gradelib.php');

    $params = array('itemname' => $englishcentral->name);
    if (array_key_exists('cmidnumber', $englishcentral)) {
        $params['idnumber'] = $englishcentral->cmidnumber;
    }

    if ($englishcentral->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = $englishcentral->grade;
        $params['grademin'] = 0;
    } else if ($englishcentral->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid'] = -$englishcentral->grade;

        // Make sure current grade fetched correctly from $grades
        $currentgrade = null;
        if (! empty($grades)) {
            if (is_array($grades)) {
                $currentgrade = reset($grades);
            } else {
                $currentgrade = $grades;
            }
        }

        // When converting a score to a scale, use scale's grade maximum to calculate it.
        if (! empty($currentgrade) && $currentgrade->rawgrade !== null) {
            $grade = grade_get_grades($englishcentral->course, 'mod', 'englishcentral', $englishcentral->id, $currentgrade->userid);
            $params['grademax'] = reset($grade->items)->grademax;
        }
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    } else if (!empty($grades)) {
        // Need to calculate raw grade (Note: $grades has many forms)
        if (is_object($grades)) {
            $grades = array($grades->userid => $grades);
        } else if (array_key_exists('userid', $grades)) {
            $grades = array($grades['userid'] => $grades);
        }
        foreach ($grades as $key => $grade) {
            if (!is_array($grade)) {
                $grades[$key] = $grade = (array) $grade;
            }
            //check raw grade isnt null otherwise we insert a grade of 0
            if ($grade['rawgrade'] !== null) {
                $grades[$key]['rawgrade'] = ($grade['rawgrade'] * $params['grademax'] / 100);
            } else {
                //setting rawgrade to null just in case user is deleting a grade
                $grades[$key]['rawgrade'] = null;
            }
        }
    }

    if (is_object($englishcentral->course)) {
        $courseid = $englishcentral->course->id;
    } else {
        $courseid = $englishcentral->course;
    }

    return grade_update('mod/englishcentral', $courseid, 'mod', 'englishcentral', $englishcentral->id, 0, $grades, $params);
}

/**
 * Update grades in central gradebook
 *
 * @category grade
 * @param object $englishcentral
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 */
function englishcentral_update_grades($englishcentral, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/lib/gradelib.php');

    if (empty($englishcentral->grade)) {
        $grades = null;
    } else if ($grades = englishcentral_get_user_grades($englishcentral, $userid)) {
        // do nothing
    } else if ($userid && $nullifnone) {
        $grades = (object)array('userid' => $userid, 'rawgrade' => null);
    } else {
        $grades = null;
    }

    englishcentral_grade_item_update($englishcentral, $grades);
}

/**
 * Return grade for given user or all users.
 *
 * @global stdClass
 * @global object
 * @param int $ecid id of englishcentral
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function englishcentral_get_user_grades($englishcentral, $userid=0) {
    global $DB;

    $goal = 0;

    if ($englishcentral->watchgoal) {
        $goal += intval($englishcentral->watchgoal);
    }
    if ($englishcentral->learngoal) {
        $goal += intval($englishcentral->learngoal);
    }
    if ($englishcentral->speakgoal) {
        $goal += intval($englishcentral->speakgoal);
    }

    if ($goal) {
        $select = 'SUM(watchcomplete) + SUM(learncount) + SUM(speakcount)';
        $select = "ROUND(100 * ($select) / ?, 0)";
        // Note: MSSQL always requires precision for ROUND function.
    } else {
        // If no goals have been setup, all grades will be set to zero.
        $select = '?';
    }

    $select = "userid, $select AS rawgrade";
    $from   = '{englishcentral_attempts}';
    $where  = 'ecid = ?';
    $params = array($goal, $englishcentral->id);

    if ($userid) {
        $where .= ' AND userid = ?';
        $params[] = $userid;
    } else {
        $where .= ' GROUP BY userid';
    }

    return $DB->get_records_sql("SELECT $select FROM $from WHERE $where", $params);
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the englishcentral.
 *
 * @param $mform form passed by reference
 */
function englishcentral_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'englishcentralheader', get_string('modulenameplural', 'englishcentral'));
    $mform->addElement('advcheckbox', 'reset_englishcentral', get_string('deleteallattempts','englishcentral'));
}

/**
 * Course reset form defaults.
 * @param object $course
 * @return array
 */
function englishcentral_reset_course_form_defaults($course) {
    return array('reset_englishcentral' => 1);
}

/**
 * Removes all grades from gradebook
 *
 * @global stdClass
 * @global object
 * @param int $courseid
 * @param string optional type
 */
function englishcentral_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $sql = "SELECT l.*, cm.idnumber as cmidnumber, l.course as courseid
              FROM {englishcentral} l, {course_modules} cm, {modules} m
             WHERE m.name='englishcentral' AND m.id=cm.module AND cm.instance=l.id AND l.course=:course";
    $params = array ("course" => $courseid);
    if ($englishcentrals = $DB->get_records_sql($sql,$params)) {
        foreach ($englishcentrals as $englishcentral) {
            englishcentral_grade_item_update($englishcentral, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * englishcentral attempts for course $data->courseid.
 *
 * @global stdClass
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function englishcentral_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', 'englishcentral');
    $status = array();

    if (!empty($data->reset_englishcentral)) {
        $englishcentralssql = "SELECT l.id
                         FROM {englishcentral} l
                        WHERE l.course=:course";

        $params = array ("course" => $data->courseid);
        $DB->delete_records_select('englishcentral_attempts', "ecid IN ($englishcentralssql)", $params);
        $DB->delete_records_select('englishcentral_phs', "ecid IN ($englishcentralssql)", $params);

        // remove all grades from gradebook
        if (empty($data->reset_gradebook_grades)) {
            englishcentral_reset_gradebook($data->courseid);
        }

        $status[] = array('component' => $componentstr, 'item' => get_string('deleteallattempts', 'englishcentral'), 'error' => false);
    }

    /// updating dates - shift may be negative too
    if ($data->timeshift) {
        shift_course_mod_dates('englishcentral', array('available', 'deadline'), $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => false);
    }

    return $status;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function englishcentral_user_outline($course, $user, $mod, $englishcentral) {
    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $englishcentral the module instance record
 * @return void, is supposed to echp directly
 */
function englishcentral_user_complete($course, $user, $mod, $englishcentral) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in englishcentral activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function englishcentral_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link englishcentral_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function englishcentral_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see englishcentral_get_recent_mod_activity()}

 * @return void
 */
function englishcentral_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function englishcentral_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function englishcentral_get_extra_capabilities() {
    return array();
}

//////////////////////////////////////////////////////////////////////////////
// Gradebook API
//////////////////////////////////////////////////////////////////////////////

/**
 * Is a given scale used by the instance of englishcentral?
 *
 * This function returns if a scale is being used by one englishcentral
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $ecid ID of an instance of this module
 * @return bool true if the scale is used by the given englishcentral instance
 */
function englishcentral_scale_used($ecid, $scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('englishcentral', array('id' => $ecid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of englishcentral.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any englishcentral instance
 */
function englishcentral_scale_used_anywhere($scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('englishcentral', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

//////////////////////////////////////////////////////////////////////////////
// File API
//////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function englishcentral_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for englishcentral file areas
 *
 * @package mod_englishcentral
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function englishcentral_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the englishcentral file areas
 *
 * @package mod_englishcentral
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the englishcentral's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function englishcentral_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

//////////////////////////////////////////////////////////////////////////////
// Navigation API
//////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding englishcentral nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the englishcentral module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function englishcentral_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the englishcentral settings
 *
 * This function is called when the context for the page is a englishcentral module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $englishcentralnode {@link navigation_node}
 */
function englishcentral_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $englishcentralnode=null) {
}
