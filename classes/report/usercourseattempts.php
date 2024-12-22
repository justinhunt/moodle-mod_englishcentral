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

    protected $fields = ['name', 'total_p', 'watch', 'learn', 'speak', 'chat' ,  'firstattempt'];
    protected $headingdata = null;
    protected $qcache = [];
    protected $ucache = [];

    public function fetch_formatted_field($field, $record, $withlinks) {
        global $DB, $CFG, $OUTPUT;
        switch ($field) {

            case 'name':
                $ec = $this->fetch_cache(constants::M_TABLE, $record->ecid);
                $ret = $record->name;
                if ($withlinks) {
                        $link = new \moodle_url(constants::M_URL . '/reports.php',
                                ['format' => 'html', 'report' => 'userattempts', 'id' => $this->cm->id, 'userid' => $this->headingdata->userid]);
                        $ret = \html_writer::link($link, $ret);
                }
                break;

            // Not necessary here . Since Watch = the same details  
            case 'attempts':
                    $ret = $record->attemptcount;
                    break;

            case 'chat':
                if (get_config(constants::M_COMPONENT, 'chatmode_enabled')) {
                    $ret = $record->chat;
                } else {
                    $ret = '-';
                }

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
        $record = $this->headingdata;
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

    public function process_raw_data($formdata) {
        global $DB, $USER;

        // heading data
        $this->headingdata = new \stdClass();
        $this->headingdata->course = $formdata->course;
        $this->headingdata->userid = $formdata->userid;
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
