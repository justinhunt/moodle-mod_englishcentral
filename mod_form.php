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

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/englishcentral/lib.php');

/**
 * Module instance settings form
 */
class mod_englishcentral_mod_form extends moodleform_mod {

    /** size of numeric text boxes */
    const TEXT_NUM_SIZE = 4;

    /**
     * Defines forms elements
     */
    public function definition() {
        global $PAGE;

        $plugin = 'mod_englishcentral';
        $config = get_config($plugin);

        $PAGE->requires->js_call_amd("$plugin/form", 'init');

        $mform = $this->_form;

        $dateoptions = array('optional' => true);
        $textoptions = array('size' => self::TEXT_NUM_SIZE);

        //-------------------------------------------------------------------------------
        $name = 'general';
        $label = get_string($name, 'form');
        $mform->addElement('header', $name, $label);
        //-------------------------------------------------------------------------------

        // Adding the standard "name" field
        $name = 'name';
        $label = get_string('activityname', $plugin);
        $mform->addElement('text', $name, $label, array('size'=>'64'));
        if (empty($CFG->formatstringstriptags)) {
            $mform->setType($name, PARAM_CLEAN);
        } else {
            $mform->setType($name, PARAM_TEXT);
        }
        $mform->addRule($name, null, 'required', null, 'client');
        $mform->addRule($name, get_string('maximumchars', null, 255), 'maxlength', 255, 'client');
        $mform->addHelpButton($name, 'activityname', $plugin);

        // Adding the standard "intro" and "introformat" fields
        $this->standard_intro_elements();

        //-----------------------------------------------------------------------------
        $name = 'timing';
        $label = get_string($name, 'form');
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);
        //-----------------------------------------------------------------------------

        $name = 'availablefrom';
        $label = get_string($name, $plugin);
        $mform->addElement('date_time_selector', $name, $label, $dateoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $this->set_type_default_advanced($mform, $config, $name, PARAM_INT);

        $name = 'readonlyuntil';
        $label = get_string($name, $plugin);
        $mform->addElement('date_time_selector', $name, $label, $dateoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $this->set_type_default_advanced($mform, $config, $name, PARAM_INT);

        $name = 'readonlyfrom';
        $label = get_string($name, $plugin);
        $mform->addElement('date_time_selector', $name, $label, $dateoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $this->set_type_default_advanced($mform, $config, $name, PARAM_INT);

        $name = 'availableuntil';
        $label = get_string($name, $plugin);
        $mform->addElement('date_time_selector', $name, $label, $dateoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $this->set_type_default_advanced($mform, $config, $name, PARAM_INT);

        //-------------------------------------------------------------------------------
        $name = 'goals';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);
        //-------------------------------------------------------------------------------

        $names = array('watchgoal' => 10,
                       'learngoal' => 20,
                       'speakgoal' => 10,
                       'studygoal' => 60);
        foreach ($names as $name => $default) {
            $label = get_string($name, $plugin);
            $units = get_string($name.'units', $plugin);
            $elements = array(
                $mform->createElement('text', $name, '', $textoptions),
                $mform->createElement('static', '', '', $units)
            );
            $mform->addElement('group', $name.'group', $label, $elements, ' ', false);
            $mform->setType($name, PARAM_INT);
            $mform->setDefault($name, $default);
            $mform->addHelpButton($name.'group', $name, $plugin);
        }

        // add grade elements
        $this->standard_grading_coursemodule_elements();

        // add standard elements
        $this->standard_coursemodule_elements();

        // add standard buttons
        $this->add_action_buttons();
    }

    /**
     * set_type_default_advanced
     *
     * @param $mform
     * @param $config
     * @param $name of field
     * @param $type PARAM_xxx constant value
     * @param $default (optional, default = null)
     * @todo Finish documenting this function
     */
    private function set_type_default_advanced($mform, $config, $name, $type, $default=null) {
        $mform->setType($name, $type);
        if (isset($config->$name)) {
            $mform->setDefault($name, $config->$name);
        } else if ($default) {
            $mform->setDefault($name, $default);
        }
        $adv_name = 'adv'.$name;
        if (isset($config->$adv_name)) {
            $mform->setAdvanced($name, $config->$adv_name);
        }
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
}
