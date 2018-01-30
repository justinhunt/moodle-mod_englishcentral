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
            $this->context = context_module($cm->id);;
        } else {
            $this->context = context_course($this->course->id);
        }

        $this->time = time();

        if ($this->can_manage()) {
            $this->available = true;
        } else if ($this->availableuntil && $this->availableuntil < $this->time) {
            $this->available = false;
        } else if ($this->availablefrom && $this->availablefrom > $this->time) {
            $this->available = false;
        } else {
            $this->available = true;
        }

        if ($this->can_manage()) {
            $this->readonly = false;
        } else if ($this->readonlyuntil && $this->readonlyuntil > $this->time) {
            $this->readonly = true;
        } else if ($this->readonlyfrom && $this->readonlyfrom < $this->time) {
            $this->readonly = true;
        } else {
            $this->readonly = false;
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
    static public function create($instance, $cm, $course, $context=null) {
        return new activity($instance, $cm, $course, $context);
    }

    ////////////////////////////////////////////////////////////////////////////////
    // availability API
    ////////////////////////////////////////////////////////////////////////////////

    public function not_available() {
        return ($this->available ? false : true);
    }

    public function not_viewable() {
        return ($this->readonly ? true : false);
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
        if ($videos = $DB->get_records('englishcentral_videos', array('ecid' => $this->id))) {
            return array_keys($videos);
        }
        return array();
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
}
