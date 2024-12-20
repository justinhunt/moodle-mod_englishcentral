<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:52
 */

namespace mod_englishcentral\report;

use \mod_englishcentral\constants;

class courseattempts extends basereport {

    protected $report = "courseattempts";

    protected $fields = ['username', 'activities', 'total', 'watch', 'learn', 'speak', 'chat'];
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
                                ['format' => 'html', 'report' => 'usercourseattempts', 'id' => $this->cm->id, 'userid' => $record->userid]);
                        $ret = \html_writer::link($link, $ret);
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
        $thecourse = $this->fetch_cache('course', $record->course);
        return get_string('courseattemptsheading', constants::M_COMPONENT, $thecourse->fullname);

    }

    public function process_raw_data($formdata) {
        global $DB, $USER;

        // heading data
        $this->headingdata = new \stdClass();
        $this->headingdata->course = $formdata->course;
        $emptydata = [];
        $allparams = [];

        // Now lets build our SQL.
        $selectsql = 'SELECT tu.userid , COUNT(DISTINCT(ec.id)) AS activities, SUM(watchcomplete) + SUM(learncount) + 
                        SUM(speakcount) + SUM(chatcount) AS total,'.
          'SUM(watchcomplete) AS watch,'.
          'SUM(learncount) AS learn,'.
          'SUM(speakcount) AS speak,'.
          'SUM(chatcount) AS chat ' .
          'FROM {' . constants::M_ATTEMPTSTABLE . '} tu ' .
          'INNER JOIN {' . constants::M_TABLE . '} ec ' .
          'ON ec.id = tu.ecid ';

        // if we need to show  groups
        if ($formdata->groupid > 0) {
            list($groupswhere, $allparams) = $DB->get_in_or_equal($formdata->groupid);

            $alldatasql = $selectsql .
                    " INNER JOIN {groups_members} gm ON tu.userid=gm.userid " .
                    " WHERE gm.groupid $groupswhere AND ec.course = ?";
            $allparams[] = $formdata->course;

        // If we don't need to show groups.
        } else {
            $alldatasql = $selectsql . " WHERE ec.course = ?";
            $allparams['course'] = $formdata->course;
        }

        // Add a 'group by' clause to SQL
        $alldatasql .= " GROUP BY userid";

        // Use the SQL to fetch the data.
        $alldata = $DB->get_records_sql($alldatasql, $allparams);

        // Here we manually tweak the data, in this case to use points and goals to create percents.
        if ($alldata) {
            foreach ($alldata as $thedata) {
                $this->rawdata[] = $thedata;
            }
        } else {
            $this->rawdata = $emptydata;
        }
        return true;
    }
}
