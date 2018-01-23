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

    const PERIOD_NONE    = 0;
    const PERIOD_WEEKLY  = 1;
    const PERIOD_MONTHLY = 2;
    const PERIOD_ENDDATE = 3;

    /**
     * Defines forms elements
     */
    public function definition() {

        // cache the name of this plugin
        $plugin = 'mod_englishcentral';

        $mform = $this->_form;

		$config = get_config('englishcentral');

        $str = (object)array(
            'maximumchars' => get_string('maximumchars', '', 255),
            'unlimited' => get_string('unlimited'),
            'monthly' => get_string('monthly', 'calendar'),
            'weekly' => get_string('weekly', 'calendar'),
            'date' => get_string('date')
        );


        //-------------------------------------------------------------------------------
        $name = 'general';
        $label = get_string($name, 'form');
        $mform->addElement('header', $name, $label);
        //-------------------------------------------------------------------------------

        // Adding the standard "name" field
        $name = 'name';
        $label = get_string('englishcentralname', $plugin);
        $mform->addElement('text', $name, $label, array('size'=>'64'));
        if (empty($CFG->formatstringstriptags)) {
            $mform->setType($name, PARAM_CLEAN);
        } else {
            $mform->setType($name, PARAM_TEXT);
        }
        $mform->addRule($name, null, 'required', null, 'client');
        $mform->addRule($name, $str->maximumchars, 'maxlength', 255, 'client');
        $mform->addHelpButton($name, 'englishcentralname', $plugin);

        // Adding the standard "intro" and "introformat" fields
        $this->standard_intro_elements();

        //-------------------------------------------------------------------------------
        $name = 'goals';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);
        //-------------------------------------------------------------------------------

        $options = array('size' => '3');
        $names = array('watchgoal' => 10,
                       'learngoal' => 20,
                       'speakgoal' => 10,
                       'studygoal' => 60);
        foreach ($names as $name => $default) {
            $label = get_string($name, $plugin);
            $units = get_string($name.'units', $plugin);
            $elements = array(
                $mform->createElement('text', $name, '', $options),
                $mform->createElement('static', '', '', $units)
            );
            $mform->addElement('group', $name.'group', $label, $elements, ' ', false);
            $mform->setType($name, PARAM_INT);
            $mform->setDefault($name, $default);
            $mform->addHelpButton($name.'group', $name, $plugin);
        }

        $name = 'goalperiod';
        $label = get_string($name, $plugin);
        $newline = html_writer::empty_tag('br');
        $elements = array(
            $mform->createElement('radio', 'periodtype', '', $str->unlimited, self::PERIOD_NONE),
            $mform->createElement('static', '', '', $newline),
            $mform->createElement('radio', 'periodtype', '', $str->weekly, self::PERIOD_WEEKLY),
            $mform->createElement('select', 'weekday', '', self::weekday_options($plugin)),
            $mform->createElement('static', '', '', $newline),
            $mform->createElement('radio', 'periodtype', '', $str->monthly, self::PERIOD_MONTHLY),
            $mform->createElement('select', 'monthday', '', self::monthday_options($plugin)),
            $mform->createElement('static', '', '', $newline),
            $mform->createElement('radio', 'periodtype', '', $str->date, self::PERIOD_ENDDATE),
            $mform->createElement('date_selector', 'enddate')
        );
        $mform->addElement('group', $name, $label, $elements, ' ', false);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);

        $mform->disabledIf('weekday',        'periodtype', 'neq', self::PERIOD_WEEKLY);
        $mform->disabledIf('monthday',       'periodtype', 'neq', self::PERIOD_MONTHLY);
        $mform->disabledIf('enddate[day]',   'periodtype', 'neq', self::PERIOD_ENDDATE);
        $mform->disabledIf('enddate[month]', 'periodtype', 'neq', self::PERIOD_ENDDATE);
        $mform->disabledIf('enddate[year]',  'periodtype', 'neq', self::PERIOD_ENDDATE);

        // add grade elements
        $this->standard_grading_coursemodule_elements();

        // add standard elements
        $this->standard_coursemodule_elements();

        // add standard buttons
        $this->add_action_buttons();
    }

    /**
     * Defines default values for form elements
     */
    public function data_preprocessing(&$values) {

        $name = 'goalperiod';
        if (empty($values[$name])) {
            $value = 0;
        } else {
            $value = intval($values[$name]);
        }

        $name = 'periodtype';
        switch (true) {

            case ($value==0):
                $values[$name] = self::PERIOD_NONE;
                break;

            case ($value < 0):
                $values['weekday'] = abs($value);
                $values[$name] = self::PERIOD_WEEKLY;
                break;

            case ($value <= 31):
                $values['monthday'] = $value;
                $values[$name] = self::PERIOD_MONTHLY;
                break;

            default:
                $values['enddate'] = $value;
                $values[$name] = self::PERIOD_ENDDATE;
        }
    }

    /**
     * Process $data from a recently submitted form
     */
    public function form_postprocessing($data) {

        if (empty($data->instance)) {
            $data->timecreated = time();
        } else {
            $data->timemodified = time();
        }

        if (isset($data->periodtype)) {
            $type = $data->periodtype;
        } else {
            $type = self::PERIOD_NONE;
        }

        switch ($type) {
            case self::PERIOD_WEEKLY:
                $data->goalperiod = -intval($data->weekday);
                break;
            case self::PERIOD_MONTHLY:
                $data->goalperiod = intval($data->monthday);
                break;
            case self::PERIOD_ENDDATE:
                $data->goalperiod = intval($data->enddate);
                break;
            case self::PERIOD_NONE:
                $data->goalperiod = 0;
                break;
        }

        unset($data->periodtype);
        unset($data->weekday);
        unset($data->monthday);
        unset($data->enddate);
    }

	/**
	 * get options for weekdays
	 */
	static protected function weekday_options($plugin) {
		$days = array('0' => get_string('sunday',    'calendar'),
                      '1' => get_string('monday',    'calendar'),
                      '2' => get_string('tuesday',   'calendar'),
                      '3' => get_string('wednesday', 'calendar'),
                      '4' => get_string('thursday',  'calendar'),
                      '5' => get_string('friday',    'calendar'),
                      '6' => get_string('saturday',  'calendar'));

        $firstday = get_string('firstdayofweek', 'langconfig');
        for ($i=0; $i < $firstday; $i++) {
            $day = $days["$i"];
            unset($days["$i"]);
            $days["$i"] = $day;
        }

        return self::due_options($plugin, $days);
    }

	/**
	 * get options for monthdays
	 */
	static protected function monthday_options($plugin) {
	    $fmt = get_string('duedateformat', $plugin);
	    $dates = array();
        for ($i=0; $i<=30; $i++) {
            $dates["$i"] = date($fmt, $i * DAYSECS);
        }
        return self::due_options($plugin, $dates);
    }

	/**
	 * get options for menu of due days/dates
	 */
	static protected function due_options($plugin, $options) {
        foreach ($options as $i => $option) {
            $options[$i] = get_string('due', $plugin, $option);
        }
        return $options;
    }
}
