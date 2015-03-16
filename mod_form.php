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

    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = $this->_form;
		
		//just for now
		$config = new stdClass();
		$config->watchmode=1;
		$config->speakmode=1;
		$config->speaklitemode=0;
		$config->simpleui=0;
		$config->learnmode=1;
		$config->lightboxmode=0;
		$config->hiddenchallengemode=0;

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('englishcentralname', 'englishcentral'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'englishcentralname', 'englishcentral');

        // Adding the standard "intro" and "introformat" fields
        $this->add_intro_editor();

        //-------------------------------------------------------------------------------
        // Adding the rest of englishcentral settings, spreeading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic
        $mform->addElement('text', 'videotitle', get_string('videotitle', 'englishcentral'), array('size'=>'64'));
        $mform->addElement('text', 'videoid', get_string('videoid', 'englishcentral'), array('size'=>'24'));
        $mform->addRule('videotitle', null, 'required', null, 'client');
        $mform->addRule('videoid', null, 'required', null, 'client');
        $mform->setType('videotitle', PARAM_TEXT);
        $mform->setType('videoid', PARAM_INT);
        
        //player options
        $mform->addElement('advcheckbox', 'simpleui', get_string('simpleui', 'englishcentral'));
        $mform->setDefault('simpleui', $config->simpleui);
        $mform->addElement('advcheckbox', 'watchmode', get_string('watchmode', 'englishcentral'));
        $mform->setDefault('watchmode', $config->watchmode);
        $mform->addElement('advcheckbox', 'speakmode', get_string('speakmode', 'englishcentral'));
        $mform->setDefault('speakmode', $config->speakmode);
        $mform->addElement('advcheckbox', 'speaklitemode', get_string('speaklitemode', 'englishcentral'));
        $mform->setDefault('speaklitemode', $config->speaklitemode);
        $mform->addElement('advcheckbox', 'learnmode', get_string('learnmode', 'englishcentral'));
        $mform->setDefault('learnmode', $config->learnmode);
        $mform->addElement('advcheckbox', 'hiddenchallengemode', get_string('hiddenchallengemode', 'englishcentral'));
        $mform->setDefault('hiddenchallengemode', $config->hiddenchallengemode);
       // $mform->addElement('advcheckbox', 'lightboxmode', get_string('lightboxmode', 'englishcentral'));
       // $mform->setDefault('lightboxmode', $config->lightboxmode);
   
        // Grade.
        $this->standard_grading_coursemodule_elements();
		
        //attempts
        $attemptoptions = array(0 => get_string('unlimited', 'englishcentral'),
                            1 => '1',2 => '2',3 => '3',4 => '4',5 => '5',);
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', 'englishcentral'), $attemptoptions);
        
        //grade options
        $gradeoptions = array(MOD_ENGLISHCENTRAL_GRADEHIGHEST => get_string('gradehighest', 'englishcentral'),
                            MOD_ENGLISHCENTRAL_GRADELOWEST => get_string('gradelowest', 'englishcentral'),
                            MOD_ENGLISHCENTRAL_GRADELATEST => get_string('gradelatest', 'englishcentral'),
                            MOD_ENGLISHCENTRAL_GRADEAVERAGE => get_string('gradeaverage', 'englishcentral'),
							MOD_ENGLISHCENTRAL_GRADENONE => get_string('gradenone', 'englishcentral'));
        $mform->addElement('select', 'gradeoptions', get_string('gradeoptions', 'englishcentral'), $gradeoptions);
        

        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }
}
