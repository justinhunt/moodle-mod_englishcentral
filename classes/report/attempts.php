<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:52
 */

namespace mod_englishcentral\report;

use mod_englishcentral\constants;
use mod_englishcentral\utils;

class attempts extends basereport {

    protected $report = "attempts";
    protected $fields = ['username', 'total_p', 'watch', 'learn', 'speak', 'chat' , 'firstattempt'];
    protected $headingdata = null;
    protected $qcache = [];
    protected $ucache = [];

    public function fetch_formatted_field($field, $record, $withlinks) {
        global $DB, $CFG, $OUTPUT;
        switch ($field) {

            case 'username':
                $user = $this->fetch_cache('user', $record->userid);
                $ret = fullname($user);
                if ($withlinks) {
                        $link = new \moodle_url(constants::M_URL . '/reports.php',
                                ['format' => 'html', 'report' => 'userattempts', 'id' => $this->cm->id, 'userid' => $record->userid]);
                        $ret = \html_writer::link($link, $ret);
                }
                break;

            // Not necessary here . Since Watch = the same details  
            case 'attempts':
                    $ret = $record->attemptcount;
                    break;

            case 'firstattempt':
                $ret = date("Y-m-d H:i:s", $record->firstattempt);
                break;

            case 'chat':
                if (get_config(constants::M_COMPONENT, 'chatmode_enabled') ||
                    intval($record->chat) > 0) {
                    $ret = $record->chat;
                } else {
                    $ret = '-';
                }
                break;

            default:
                if (property_exists($record, $field)) {
                    $ret = $record->{$field};
                } else {
                    $ret = '';
                }
        }
        return $ret;
    }

    public function fetch_formatted_heading() {
        $record = $this->headingdata;
        $ret = '';
        if (!$record) {
            return $ret;
        }
        $ec = $this->fetch_cache(constants::M_TABLE, $record->ecid);
        return get_string('attemptsheading', constants::M_COMPONENT, $ec->name);

    }

    public function process_raw_data($formdata) {
        global $DB, $USER;

        // heading data
        $this->headingdata = new \stdClass();
        $this->headingdata->ecid = $formdata->ecid;
        $emptydata = [];

        // Groups stuff
        $moduleinstance = $DB->get_record(constants::M_TABLE, ['id' => $formdata->ecid]);
        $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance(constants::M_TABLE, $moduleinstance->id, $course->id, false, MUST_EXIST);
        $context = empty($cm) ? \context_course::instance($course->id) : \context_module::instance($cm->id);

        // Before we do SQL stuff lets get the teacher specified goals.
        // initialize study goals
        $goals = ['watch' => 0, 'learn' => 0, 'speak' => 0, 'chat' => 0, 'total' => 0];
        if ($moduleinstance->watchgoal +
                $moduleinstance->learngoal +
                $moduleinstance->speakgoal +
                $moduleinstance->chatgoal) {
            $goals['watch'] = intval($moduleinstance->watchgoal);
            $goals['learn'] = intval($moduleinstance->learngoal);
            $goals['speak'] = intval($moduleinstance->speakgoal);
            $goals['chat'] = intval($moduleinstance->chatgoal);
        }
        $goals['total'] = $goals['watch'] + $goals['learn'] + $goals['speak'] + $goals['chat'];

        // Now lets build our SQL.
        // We use COALESCE because the chatcount could contain nulls, and if ALL fields are NULL postgresql SUM returns null
        // The other fields may not need COALESCE but, just in case, we added it to them too
        $selectsql = 'SELECT tu.userid , SUM(COALESCE(watchcomplete, 0)) + ' .
          'SUM(COALESCE(learncount, 0)) + ' .
          'SUM(COALESCE(speakcount, 0)) + ' .
          'SUM(COALESCE(chatcount, 0)) AS total,'.
          'SUM(COALESCE(watchcomplete, 0)) AS watch,'.
          'SUM(COALESCE(learncount, 0)) AS learn,'.
          'SUM(COALESCE(speakcount, 0)) AS speak,'.
          'SUM(COALESCE(chatcount, 0)) AS chat,' .
          'COUNT(id) AS attemptcount, ' .
          'MIN(timecreated) AS firstattempt ' .
          ' FROM {' . constants::M_ATTEMPTSTABLE . '} tu ';

        // if we need to show  groups
        if ($formdata->groupid > 0) {
            list($groupswhere, $allparams) = $DB->get_in_or_equal($formdata->groupid);

            $alldatasql = $selectsql .
                    " INNER JOIN {groups_members} gm ON tu.userid=gm.userid " .
                    " WHERE gm.groupid $groupswhere AND tu.ecid = ?";
            $allparams[] = $formdata->ecid;

        // If we don't need to show groups.
        } else {
            $alldatasql = $selectsql . " WHERE tu.ecid = ?";
            $allparams = ['ecid' => $formdata->ecid];
        }

        // Add a 'group by' clause to SQL
        $alldatasql .= " GROUP BY userid";

        // Use the SQL to fetch the data.
        $alldata = $DB->get_records_sql($alldatasql, $allparams);

        // Here we manually tweak the data, in this case to use points and goals to create percents.
        if ($alldata) {
            foreach ($alldata as $thedata) {
                // Add a percentage field for each pointfield and add the goal to the display
                //eg learn = 5 becomes learn = 6/8  learn_p = 75%(6)
                foreach ($goals as $goalfield => $goalvalue) {
                    $thedata->{$goalfield . '_p'} = $goalvalue > 0 ? round($thedata->{$goalfield} / $goalvalue * 100 , 0) : '-';
                    if ($thedata->{$goalfield . '_p'} > 100) {$thedata->{$goalfield . '_p'} = 100;}
                    $thedata->{$goalfield . '_p'} .= "% (" .$thedata->{$goalfield}.")";
                    $thedata->{$goalfield} = $thedata->{$goalfield} . '/' . $goalvalue;
                }
                $this->rawdata[] = $thedata;
            }
        } else {
            $this->rawdata = $emptydata;
        }
        return true;
    }

}
