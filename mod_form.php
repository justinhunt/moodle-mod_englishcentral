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
 * The main englishcentral configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_englishcentral
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_englishcentral\constants;
use mod_englishcentral\utils;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/englishcentral/lib.php');

/**
 * Module instance settings form
 */
class mod_englishcentral_mod_form extends moodleform_mod {

    /** size of numeric text boxes */
    const TEXT_NUM_SIZE = 4;

    public function __construct($current, $section, $cm, $course, $ajaxformdata=null, $customdata=null) {
        global $CFG;

        $this->current   = $current;
        $this->_instance = $current->instance;
        $this->_section  = $section;
        $this->_cm       = $cm;
        $this->_course   = $course;
        $this->_modname = 'englishcentral';

        // Set context
        if ($cm) {
            $this->context = context_module::instance($cm->id);
        } else {
            $this->context = context_course::instance($course->id);
        }

        // Set the course format.
        require_once($CFG->dirroot . '/course/format/lib.php');
        $this->courseformat = course_get_format($course);

        $this->init_features();

        moodleform::__construct('modedit.php', $customdata, 'post', '', null, true, $ajaxformdata);
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        global $PAGE;

        $mform = $this->_form;

        //Add this activity specific form fields
        //We want to do this procedurally because in setup tabs we want to show a subset of this form
        // with just the activity specific fields,and we use a custom form and the same elements
        utils::add_mform_elements($mform,$this->context);

        // Grade.
        $this->standard_grading_coursemodule_elements();

        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();

        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }


    /**
     * return a field value from the original record
     * this function is useful to see if a value has changed
     *
     * @param string the $field name
     * @param mixed the $default value (optional, default=null)
     * @return mixed the field value if it exists, $default otherwise
     */
    public function get_originalvalue($field, $default=null) {
        if (isset($this->current->$field)) {
            return $this->current->$field;
        } else {
            return $default;
        }
    }

    /**
     * Display module-specific activity completion rules.
     * Part of the API defined by moodleform_mod
     * @return array Array of string IDs of added items, empty array if none
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $plugin = 'mod_englishcentral';

        // array of elements names to be returned by this method
        $names = array();

        // these fields will be disabled if grade type is not "point" or grade points = 0
        $disablednames = array('completionusegrade');

        // add "minimum grade" completion condition
        $name = 'completionmingrade';
        $label = get_string($name, $plugin);
        if (empty($this->current->$name)) {
            $value = 0.0;
        } else {
            $value = floatval($this->current->$name);
        }
        $group = array();
        $group[] = &$mform->createElement('checkbox', $name.'enabled', '', $label);
        $group[] = &$mform->createElement('static', $name.'space', '', ' &nbsp; ');
        $group[] = &$mform->createElement('text', $name, '', array('size' => 3));
        $mform->addGroup($group, $name.'group', '', '', false);
        $mform->setType($name, PARAM_FLOAT);
        $mform->setDefault($name, 0.00);
        $mform->setType($name.'enabled', PARAM_INT);
        $mform->setDefault($name.'enabled', empty($value) ? 0 : 1);
        $mform->disabledIf($name, $name.'enabled', 'notchecked');
        $names[] = $name.'group';
        $disablednames[] = $name.'group';

        // add "grade passed" completion condition
        $name = 'completionpass';
        $label = get_string($name, $plugin);
        $mform->addElement('advcheckbox', $name, '', $label);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, 0);
        $names[] = $name;
        $disablednames[] = $name;

        // add "status completed" completion condition
        $name = 'completiongoals';
        $label = get_string($name, $plugin);
        $mform->addElement('advcheckbox', $name, '', $label);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, 0);
        $names[] = $name;
        // no need to disable this field :-)

        // disable grade conditions, if necessary
        foreach ($disablednames as $name) {
            if ($mform->elementExists($name)) {
                $mform->disabledIf($name, 'grade[modgrade_point]', 'eq', 0);
                $mform->disabledIf($name, 'grade[modgrade_type]', 'neq', 'point');
            }
        }

        return $names;
    }

    /**
     * Called during validation. Indicates whether a module-specific completion rule is selected.
     *
     * @param array $data Input data (not yet validated)
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        if (empty($data['completionmingradeenabled']) || empty($data['completionmingrade'])) {
            if (empty($data['completionpass']) && empty($data['completiongoals'])) {
                return false;
            }
        }
        return true; // at least one of the module-specific completion conditions is set

    }
}
