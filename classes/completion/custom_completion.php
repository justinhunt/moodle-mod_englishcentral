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

declare(strict_types=1);

namespace mod_englishcentral\completion;

use core_completion\activity_custom_completion;

/**
 * Activity custom completion subclass for the forum activity.
 *
 * Class for defining english centrals custom completion rules and fetching the completion statuses
 * of the custom completion rules for a giveninstance and a user.
 *
 * @package mod_englishcentral
 * @copyright Justin Hunt <poodllsupport@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $CFG, $DB;

        $this->validate_rule($rule);

        $userid = $this->userid;

        if (!$ec = $DB->get_record('englishcentral', array('id' => $this->cm->instance))) {
            throw new \moodle_exception('Unable to find EnglishCentral with id ' . $this->cm->instance);
        }

        $course = $DB->get_record('course', array('id' => $this->cm->course), '*', MUST_EXIST);
        $ec = \mod_englishcentral\activity::create($ec, $this->cm, $course);

        // get grade, if necessary
        $grade = false;
        if ($ec->completionmingrade > 0.0 || $ec->completionpass) {
            require_once($CFG->dirroot.'/lib/gradelib.php');
            $params = array('courseid'     => $course->id,
                'itemtype'     => 'mod',
                'itemmodule'   => 'englishcentral',
                'iteminstance' => $this->cm->instance);
            if ($grade_item = grade_item::fetch($params)) {
                $grades = grade_grade::fetch_users_grades($grade_item, array($userid), false);
                if (isset($grades[$userid])) {
                    $grade = $grades[$userid];
                }
                unset($grades);
            }
            unset($grade_item);
        }

        switch ($rule) {
                case 'completionmingrade':
                    // decimal (e.g. completionmingrade) fields are returned by MySQL as a string
                    // and since empty('0.0') returns false (!!), so we must use numeric comparison
                    if (empty($ec->completionmingrade) || floatval($ec->completionmingrade)==0.0) {
                        $state=true;
                        break;
                    }

                    $state = ($grade && $grade->finalgrade >= $ec->completionmingrade);
                    break;
                case 'completionpass':
                    $state = ($grade && $grade->is_passed());
                    break;
                case 'completiongoals':
                    // if goals have been set up, calculate total percent
                    $progress = $ec->get_progress();

                    if ($goals = ($ec->watchgoal + $ec->learngoal + $ec->speakgoal)) {
                        $state = 0;
                        $state += max(0, min($progress->watch, $ec->watchgoal));
                        $state += max(0, min($progress->learn, $ec->learngoal));
                        $state += max(0, min($progress->speak, $ec->speakgoal));
                        $state = (round(100 * $state / $goals, 0) >= 100);
                    } else {
                        $state = false; // unusual - no goals have been set up !!
                    }
                    break;
        }
        return $state ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completionmingrade',
            'completionpass',
            'completiongoals',
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $completionmingrade = $this->cm->customdata['customcompletionrules']['completionmingrade'] ?? 0;
        $completionpass = $this->cm->customdata['customcompletionrules']['completionpass'] ?? 0;
        $completiongoals = $this->cm->customdata['customcompletionrules']['completiongoals'] ?? 0;

        return [
            'completionmingrade' => get_string('completiondetail:mingrade', 'englishcentral', $completionmingrade),
            'completionpass' => get_string('completiondetail:pass', 'englishcentral'),
            'completiongoals' => get_string('completiondetail:goals', 'englishcentral'),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionmingrade',
            'completionpass',
            'completiongoals',
            'completionusegrade',
        ];
    }
}
