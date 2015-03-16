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
 * Defines all the backup steps that will be used by {@link backup_englishcentral_activity_task}
 *
 * @package     mod_englishcentral
 * @category    backup
 * @copyright   2014 Justin Hunt <poodllsupport@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/englishcentral/lib.php');

/**
 * Defines the complete webquest structure for backup, with file and id annotations
 *
 */
class backup_englishcentral_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the structure of the 'englishcentral' element inside the webquest.xml file
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // are we including userinfo?
        $userinfo = $this->get_setting_value('userinfo');

        ////////////////////////////////////////////////////////////////////////
        // XML nodes declaration - non-user data
        ////////////////////////////////////////////////////////////////////////

        // root element describing englishcentral instance
        $englishcentral = new backup_nested_element('englishcentral', array('id'), array(
            'course','name','intro','introformat','videotitle','videoid','watchmode','speakmode',
			'learnmode','hiddenchallengemode','speaklitemode','simpleui','maxattempts','grade',
			'gradeoptions','timecreated','timemodified','lightboxmode'
			));
		
		//attempts
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', array('id'),array(
			"englishcentralid","userid","linestotal","totalactivetime","watchedcomplete","activetime"
			,"datecompleted","linesrecorded","lineswatched","points","recordingcomplete","sessiongrade"
			,"sessionscore","videoid","status","timecreated"
		));
		
		//phenomes
        $phonemes = new backup_nested_element('phonemes');
        $phoneme = new backup_nested_element('phoneme', array('id'),array(
			 "englishcentralid","attemptid","userid","phoneme","badcount","goodcount","timecreated" 
		));
		
		// Build the tree.
        $englishcentral->add_child($attempts);
        $attempts->add_child($attempt);
        $englishcentral->add_child($phonemes);
        $phonemes->add_child($phoneme);
		


        // Define sources.
        $englishcentral->set_source_table('englishcentral', array('id' => backup::VAR_ACTIVITYID));

        //sources if including user info
        if ($userinfo) {
			$attempt->set_source_table('englishcentral_attempt',
											array('englishcentralid' => backup::VAR_PARENTID));
			$phoneme->set_source_table('englishcentral_phs',
											array('englishcentralid' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $attempt->annotate_ids('user', 'userid');
		$phoneme->annotate_ids('user', 'userid');


        // Define file annotations.
        // intro file area has 0 itemid.
        $englishcentral->annotate_files('mod_englishcentral', 'intro', null);

        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($englishcentral);
		

    }
}
