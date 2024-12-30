<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:52
 */

namespace mod_englishcentral\report;

use \mod_englishcentral\constants;
use \mod_englishcentral\utils;

class usercourseattempts extends basereport {

    protected $report = "usercourseattempts";

    protected $fields = ['activityname', 'total_p', 'watch', 'learn', 'speak', 'chat' ,  'firstattempt'];
    protected $formdata = null;
    protected $qcache = [];
    protected $ucache = [];

    public function fetch_formatted_field($field, $record, $withlinks) {
        global $DB, $CFG, $OUTPUT;

        switch ($field) {

            case 'activityname':
                $ec = $this->fetch_cache(constants::M_TABLE, $record->ecid);
                $ret = $record->name;
                if ($withlinks) {
                        $link = new \moodle_url(constants::M_URL . '/reports.php',
                                ['format' => $this->formdata->format, 'report' => 'userattempts',
                                 'id' => $this->cm->id, 'userid' => $this->formdata->userid]);
                        $ret = \html_writer::link($link, $ret);
                }
                break;

            // Not necessary here . Since Watch = the same details.
            case 'attempts':
                    $ret = $record->attemptcount;
                    break;

            case 'watch':
                $watchgoal = intval($record->watchgoal);
                if ($watchgoal > 0) {
                    $ret = $record->watch . '/' . $watchgoal;
                } else {
                    $ret = $record->watch;
                }
                break;

            case 'learn':
                $learngoal = intval($record->learngoal);
                if ($learngoal > 0) {
                    $ret = $record->learn . '/' . $learngoal;
                } else {
                    $ret = $record->learn;
                }
                break;

            case 'speak':
                $speakgoal = intval($record->speakgoal);
                if ($speakgoal > 0) {
                    $ret = $record->speak . '/' . $speakgoal;
                } else {
                    $ret = $record->speak;
                }
                break;

            case 'chat':
                if (get_config(constants::M_COMPONENT, 'chatmode') ||
                    intval($record->chat) > 0) {
                        $chatgoal = intval($record->chatgoal);
                        if ($chatgoal > 0) {
                            $ret = $record->chat . '/' . $chatgoal;
                        } else {
                            $ret = $record->chat;
                        }
                } else {
                    $ret = '-';
                }
                break;

            case 'total_p':
                $ret = $record->total_p . "% (" . $record->total .")";
                break;

            case 'firstattempt':
                $ret = date("Y-m-d H:i:s", $record->firstattempt);
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
        $thecourse = $this->fetch_cache('course', $record->course);
        $theuser = $this->fetch_cache('user', $record->userid);
        $a = new \stdClass();
        $a->username = fullname($theuser);
        $a->coursename = $thecourse->fullname;
        return get_string('usercourseattemptsheading', constants::M_COMPONENT, $a);
    }

    public function fetch_chart($renderer, $showdatasource = true) {
        global $CFG;
        $CFG->chart_colorset = ['#ceb9df', '#a9dbef', '#f7c1a1', '#d3e9af'];
        $records = $this->rawdata;
        // Build the series data.
        $watchseries = [];
        $learnseries = [];
        $speakseries = [];
        $chatseries = [];
        $activitynames = [];
        foreach ($records as $record) {
            $watchseries[] = $record->watch_p;
            $learnseries[] = $record->learn_p;
            $speakseries[] = $record->speak_p;
            $chatseries[] = $record->chat_p;
            $activitynames[] = $record->name;
        }

        // Display the chart.
        $chart = new \core\chart_bar();
        $chart->set_horizontal(false);
        $chart->set_stacked(false);
    //    $yzeroaxis = $chart->get_yaxis(0, true);
        $yaxis = $chart->get_yaxis(0, true);
        $yaxis->set_stepsize(10);
        $yaxis->set_min(0);
        $yaxis->set_max(100);

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
        $chart->set_labels($activitynames);

        $thechart = $renderer->render_chart($chart, $showdatasource);
        return '<div class="mod_ec_chartcontainer">' .
            $thechart . '</div>';
    }

    public function process_raw_data($formdata) {
        global $DB, $USER;

        // Save form data for later.
        $this->formdata = $formdata;

        $emptydata = [];

        // Now lets build our SQL.
        $selectsql = 'SELECT tu.ecid , SUM(COALESCE(watchcomplete, 0)) + ' .
          'SUM(COALESCE(learncount, 0)) + ' .
          'SUM(COALESCE(speakcount, 0)) + ' .
          'SUM(COALESCE(chatcount, 0)) AS total,'.
          'SUM(COALESCE(watchcomplete, 0)) AS watch,'.
          'SUM(COALESCE(learncount, 0)) AS learn,'.
          'SUM(COALESCE(speakcount, 0)) AS speak,'.
          'SUM(COALESCE(chatcount, 0)) AS chat,' .
          'MIN(tu.timecreated) AS firstattempt, ' .
          'ec.name, ' .
          'ec.watchgoal, ' .
          'ec.learngoal, ' .
          'ec.speakgoal, ' .
          'ec.chatgoal ' .
          'FROM {' . constants::M_ATTEMPTSTABLE . '} tu '.
          'INNER JOIN {' . constants::M_TABLE . '} ec ' .
          'ON ec.id = tu.ecid ';

        $alldatasql = $selectsql . " WHERE ec.course = ? AND tu.userid = ? ";
        $allparams = ['course' => $formdata->course, 'userid' => $formdata->userid];

        // Days limit WHERE condition.
        if ($formdata->dayslimit > 0) {
            // Calculate the unix timestamp X days ago.
            // 86400 = 24 hours * 60 minutes * 60 seconds.
            $dayslimit = time() - ($formdata->dayslimit * 86400);
            $dayslimitcondition = " AND tu.timecreated >= ?";
            $alldatasql .= $dayslimitcondition;
            $allparams['dayslimit'] = $dayslimit;
        }

        // Add a 'group by' clause to SQL
        $alldatasql .= "GROUP BY tu.ecid";

        // Use the SQL to fetch the data.
        $alldata = $DB->get_records_sql($alldatasql, $allparams);

        // Here we manually tweak the data, in this case to use points and goals to create percents.
        if ($alldata) {
            foreach ($alldata as $thedata) {

                // Get the goals for each ec activity returned.
                $goals = ['watch' => 0, 'learn' => 0, 'speak' => 0, 'chat' => 0, 'total' => 0];
                if ($thedata->watchgoal +
                $thedata->learngoal +
                $thedata->speakgoal +
                $thedata->chatgoal) {
                    $goals['watch'] = intval($thedata->watchgoal);
                    $goals['learn'] = intval($thedata->learngoal);
                    $goals['speak'] = intval($thedata->speakgoal);
                    $goals['chat'] = intval($thedata->chatgoal);
                }
                $goals['total'] = $goals['watch'] + $goals['learn'] + $goals['speak'] + $goals['chat'];

                // Add a percentage field for each pointfield and add the goal to the display
                //eg learn = 6 becomes learn = 6/8  learn_p = 75%
                $totalpoints = 0;
                foreach ($goals as $goalfield => $goalvalue) {
                    if ($goalfield == 'total') { continue; }
                    $pointsvalue = $thedata->{$goalfield};
                    // We need to adjust the pointvalue so its not higher than goalvalue (eg they spoke 6 lines, but goal was 2).
                    if ($pointsvalue > $goalvalue && $goalvalue > 0) {$pointsvalue = $goalvalue;}
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
