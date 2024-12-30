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
    protected $formdata = null;
    protected $qcache = [];
    protected $ucache = [];

    protected $goals = [];

    public function fetch_formatted_field($field, $record, $withlinks) {
        global $DB, $CFG, $OUTPUT;

        switch ($field) {

            case 'username':
                $user = $this->fetch_cache('user', $record->userid);
                $ret = fullname($user);
                if ($withlinks) {
                        $link = new \moodle_url(constants::M_URL . '/reports.php',
                                ['format' => $this->formdata->format, 'report' => 'userattempts',
                                'id' => $this->cm->id, 'userid' => $record->userid, 'dayslimit' => $this->formdata->dayslimit]);
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

            case 'learn':
            case 'speak':
            case 'watch':
                $goalvalue = $this->goals[$field];
                $ret = $record->{$field}  . '/' . $goalvalue;
                break;

            case 'total_p':
                $ret = $record->total_p . "% (" .$record->total.")";
                break;

            case 'chat':
                if (get_config(constants::M_COMPONENT, 'chatmode') ||
                    intval($record->chat) > 0) {
                    $goalvalue = $this->goals[$field];
                    $ret = $record->chat . '/' . $goalvalue;
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
        $record = $this->formdata;
        $ret = '';
        if (!$record) {
            return $ret;
        }
        $ec = $this->fetch_cache(constants::M_TABLE, $record->ecid);
        return get_string('attemptsheading', constants::M_COMPONENT, $ec->name);
    }

    public function fetch_chart($renderer, $showdatasource = true) {
        global $CFG;

        $records = $this->rawdata;
        // Build the series data.
        $watchseries = [];
        $learnseries = [];
        $speakseries = [];
        $chatseries = [];
        $usernames = [];
        foreach ($records as $record) {
            $watchseries[] = $record->watch_p;
            $learnseries[] = $record->learn_p;
            $speakseries[] = $record->speak_p;
            $chatseries[] = $record->chat_p;
            $user = $this->fetch_cache('user', $record->userid);
            $percentstring = $record->total_p . '%';
            $percentstring = str_pad($percentstring,
                4,
                ' ', // doesn't work
                STR_PAD_LEFT);
            $usernames[] = fullname($user) . ' ' . $percentstring;
        }

        // Display the chart.
        $chart = new \core\chart_bar();
        $chart->set_horizontal(true);
        $chart->set_stacked(true);
        $chart->add_series(new \core\chart_series(
            get_string('watch', constants::M_COMPONENT),
             $watchseries));
        $chart->add_series(new \core\chart_series(
            get_string('learn', constants::M_COMPONENT),
             $learnseries));
        $chart->add_series(new \core\chart_series(
            get_string('speak', constants::M_COMPONENT),
             $speakseries));
        if (get_config(constants::M_COMPONENT, 'chatmode')) {
            $chart->add_series(new \core\chart_series(
                get_string('chat', constants::M_COMPONENT),
                $chatseries));
        }
        $chart->set_labels($usernames);
        $thechart = $renderer->render_chart($chart, $showdatasource);
        // We set a height of 40px per "bar.".
        $chartheight = max([count($usernames) * 40, 450]);
        return '<div class="mod_ec_chartcontainer" style="height: ' .
            $chartheight . 'px">' .
            $thechart . '</div>';
    }

    public function process_raw_data($formdata) {
        global $DB, $USER;

        // Save form data for later.
        $this->formdata = $formdata;

        $emptydata = [];

        // Groups stuff
        $moduleinstance = $DB->get_record(constants::M_TABLE, ['id' => $formdata->ecid]);
        $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance(constants::M_TABLE, $moduleinstance->id, $course->id, false, MUST_EXIST);
        $context = empty($cm) ? \context_course::instance($course->id) : \context_module::instance($cm->id);

        // initialize study goals and save them for use later in when displaying data
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
        $this->goals = $goals;

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

        // Days limit WHERE condition
        if ($formdata->dayslimit > 0) {
            // Calculate the unix timestamp X days ago.
            // 86400 = 24 hours * 60 minutes * 60 seconds.
            $dayslimit = time() - ($formdata->dayslimit * 86400);
            $dayslimitcondition = " AND timecreated >= ?";
            $alldatasql .= $dayslimitcondition;
            $allparams['dayslimit'] = $dayslimit;
        }

        // Add a 'group by' clause to SQL
        $alldatasql .= " GROUP BY userid";

        // Use the SQL to fetch the data.
        $alldata = $DB->get_records_sql($alldatasql, $allparams);

        // Here we manually tweak the data, in this case to use points and goals to create percents.
        if ($alldata) {
            foreach ($alldata as $thedata) {
                 // Add a percentage field for each pointfield
                 // eg learn = 6 becomes learn = 6/8  learn_p = 75%
                 // We also recalculate the 'total'
                $totalpoints = 0;
                foreach ($goals as $goalfield => $goalvalue) {
                    if ($goalfield == 'total') { continue; }
                    $pointsvalue = $thedata->{$goalfield};
                    // We need to adjust the pointvalue so its not higher than goalvalue (eg they spoke 6 lines, but goal was 2).
                    if ($pointsvalue > $goalvalue && $goalvalue > 0) {$pointsvalue = $goalvalue;}
                    // If no goal was set  ... we do not calc a percentage.
                    $thedata->{$goalfield . '_p'} = $goalvalue > 0 ? round($pointsvalue / $goalvalue * 100 , 0) : '-';
                    // We recalc the total, using the goal adjusted points value
                    $totalpoints += $pointsvalue;
                }
                $thedata->total = $totalpoints;
                $thedata->total_p = $goals['total'] > 0 ? round($totalpoints / $goals['total'] * 100 , 0) : '-';
                $this->rawdata[] = $thedata;
            }
        } else {
            $this->rawdata = $emptydata;
        }
        return true;
    }

}
