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

class attemptssummary extends basereport
{

    protected $report = "attemptssummary";
    protected $fields = ['firstname', 'lastname', 'total_p', 'watch', 'learn', 'speak', 'chat'];
    protected $formdata = null;
    protected $qcache = [];
    protected $ucache = [];
    protected $goals = null;
    protected $sort = 'firstname';
    protected $order = 'ASC';

    protected $ec = null;

    public function fetch_formatted_field($field, $record, $withlinks)
    {
        global $DB, $CFG, $OUTPUT;

        switch ($field) {

            case 'firstname':
            case 'lastname':
                if ($withlinks) {
                    $link = new \moodle_url(
                        constants::M_URL . '/reports.php',
                        [
                            'format' => $this->formdata->format,
                            'report' => 'userattempts',
                            'id' => $this->cm->id,
                            'userid' => $record->userid,
                            'dayslimit' => $this->formdata->dayslimit
                        ]
                    );
                    $ret = \html_writer::link($link, $record->{$field});
                } else {
                    $ret = $record->{$field};
                }
                break;

            case 'learn':
            case 'speak':
            case 'watch':
                $goalvalue = $this->goals->{$field};
                $ret = $record->{$field}  . '/' . $goalvalue;
                break;

            case 'total_p':
                $ret = $record->percent . "%";
                break;

            case 'chat':
                if (
                    get_config(constants::M_COMPONENT, 'chatmode') ||
                    intval($record->chat) > 0
                ) {
                    $goalvalue = $this->goals->chat;
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

    public function fetch_formatted_heading()
    {
        $record = $this->formdata;
        $ret = '';
        if (!$record) {
            return $ret;
        }
        $ec = $this->fetch_cache(constants::M_TABLE, $record->ecid);
        return get_string('attemptssummaryheading', constants::M_COMPONENT, $ec->name);
    }

    public function fetch_chart($renderer, $showdatasource = true)
    {
        global $PAGE, $CFG;
        $PAGE->requires->js_call_amd($this->ec->plugin . "/report", 'init');
        $items = $this->rawdata;
        $output = '';
        $url = $PAGE->url;
        $type = 'firstname';
        $fullname = get_string($type, 'moodle');
        $fullname .= $this->get_sort_icon($url, $type);

        $fullname .= ' ';

        $type = 'lastname';
        $fullname .= get_string('lastname', 'moodle');
        $fullname .= $this->get_sort_icon($url, $type);
        $fullname = \html_writer::tag('span', $fullname, array('class' => 'fullname'));

        $type = 'percent';
        $percent = '%'; // get_string($type, 'grades');
        $percent .= $this->get_sort_icon($url, $type);
        $percent = \html_writer::tag('span', $percent, array('class' => 'percent'));

        $output .= \html_writer::tag('dt', $fullname . $percent, array('class' => 'user title'));

        $title = '';
        $left = 0;
        foreach (array('watch', 'learn', 'speak', 'chat') as $type) {
            if ($this->goals->$type) {
                $text = $this->ec->get_string($type . 'goal');
                $sort = $this->get_sort_icon($url, $type);
                $percent = (100 * min(1, $this->goals->$type / $this->goals->total));
                $style = "margin-left: $left%; width: $percent%;";
                $params = array('class' => $type, 'style' => $style);
                $title .= \html_writer::tag('span', $text . ' ' . $sort, $params);
                $left += $percent;
            }
        }
        $output .= \html_writer::tag('dd', $title, array('class' => 'bars title'));

        if ($this->sort == 'percent') {
            uasort($items, array($this, 'uasort_percent'));
        }

        foreach ($items as $userid => $item) {
            $output .= $this->show_progress_report_item($item, $this->goals);
        }

        if (count($items)) {
            $output = \html_writer::tag('dl', $output, array('class' => 'userbars'));
        } else {
            $output = \html_writer::tag('p', $this->ec->get_string('noprogressreport'));
        }

         // We need it to be under page-mod-englishcentral-report for the css styles to apply.
         return \html_writer::div($output, 'page-mod-englishcentral-report', ['id' => 'page-mod-englishcentral-report']);

    }

    /**
     * Set the sort item/order
     */
    protected function setup_sort()
    {
        global $SESSION;

        // initialize session info
        if (empty($SESSION->englishcentral)) {
            $SESSION->englishcentral = new \stdClass();
            $SESSION->englishcentral->sort = '';
            $SESSION->englishcentral->order = '';
        }

        // override sort item/order with incoming data
        $sort = optional_param('sort', '', PARAM_ALPHA);
        switch (true) {

            case ($sort == ''):
                $sort = $SESSION->englishcentral->sort;
                $order = $SESSION->englishcentral->order;
                break;

            case ($sort == $SESSION->englishcentral->sort):
                $order = optional_param('order', '', PARAM_ALPHA);
                break;

            default:
                $order = '';
        }

        if ($sort == '') {
            $sort = 'lastname';
            $order = '';
        }

        if ($order == '') {
            if ($sort == 'firstname' || $sort == 'lastname') {
                $order = 'ASC';
            } else {
                $order = 'DESC';
            }
        }

        // store new/updated sort item/order
        $this->sort = $SESSION->englishcentral->sort = $sort;
        $this->order = $SESSION->englishcentral->order = $order;
    }

    protected function get_sort_icon($url, $sort)
    {
        global $OUTPUT;

        if ($sort == $this->sort) {
            $order = $this->order;
        } else {
            $order = ''; // unsorted
        }

        switch (true) {
            case ($order == 'ASC'):
                $text = 'sortdesc';
                $icon = 't/sort_asc';
                break;
            case ($order == 'DESC'):
                $text = 'sortasc';
                $icon = 't/sort_desc';
                break;
            case ($sort == 'firstname'):
            case ($sort == 'lastname'):
                $text = "sortby$sort";
                $icon = 't/sort';
            default:
                $text = 'sort';
                $icon = 't/sort';
                break;
        }

        $params = [];
        if ($sort) {
            $params['sort'] = $sort;
        } else {
            $url->remove_params('sort');
        }
        if ($order) {
            $params['order'] = ($order == 'ASC' ? 'DESC' : 'ASC');
        } else {
            $url->remove_params('order');
        }
        if (count($params)) {
            $url->params($params);
        }

        $text = get_string($text, 'grades');
        $params = ['class' => 'sorticon'];
        $icon = $OUTPUT->pix_icon($icon, $text, 'moodle', $params);

        return \html_writer::link($url, $icon, ['title' => $text]);
    }

    protected function uasort_percent($a, $b)
    {
        $anum = intval($a->percent);
        $bnum = intval($b->percent);
        if ($anum > $bnum) {
            return ($this->order == 'ASC' ? 1 : -1);
        }
        if ($anum < $bnum) {
            return ($this->order == 'ASC' ? -1 : 1);
        }
        return 0;
    }

    protected function show_progress_report_item($item, $goals)
    {
        $output = '';
        $output .= \html_writer::tag('dt', $this->show_progress_report_user($item, $goals), array('class' => 'user'));
        $output .= \html_writer::tag('dd', $this->show_progress_report_bars($item, $goals), array('class' => 'bars'));
        return $output;
    }

    protected function show_progress_report_user($item, $goals)
    {
        $output = '';
        $output .= \html_writer::tag('span', fullname($item), array('class' => 'fullname'));
        $output .= \html_writer::tag('span', $item->percent . '%', array('class' => 'percent'));
        return $output;
    }

    protected function show_progress_report_bars($item, $goals)
    {
        $output = '';
        $output .= $this->show_progress_report_bar($item, $goals, 'watch');
        $output .= $this->show_progress_report_bar($item, $goals, 'learn');
        $output .= $this->show_progress_report_bar($item, $goals, 'speak');
        $output .= $this->show_progress_report_bar($item, $goals, 'chat');
        return $output;
    }

    protected function show_progress_report_bar($item, $goals, $type)
    {
        if (empty($this->goals->$type)) {
            return '';
        }

        $text = $item->$type . ' / ' . $this->goals->$type;
        switch ($type) {
            case 'watch':
                $title = $this->ec->get_string('watchvideos', $text);
                break;
            case 'learn':
                $title = $this->ec->get_string('learnwords', $text);
                break;
            case 'speak':
                $title = $this->ec->get_string('speaklines', $text);
                break;
            case 'chat':
                $title = $this->ec->get_string('chatquestions', $text);
                break;
        }
        $text = \html_writer::tag('span', $text, array('class' => 'text', 'title' => $title));

        if (empty($item->$type)) {
            $bar = '';
        } else {
            $value = min($item->$type, $this->goals->$type);
            $width = (100 * min(1, $value / $this->goals->$type)) . '%;';
            $params = array('class' => 'bar', 'style' => 'width: ' . $width);
            $bar = \html_writer::tag('span', '', $params);
        }

        $width = (100 * min(1, $this->goals->$type / $this->goals->total)) . '%';
        $params = array('class' => $type, 'style' => 'width: ' . $width);

        return \html_writer::tag('span', $bar . $text, $params);
    }

    public function process_raw_data($formdata)
    {
        global $CFG, $DB, $USER;

        // Save form data for later.
        $this->formdata = $formdata;

        // Set up sort.
        $this->setup_sort();

        // Init empty data.
        $emptydata = [];

        // Groups stuff
        $moduleinstance = $DB->get_record(constants::M_TABLE, ['id' => $formdata->ecid]);
        $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance(constants::M_TABLE, $moduleinstance->id, $course->id, false, MUST_EXIST);
        $context = empty($cm) ? \context_course::instance($course->id) : \context_module::instance($cm->id);
        $ec = \mod_englishcentral\activity::create($moduleinstance, $cm, $course, $context);
        $this->ec = $ec;

        // initialize study goals
        $goals = (object) array(
            'watch' => 0,
            'learn' => 0,
            'speak' => 0,
            'chat' => 0
        );

        // Create SQL to fetch aggregate items from the EC attempts table.
        $select = 'userid,' .
            'SUM(watchcomplete) + SUM(learncount) + SUM(speakcount) + SUM(chatcount) AS percent,' .
            'SUM(watchcomplete) AS watch,' .
            'SUM(learncount) AS learn,' .
            'SUM(speakcount) AS speak,' .
            'SUM(chatcount) AS chat';
        $from = '{englishcentral_attempts}';
        $where = 'ecid = ?';
        $params = [$formdata->ecid];

        // Days limit WHERE condition.
        if ($formdata->dayslimit > 0) {
            // Calculate the unix timestamp X days ago.
            // 86400 = 24 hours * 60 minutes * 60 seconds.
            $dayslimitinseconds = time() - ($formdata->dayslimit * 86400);
            $dayslimitcondition = " AND timecreated >= ?";
            $where .= $dayslimitcondition;
            $params['dayslimit'] = $dayslimitinseconds;
        }

        if ($formdata->groupid) {
            $where .= ' AND userid IN (SELECT gm.userid FROM {groups_members} gm WHERE gm.groupid = ?)';
            $params[] = $formdata->groupid;
        }
        $where = "$where GROUP BY userid";

        $from = "(SELECT $select FROM $from WHERE $where) items," .
            '{user} u';
        $where = 'items.userid = u.id';

        // Get_all_user_name_fields deprecated in 3.11.
        if ($CFG->version < 2021051700) {
            $select = 'items.*,' . get_all_user_name_fields(true, 'u');
        } else {
            $userfields = \core_user\fields::for_name();
            $usersql = $userfields->get_sql('u');
            // Note no concatenating comma, thats how userfields -> selects works.
            $select = 'items.*' . $usersql->selects;
        }

        if ($this->sort == 'firstname' || $this->sort == 'lastname') {
            $order = 'u.' . $this->sort;
        } else {
            $order = 'items.' . $this->sort;
        }
        if ($this->order) {
            $order .= ' ' . $this->order;
        }

        // set goals to maximum in these aggregate items
        if ($items = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $order", $params)) {
            foreach ($items as $userid => $item) {
                $goals->watch = max($goals->watch, $item->watch);
                $goals->learn = max($goals->learn, $item->learn);
                $goals->speak = max($goals->speak, $item->speak);
                $goals->chat = max($goals->chat, $item->chat);
            }
        } else {
            $items = [];
        }

        // Override goals with teacher-specified goals, if available.
        if (
            $moduleinstance->watchgoal + $moduleinstance->learngoal +
            $moduleinstance->speakgoal + $moduleinstance->chatgoal
        ) {
            $goals->watch = intval($moduleinstance->watchgoal);
            $goals->learn = intval($moduleinstance->learngoal);
            $goals->speak = intval($moduleinstance->speakgoal);
            $goals->chat = intval($moduleinstance->chatgoal);
        }

        $goals->total = ($goals->watch +
            $goals->learn +
            $goals->speak +
            $goals->chat);
        $this->goals = $goals;

        // Here we can manually tweak the data,
        if ($items) {
            foreach ($items as $userid => $item) {
                $item->total = (min($this->goals->watch, $item->watch) +
                    min($this->goals->learn, $item->learn) +
                    min($this->goals->speak, $item->speak) +
                    min($this->goals->chat, $item->chat));
                if ($this->goals->total == 0) {
                    $item->percent = '';
                } else {
                    $item->percent = round(100 * min(1, $item->total / $this->goals->total));
                }
                $this->rawdata[$userid] = $item;
            }
        } else {
            $this->rawdata = $emptydata;
        }
        return true;
    }

}
