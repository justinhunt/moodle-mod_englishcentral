<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:52
 */

namespace mod_englishcentral\report;

use mod_englishcentral\constants;
use mod_englishcentral\utils;


class videoperformance extends basereport {

    protected $report = "videoperformance";
    protected $fields = ['videoid', 'videoname', 'difficulty', 'totalwatches', 'averagelearn', 'averagespeak', 'averagechat'];
    protected $formdata = null;
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
                if (!empty($record->detailsjson) && utils::is_json($record->detailsjson)) {
                    $details = json_decode($record->detailsjson);
                    if (isset($details->thumbnailURL)) {
                        $ret .= '<br/>' . \html_writer::img($details->thumbnailURL, '$record->videoname');
                    }
                }
                break;

            case 'difficulty':
                $ret = '-';
                if (!empty($record->detailsjson) && utils::is_json($record->detailsjson)) {
                    $details = json_decode($record->detailsjson);
                    if (isset($details->difficulty)) {
                        $ret = $details->difficulty;
                    }
                }
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
                if (get_config(constants::M_COMPONENT, 'chatmode') ||
                    intval($record->averagechat) > 0) {
                    $ret = $record->averagechat;
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
        return get_string('videoperformanceheading', constants::M_COMPONENT, $ec->name);
    }

    public function fetch_chart($renderer, $showdatasource = true) {
        global $CFG;
        $records = $this->rawdata;

        // Build the series data.
        $videoseries = [];
        $videonames = [];
        foreach ($records as $record) {
            $videoseries[] = $record->totalwatches;
            $videonames[] = $record->videoname;
        }

        // Display the chart
        $chart = new \core\chart_pie();
        $chart->set_doughnut(true); // Calling set_doughnut(true) we display the chart as a doughnut.
        $chart->add_series( new \core\chart_series('My series title', $videoseries));
        $chart->set_labels($videonames);
        $thechart = $renderer->render_chart($chart, $showdatasource);
        return $thechart;
    }

    public function process_raw_data($formdata) {
        global $DB, $USER;

        // Save form data for later.
        $this->formdata = $formdata;

        $this->rawdata = [];
        $emptydata = [];

        $selectsql = 'SELECT vid.videoid as videoid, vid.name as videoname, vid.detailsjson, COUNT(watchcomplete) as totalwatches,'.
        'ROUND(AVG(COALESCE(learncount, 0)),1) AS averagelearn,'.
        'ROUND(AVG(COALESCE(speakcount, 0)),1) AS averagespeak,'.
        'ROUND(AVG(COALESCE(chatcount, 0)),1) AS averagechat ' .
        ' FROM {' . constants::M_ATTEMPTSTABLE . '} tu ';

        $selectsql .= 'INNER JOIN {' . constants::M_VIDEOSTABLE . '} vid ';
        $selectsql .= 'ON (tu.ecid = vid.ecid) and (tu.videoid = vid.videoid) ';
        $selectsql .= 'WHERE tu.ecid = ? ';
        $allparams = ['ecid' => $formdata->ecid];

        // Days limit WHERE condition.
        if ($formdata->dayslimit > 0) {
            // Calculate the unix timestamp X days ago.
            // 86400 = 24 hours * 60 minutes * 60 seconds.
            $dayslimit = time() - ($formdata->dayslimit * 86400);
            $dayslimitcondition = " AND timecreated >= ?";
            $selectsql .= $dayslimitcondition;
            $allparams['dayslimit'] = $dayslimit;
        }

        // GROUP BY .
        $selectsql .= 'GROUP BY vid.id, vid.name ';

        // Run the SQL.
        $alldata = $DB->get_records_sql($selectsql, $allparams);

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