<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:52
 */

namespace mod_englishcentral\report;

use mod_englishcentral\constants;


class videoperformance extends basereport {

    protected $report = "videoperformance";
    protected $fields = ['videoid', 'videoname', 'totalwatches', 'averagelearn', 'averagespeak', 'averagechat'];
    protected $headingdata = null;
    protected $qcache = array();
    protected $ucache = array();

    public function fetch_formatted_field($field, $record, $withlinks) {
        global $DB, $CFG, $OUTPUT;

        switch ($field) {

            case 'videoid':
                $ret = $record->videoid;
                break;

            case 'videoname':
                $ret = $record->videoname;
                break;

            case 'totalwatches':
                $ret = $record->totalwatches;
                break;

            case 'averagelearn':
                $ret = $record->averagelearn;
                break;

            case 'averagespeak':
                    $ret = $record->averagespeak;
                    break;

            case 'averagechat':
                $ret = $record->averagechat;
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
        return get_string('videoperformanceheading', constants::M_COMPONENT, $ec->name);
    }

  

    public function process_raw_data($formdata) {
        global $DB, $USER;

        // heading data
        $this->headingdata = new \stdClass();
        $this->headingdata->ecid = $formdata->ecid;

        $this->rawdata = [];
        $emptydata = [];

        $selectsql = 'SELECT vid.id as videoid, vid.name as videoname , COUNT(watchcomplete) as totalwatches,'.
        'ROUND(AVG(learncount),1) AS averagelearn,'.
        'ROUND(AVG(speakcount),1) AS averagespeak,'.
        'ROUND(AVG(chatcount),1) AS averagechat ' .
        ' FROM {' . constants::M_ATTEMPTSTABLE . '} tu ';

        $selectsql .= 'INNER JOIN {' . constants::M_VIDEOSTABLE . '} vid ';
        $selectsql .= 'ON (tu.ecid = vid.ecid) and (tu.videoid = vid.id) ';
        $selectsql .= 'WHERE tu.ecid = ? ';
        $selectsql .= 'GROUP BY vid.id, vid.name ';
        $params = ['ecid' => $formdata->ecid];

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
    }

}