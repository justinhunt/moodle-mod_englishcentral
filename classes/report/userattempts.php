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

class userattempts extends basereport {

    protected $report = "userattempts";
    protected $fields = ['videoid', 'videoname', 'learn', 'speak', 'chat', 'activetime', 'totaltime', 'timecreated', 'timecompleted'];
    protected $headingdata = null;
    protected $qcache = [];
    protected $ucache = [];

    public function fetch_formatted_heading() {
        $record = $this->headingdata;
        $ret = '';
        if (!$record) {
            return $ret;
        }
        $user = $this->fetch_cache('user', $record->userid);
        $ec = $this->fetch_cache(constants::M_TABLE, $record->ecid);
        $a = new \stdClass();
        $a->username = fullname($user);
        $a->activityname = $ec->name;

        return get_string('userattemptsheading', constants::M_COMPONENT, $a);

    }

    public function fetch_formatted_field($field, $record, $withlinks) {
        global $DB, $CFG, $OUTPUT;

        switch ($field) {
            case 'videoid':
                $ret = $record->videoid;
                break;

            case 'videoname':
                if ($withlinks && !empty($record->videoname)) {
                    $link = new \moodle_url(constants::M_URL . '/reports.php',
                            ['format' => 'html', 'report' => 'videoperformance', 'id' => $this->cm->id, 'videoid' => $record->videoid]);
                    $ret = \html_writer::link($link, $record->videoname);
                    if (!empty($record->detailsjson) && utils::is_json($record->detailsjson)) {
                        $details = json_decode($record->detailsjson);
                        if (isset($details->thumbnailURL)) {
                            $ret .= '<br/>' . \html_writer::img($details->thumbnailURL, '$record->videoname');
                        }
                    }
                } else {
                    if (empty($record->videoname)) {
                        $ret = get_string('deletedvideo', constants::M_COMPONENT);
                    } else {
                        $ret = $record->videoname;
                    }
                }
                break;

            case 'watch':
                $ret = $record->watchcount;
                break;

            case 'learn':
                $ret = $record->learncount;
                break;

            case 'speak':
                $ret = $record->speakcount;
                break;

            case 'chat':
                if (get_config(constants::M_COMPONENT, 'chatmode_enabled') ||
                intval($record->chatcount) > 0) {
                    $ret = $record->chatcount;
                } else {
                    $ret = '-';
                }
                break;

            case 'activetime':
                $ret = $record->activetime;
                break;

            case 'totaltime':
                $ret = $record->totaltime;
                break;

            case 'timecreated':
                $ret = date("Y-m-d H:i:s", $record->timecreated);
                break;

            case 'timecompleted':
                $ret = date("Y-m-d H:i:s", $record->timecompleted);
                break;

            default:
                if (property_exists($record, $field)) {
                    $ret = $record->{$field};
                } else {
                    $ret = '';
                }
        }
        return $ret;

    } //end of function

    public function process_raw_data($formdata) {
        global $DB;

        // heading data
        $this->headingdata = new \stdClass();
        $this->headingdata->userid = $formdata->userid;
        $this->headingdata->ecid = $formdata->ecid;

        $this->rawdata = [];
        $emptydata = [];

        $selectsql = 'SELECT tu.*, vid.name as videoname, vid.detailsjson FROM {' . constants::M_ATTEMPTSTABLE . '} tu ';
        $selectsql .= 'LEFT OUTER JOIN {' . constants::M_VIDEOSTABLE . '} vid ';
        $selectsql .= 'ON (tu.ecid = vid.ecid) AND (tu.videoid = vid.videoid) ';
        $selectsql .= 'WHERE tu.ecid =? AND tu.userid = ?';
        $params = ['ecid' => $formdata->ecid, 'userid' => $formdata->userid];

        // Run the SQL.
        $alldata = $DB->get_records_sql($selectsql, $params);

        if ($alldata) {
            foreach ($alldata as $thedata) {
                // Do any data massaging here.
                $this->rawdata[] = $thedata;
            }
        } else {
            $this->rawdata = $emptydata;
        }
        return true;
    }//end of function
}
