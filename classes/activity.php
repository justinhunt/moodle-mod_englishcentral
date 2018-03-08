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
 * Internal library of functions for module English Central
 *
 * All the englishcentral specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_englishcentral
 * @copyright  2018 Gordon Bateson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_englishcentral;

defined('MOODLE_INTERNAL') || die();

/**
 * Authentication class to access EnglishCentral API
 * originally used OAuth, modified to use JWT
 *
 *
 * @package    mod_englishcentral
 * @copyright  2018 Gordon Bateson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity {

    /**
     * construct English Central activity instance
     */
    function __construct($instance=null, $cm=null, $course=null, $context=null) {
        global $COURSE;

        $this->plugintype = 'mod';
        $this->pluginname = 'englishcentral';
        $this->plugin = $this->plugintype.'_'.$this->pluginname;

        if ($instance) {
            foreach ($instance as $field => $value) {
                $this->$field = $value;
            }
        }

        if ($cm) {
            $this->cm = $cm;
        }

        if ($course) {
            $this->course = $course;
        } else {
            $this->course = $COURSE;
        }

        if ($context) {
            $this->context = $context;
        } else if ($cm) {
            $this->context = \context_module::instance($cm->id);;
        } else if ($course) {
            $this->context = \context_course::instance($course->id);
        } else {
            $this->context = \context_system::instance();
        }

        $this->time = time();

        if ($this->can_manage()) {
            $this->available = true;
        } else if ($this->activityopen && $this->activityopen > $this->time) {
            $this->available = false;
        } else if ($this->activityclose && $this->activityclose < $this->time) {
            $this->available = false;
        } else {
            $this->available = true;
        }

        if ($this->can_manage()) {
            $this->viewable = true;
        } else if ($this->videoopen && $this->videoopen > $this->time) {
            $this->viewable = false;
        } else if ($this->videoclose && $this->videoclose < $this->time) {
            $this->viewable = false;
        } else {
            $this->viewable = true;
        }

        $this->config = get_config($this->plugin);
    }

    /**
     * Creates a new EnglishCentral activity
     *
     * @param stdclass $instance a row from the reader table
     * @param stdclass $cm a row from the course_modules table
     * @param stdclass $course a row from the course table
     * @return reader the new reader object
     */
    static public function create($instance=null, $cm=null, $course=null, $context=null) {
        return new activity($instance, $cm, $course, $context);
    }

    ////////////////////////////////////////////////////////////////////////////////
    // availability API
    ////////////////////////////////////////////////////////////////////////////////

    public function not_available() {
        return ($this->available ? false : true);
    }

    public function not_viewable() {
        return ($this->viewable ? false : true);
    }

    ////////////////////////////////////////////////////////////////////////////////
    // URLs API
    ////////////////////////////////////////////////////////////////////////////////

    public function get_report_url($escaped=null, $params=array()) {
        return $this->url('report.php', $escaped, $params);
    }

    public function get_view_url($escaped=null, $params=array()) {
        return $this->url('view.php', $escaped, $params);
    }

    public function get_viewajax_url($escaped=null, $params=array()) {
        return $this->url('view.ajax.php', $escaped, $params);
    }

    public function get_videoinfo_url($escaped=null) {
        $lang = substr(current_language(), 0, 2);
        switch ($lang) {
            case 'en': // English
                return 'https://www.englishcentral.com/videodetails';
            case 'ar': // Arabic
            case 'es': // Spanish
            case 'he': // Hebrew
            case 'ja': // Japanese
            case 'pt': // Portuguese
            case 'ru': // Russian
            case 'tr': // Turkish
            case 'vi': // Vietnamese
                return "https://$lang.englishcentral.com/videodetails";
            case 'zh': // Chinese
                return 'https://www.englishcentralchina.com/videodetails';
            default:
                'https://www.englishcentral.com/videodetails?setLanguage='.$lang;
        }
    }

    public function url($filepath, $escaped=null, $params=array()) {
        if (isset($this->cm)) {
            $params['id'] = $this->cm->id;
        }
        $url = '/'.$this->plugintype.'/'.$this->pluginname.'/'.$filepath;
        $url = new \moodle_url($url, $params);
        if (is_bool($escaped)) {
            $url = $url->out($escaped);
        }
        return $url;
    }

    ////////////////////////////////////////////////////////////////////////////////
    // capabilities API
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * can
     *
     * @prefix string $name
     * @prefix string $type (optional, default="")
     * @prefix object $context (optional, default=null)
     * @return boolean
     */
    public function can($name, $type='', $context=null) {
        $defaulttype = $this->plugintype.'/'.$this->pluginname;
        if ($type==='') {
            $type = $defaulttype;
            $is_defaulttype = true;
        } else {
            $is_defaulttype = ($type==$defaulttype);
        }
        if ($context===null) {
            $context = $this->context;
            $is_defaultcontext = true;
        } else {
            $is_defaultcontext = ($context->id==$this->context->id);
        }
        if ($is_defaulttype && $is_defaultcontext) {
            $can = 'can'.$name;
            if (! isset($this->$can)) {
                $this->$can = has_capability($type.':'.$name, $this->context);
            }
            return $this->$can;
        }
        return has_capability($type.':'.$name, $context);
    }

    /*
     * can_addinstance
     *
     * @return boolean
     **/
    public function can_addinstance() {
        return $this->can('addinstance');
    }

    /*
     * can_manage
     *
     * @return boolean
     **/
    public function can_manage() {
        return $this->can('manage');
    }

    /*
     * can_submit
     *
     * @return boolean
     **/
    public function can_submit() {
        return $this->can('submit');
    }

    /*
     * can_view
     *
     * @return boolean
     **/
    public function can_view() {
        return $this->can('view');
    }

    /*
     * can_viewreports
     *
     * @return boolean
     **/
    public function can_viewreports() {
        return $this->can('viewreports');
    }

    /**
     * accessallgroups
     *
     * @param xxx $context (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    function can_accessallgroups() {
        return $this->can('moodle/site:accessallgroups');
    }

    /*
     * req(uire)
     *
     * @prefix string $name
     * @prefix string $type (optional, default="")
     * @prefix object $context (optional, default=null)
     * @return void, but may terminate script execution
     **/
    public function req($name, $type='', $context=null) {
        if ($this->can($name, $type, $context)) {
            // do nothing
        } else {
            if ($type==='') {
                $type = 'mod/englishcentral';
            }
            if ($context===null) {
                $context = $this->context;
            }
            return require_capability($type.':'.$name, $context);
        }
    }

    ////////////////////////////////////////////////////////////////////////////////
    // strings API
    ////////////////////////////////////////////////////////////////////////////////

    public function get_string($name, $a=null) {
        return get_string($name, $this->plugin, $a);
    }

    ////////////////////////////////////////////////////////////////////////////////
    // database API
    ////////////////////////////////////////////////////////////////////////////////

    public function get_videoids() {
        global $DB;
        return $DB->get_records_menu('englishcentral_videos', array('ecid' => $this->id), 'sortorder', 'id,videoid');
    }

    public function get_accountid() {
        global $DB, $USER;
        return $DB->get_field('englishcentral_accountids', 'accountid', array('userid' => $USER->id));
    }

    public function get_accountids($groupid=0) {
        global $DB;
        $groupid = 0;
        if ($userids = $this->get_userids($groupid)) {
            list($select, $params) = $DB->get_in_or_equal($userids);
            return $DB->get_records_select_menu('englishcentral_accountids', "userid $select", $params, 'userid, accountid');
        }
        return false;
    }

    public function get_userids($groupid=0) {
        global $DB;
        $mode = $this->get_groupmode();
        if ($mode==NOGROUPS || $mode==VISIBLEGROUPS || $this->can_accessallgroups()) {
            $users = get_enrolled_users($this->context, 'mod/englishcentral:view', $groupid, 'u.id', 'id');
            if (empty($users)) {
                return false;
            }
            return array_keys($users);
        } else {
            if ($groupid) {
                $select = 'groupid = ?';
                $params = array($groupid);
            } else {
                $groups = groups_get_user_groups($course->id);
                if (empty($groups)) {
                    return false;
                }
                list($select, $params) = $DB->get_in_or_equal($groups['0']);
            }
            $users = $DB->get_records_select_menu('group_members', 'groupid '.$select, $params, 'id, userid');
            if (empty($users)) {
                return false;
            }
            return array_unique($users);
        }
    }

    /*
     * get groupmode (0=NOGROUPS, 1=VISIBLEGROUPS, 2=SEPARATEGROUPS)
     *
     * @return integer, the groupmode of this activity or course
     **/
    public function get_groupmode() {
        if ($this->cm) {
            return groups_get_activity_groupmode($this->cm);
        }
        if ($this->course) {
            return groups_get_course_groupmode($this->course);
        }
        return NOGROUPS;
    }

    public function get_progress() {
        global $DB, $USER;
        $progress = (object)array(
            'watch' => 0,
            'learn' => 0,
            'speak' => 0,
        );
        $table = 'englishcentral_attempts';
        $params = array('ecid' => $this->id,
                        'userid' => $USER->id);
        if ($attempts = $DB->get_records($table, $params)) {
            foreach ($attempts as $attempt) {
                $progress->watch += $attempt->watchcomplete;
                $progress->learn += $attempt->learncount;
                $progress->speak += $attempt->speakcount;
            }
        }
        return $progress;
    }

    public function update_progress($dialog) {
        global $DB, $USER;

        // extract/create $attempt
        $table = 'englishcentral_attempts';
        $params = array('ecid' => $this->id,
                        'userid' => $USER->id,
                        'videoid' => $dialog->dialogID);
        if ($attempt = $DB->get_record($table, $params)) {
            // $USER has attempted this video before
        } else {
            $attempt = (object)$params;
            $attempt->timecreated = $this->time;
        }

        $progress = $this->extract_progress($dialog, $attempt);

        foreach ($progress as $name => $value) {
            $attempt->$name = $value;
        }

        if (empty($attempt->id)) {
            $DB->insert_record($table, $attempt);
        } else {
            $DB->update_record($table, $attempt);
        }

        englishcentral_update_grades($this, $USER->id);
    }

    /**
     * Format data about dialog activities returned from EC ReportCard api
     * e.g. /rest/report/dialog/{dialogID}/progress
     *
     * @param array $dialog JSON data returned from EC REST call
     * @param object $attempt record from "englishcentral_attempts"
     * @return array of $progress data
     */
    public function extract_progress($dialog, $attempt) {

        // initialize totals for goals
        $progress = array(
            'dialogID' => $dialog->dialogID,

            'watchcomplete' => 0,
            'watchtotal'    => 0,
            'watchcount'    => 0,
            'watchlineids'  => array(), // dialogLineID's of lines watched,

            'learncomplete' => 0,
            'learntotal'    => 0,
            'learncount'    => 0,
            'learnwordids'  => array(), // wordHeadID's of words learned,

            'speakcomplete' => 0,
            'speaktotal'    => 0,
            'speakcount'    => 0,
            'speaklineids'  => array(), // dialogLineID's of lines spoken,

            'totalpoints'   => 0,

            // this info is no longer available
            'activetime'    => 0,
            'totaltime'     => 0,
            'sessionScore'  => 0,
            'sessionGrade'  => '', // A-F
        );

        if (isset($dialog->hash)) {
           $progress['hash'] = $dialog->hash;
        }
        if (isset($dialog->totalPoints)) {
           $progress['totalpoints']  = $dialog->totalPoints;
        }

        // populate the $progress array with values earned hitherto
        foreach (array('watchlineids', 'learnwordids', 'speaklineids') as $ids) {
            if (isset($attempt->$ids) && $attempt->$ids) {
                $progress[$ids] = explode(',', $attempt->$ids);
                $progress[$ids] = array_fill_keys($progress[$ids], 1);
            }
        }


        if (empty($dialog->activities)) {
            return $progress;
        }

        foreach($dialog->activities as $activity) {

            // activityType     : watchActivity / speakActivity
            // activityID       : 208814
            // activityTypeID   : (see below)
            // activityPoints   : 10
            // activityProgress : 1
            // completed        : 1
            // grade            : A (speakActivity only ?)

            // extract DB fields
            switch ($activity->activityTypeID) {

                case \mod_englishcentral\auth::ACTIVITYTYPE_WATCHING: // =9
                    $progress['watchcomplete'] = (empty($activity->completed) ? 0 : 1);
                    foreach ($activity->watchedDialogLines as $line) {
                        $progress['watchlineids'][$line->dialogLineID] = 1;
                    }
                    break;

                case \mod_englishcentral\auth::ACTIVITYTYPE_LEARNING: // =10
                    $progress['learncomplete'] = (empty($activity->completed) ? 0 : 1);
                    foreach ($activity->learnedDialogLines as $line) {
                        foreach($line->learnedWords as $word) {
                            if ($word->completed) {
                                $progress['learnwordids'][$word->wordHeadID] = 1;
                            }
                        }
                    }
                    break;

                case \mod_englishcentral\auth::ACTIVITYTYPE_SPEAKING: // =11
                    $progress['speakcomplete'] = (empty($activity->completed) ? 0 : 1);
                    foreach ($activity->spokenDialogLines as $line) {
                        $progress['speaklineids'][$line->dialogLineID] = 1;
                    }
                    break;
            }
        }

        $progress['watchcount'] += count($progress['watchlineids']);
        $progress['learncount'] += count($progress['learnwordids']);
        $progress['speakcount'] += count($progress['speaklineids']);

        $progress['watchlineids'] = implode(',', array_keys($progress['watchlineids']));
        $progress['learnwordids'] = implode(',', array_keys($progress['learnwordids']));
        $progress['speaklineids'] = implode(',', array_keys($progress['speaklineids']));

        return $progress;
    }

    public function get_attempts_fields($addvideoid=true) {
        $fields = 'watchcount,watchcomplete,'.
                  'learncount,learncomplete,'.
                  'speakcount,speakcomplete';
        if ($addvideoid) {
            $fields = "videoid,$fields";
        }
        return $fields;
    }

    public function get_attempts($videoid=0) {
        global $DB, $USER;
        $params = array('ecid' => $this->id,
                        'userid' => $USER->id);
        if ($videoid) {
            $params['videoid'] = $videoid;
        }
        $fields = $this->get_attempts_fields();
        if ($attempts = $DB->get_records('englishcentral_attempts', $params, 'id', $fields)) {
            return $attempts;
        } else {
            return array();
        }
    }
}
