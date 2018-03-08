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
 * @package   mod_englishcentral
 * @copyright 2014 Justin Hunt poodllsupport@gmail.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/englishcentral/backup/moodle2/restore_englishcentral_stepslib.php'); // Because it exists (must)

/**
 * englishcentral restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_englishcentral_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new restore_englishcentral_activity_structure_step('englishcentral_structure', 'englishcentral.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        return array(
            new restore_decode_content('englishcentral', array('intro'), 'englishcentral')
        );
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        return array (
            new restore_decode_rule('ENGLISHCENTRALVIEWBYID', '/mod/englishcentral/view.php?id=$1', 'course_module'),
            new restore_decode_rule('ENGLISHCENTRALINDEX', '/mod/englishcentral/index.php?id=$1', 'course')
        );
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * englishcentral logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        return array(
            new restore_log_rule('englishcentral', 'add', 'view.php?id={course_module}', '{englishcentral}'),
            new restore_log_rule('englishcentral', 'update', 'view.php?id={course_module}', '{englishcentral}'),
            new restore_log_rule('englishcentral', 'view', 'view.php?id={course_module}', '{englishcentral}')
        );
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        return array(
            new restore_log_rule('englishcentral', 'view all', 'index.php?id={course}', null)
        );
    }
}
