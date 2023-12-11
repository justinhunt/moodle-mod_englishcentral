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


defined('MOODLE_INTERNAL') || die();

use \mod_englishcentral\constants;
use \mod_englishcentral\utils;


/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package mod_englishcentral
 * @copyright COPYRIGHTNOTICE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_englishcentral_renderer extends plugin_renderer_base {

    protected $ec = null;
    protected $auth = null;

    protected $sort = null;
    protected $order = null;

    const SIGNUP_NONE = 0;
    const SIGNUP_STANDARD = 1;
    const SIGNUP_CORPORATE = 2;
    const SIGNUP_SOLUTIONS = 3;
    const SIGNUP_POODLL    = 4;

    /**
     * attach the $ec & $auth objects so they are accessible throughout this class
     *
     * @param object $ec a \mod_englishcentral/activity Object.
     * @return void
     */
    public function attach_activity_and_auth($ec=null, $auth=null) {
        $this->ec = $ec;
        $this->auth = $auth;
    }

    /**
     * Returns the header for the englishcentral module
     *
     * @param string $extrapagetitle String to append to the page title.
     * @return string
     */
    public function header($extrapagetitle=null,$hidetabs=false) {
        global $CFG;

        if (isset($this->ec->id)) {
            $activityname = format_string($this->ec->name, true, $this->ec->course);
            $title = $this->ec->course->shortname.': '.$activityname;
            if ($extrapagetitle) {
                $title .= ': '.$extrapagetitle;
            }
            $this->page->set_title($title);
            $this->page->set_heading($this->ec->course->fullname);
        }

        $output = $this->output->header();

        if (isset($this->ec->id)) {
            if ((has_capability('mod/englishcentral:manage', $this->ec->context) ||
                    has_capability('mod/englishcentral:viewreports', $this->ec->context)) &&
                    !$hidetabs) {

                //set up tabs
                $moduleinstance =$this->ec;
                ob_start();
                include($CFG->dirroot.'/mod/englishcentral/tabs.php');
                $output .= ob_get_contents();
                ob_end_clean();

                if ($this->page->url == $this->ec->get_view_url()) {
                    $icon = $this->pix_icon('i/report', 'report', 'moodle', array('class'=>'icon'));
                    $icon = html_writer::link($this->ec->get_report_url(), $icon);
                } else if ($this->page->url == $this->ec->get_report_url()) {
                    $icon = $this->pix_icon('i/preview', 'view', 'moodle', array('class'=>'icon'));
                    $icon = html_writer::link($this->ec->get_view_url(), $icon);
                } else {
                    $icon = '';
                }
                //dont show the heading in an iframe, it will be outside this anyway
                if(!$this->ec->foriframe && $CFG->version<4.0) {
                    $help = $this->help_icon('overview', $this->ec->plugin);
                    $output .= $this->heading($activityname.$help.$icon);
                }

            } else {
                if(!$this->ec->foriframe && $CFG->version<4.0) {
                    $output .= $this->output->heading($activityname);
                }
            }
        }
        return $output;
    }


    /**
     * Return HTML to display limited header
     */
    public function notabsheader() {
        return $this->output->header();
    }

    /**
     * Return HTML to display message about missing config settings
     */
    public function show_missingconfig($msg) {
        $output = '';
        $output .= $this->output->box_start('englishcentral_missingconfig');
        $output .= html_writer::tag('p', $this->ec->get_string('missingconfig'));
        $output .= $this->notification(html_writer::alist($msg), 'warning');
        $output .= $this->link_to_config_settings();
        $output .= $this->output->box_end();
        $output .= $this->footer();
        return $output;
    }

    /**
     * Return HTML to display message about missing config settings
     */
    public function show_invalidconfig($msg) {
        $output = '';
        $output .= $this->output->box_start('englishcentral_invalidconfig');
        $output .= html_writer::tag('p', $this->ec->get_string('invalidconfig'));
        $output .= $this->notification($msg, 'warning');
        $output .= $this->link_to_config_settings();
        $output .= $this->output->box_end();
        $output .= $this->footer();
        return $output;
    }

    /**
     * generate link to config settings page
     */
    public function link_to_config_settings() {
        // moodle/site:config, moodle/category:manage
        if (has_capability('moodle/site:config', context_system::instance())) {
            $link = array('section' => 'modsetting'.$this->ec->pluginname);
            $link = new moodle_url('/admin/settings.php', $link);
            $link = html_writer::link($link, get_string('settings'));
            return $this->ec->get_string('updatesettings', $link);
        } else {
            return $this->ec->get_string('consultadmin');
        }
    }

    /**
     * generate link to config settings page
     */
    public function show_support_form() {
        global $CFG, $DB, $USER;

        // available from June 2018
        $signup = self::SIGNUP_POODLL;

        $fullname = fullname($USER);
        $subject = $this->ec->get_string('supportsubject');
        $description = $this->ec->get_string('supportmessage');
        $institution = $DB->get_field('course', 'fullname', array('id' => SITEID));

        $output = '';
        $output .= html_writer::tag('h3', $this->ec->get_string('supporttitle'));
        $output .= html_writer::tag('p', $this->ec->get_string('supportconfirm'));
        $output .= html_writer::start_tag('table', array('class' => 'supportconfirm', 'cellpadding' => 4, 'cellspacing' => 4));
        $output .= html_writer::tag('tr', html_writer::tag('th', get_string('name')).html_writer::tag('td', $fullname));
        $output .= html_writer::tag('tr', html_writer::tag('th', get_string('email')).html_writer::tag('td', $USER->email));

        $url = '';
        $anchor = '';
        $params = array();

        if ($signup==self::SIGNUP_POODLL) {

                $output .= html_writer::tag('tr', html_writer::tag('th', get_string('url')).html_writer::tag('td', $CFG->wwwroot));

                $url = 'https://poodll.com/englishcentral-demo-request/';
                $anchor = '';

                $formid = '5677';
                $postid = '5678';
                $tag = 'wpcf7-f'.$formid.'-p'.$postid.'-o1';
                $params = array('_wpcf7' => $formid,
                                '_wpcf7_unit_tag' => $tag,
                                '_wpcf7_locale' => 'en_US',
                                '_wpcf7_version' => '5.0.5',
                                '_wpcf7_container_post' => $postid,
                                'your-name' => $fullname,
                                'your-email' => $USER->email,
                                'your-subject' => $subject,
                                'moodle-url' => $CFG->wwwroot,
                                'your-message' => $description);
        } else {

            if ($USER->phone1) {
                $output .= html_writer::tag('tr', html_writer::tag('th', get_string('phone1')).html_writer::tag('td', $USER->phone1));
            }
            if ($institution) {
                $output .= html_writer::tag('tr', html_writer::tag('th', get_string('institution')).html_writer::tag('td', $institution));
            }

            if ($signup==self::SIGNUP_STANDARD) {
                $output .= html_writer::tag('tr', html_writer::tag('th', get_string('subject', 'forum')).html_writer::tag('td', $subject));
                $output .= html_writer::tag('tr', html_writer::tag('th', get_string('description')).html_writer::tag('td', $description));

                $url = 'https://www.englishcentral.com/support/contact-school-support';
                $params = array('name' => $fullname,
                                'email' => $USER->email,
                                'phone' => $USER->phone1,
                                'subject' => $subject,
                                'institution' => $institution,
                                'description' => $description,
                                'type' => 'access_code_coupon');
            } else {
                if ($signup==self::SIGNUP_CORPORATE) {
                    $url = 'https://corporate.englishcentral.com/moodle-signup-gordon';
                } else { // self::SIGNUP_SOLUTIONS is default
                    $url = 'https://solutions.englishcentral.com/moodle-signup-gordon';
                }
                $anchor = 'moodle-cta';
                $formid = '11252';
                $postid = '11207';
                $tag = 'wpcf7-f'.$formid.'-p'.$postid.'-o6';
                $params = array('_wpcf7' => $formid,
                                '_wpcf7_unit_tag' => $tag,
                                '_wpcf7_locale' => 'en_US',
                                '_wpcf7_version' => '5.0.3',
                                '_wpcf7_container_post' => $postid,
                                'your-name' => $fullname,
                                'your-email' => $USER->email,
                                'school-name' => $institution,
                                'number-student' => 100,
                                'contact-number' => (empty($USER->phone1) ? '0123456789': $USER->phone1));
            }
        }

        $button = $this->single_button(new moodle_url($url, $params), get_string('continue'), 'post');

        // remove sesskey from $button; it's not necessary and could be a security risk
        $button = preg_replace('/<input[^>]*name="sesskey"[^>]*>/', '', $button);

        if ($anchor) {
            // single_button with "post" does not allow #anchor, so we add it manually
            $button = str_replace($url, "$url/#$anchor", $button);
        }

        $output .= html_writer::tag('tr', html_writer::tag('th', '').html_writer::tag('td', $button));
        $output .= html_writer::end_tag('table');
        return $output;
    }

    /**
     * Show the  some text in a box
     */
    public function show_box_text($boxtext) {
        $output = '';
        if (trim(strip_tags($boxtext))) {
            $output .= $this->output->box_start('mod_introbox');
            $output .= $boxtext;
            $output .= $this->output->box_end();
        }
        return $output;
    }

    /**
     * Show the introduction as entered on edit page
     */
    public function show_intro() {
        $output = '';
        if (trim(strip_tags($this->ec->intro))) {
            $output .= $this->output->box_start('mod_introbox');
            $output .= format_module_intro('englishcentral', $this->ec, $this->ec->cm->id);
            $output .= $this->output->box_end();
        }
        return $output;
    }

    public function show_notavailable() {
        $output = $this->notification($this->ec->get_string('notavailable'), 'warning');
        $output .= $this->show_dates_available();
        $output .= $this->course_continue_button();
        $output .= $this->footer();
        return $output;
    }

    public function show_notviewable() {
        $output = $this->notification($this->ec->get_string('notviewable'), 'warning');
        $output .= $this->show_dates_viewable();
        $output .= $this->course_continue_button();
        $output .= $this->footer();
        return $output;
    }

    public function course_continue_button() {
        $url = new moodle_url('/course/view.php', array('id' => $this->ec->course->id));
        return $this->output->continue_button($url);
    }

    /**
     * Show a list of availability time restrictions
     */
    public function show_dates_available() {
        return $this->show_dates('activity', array('open', 'close'));
    }

    /**
     * Show a list of viewable time restrictions
     */
    public function show_dates_viewable() {
        return $this->show_dates('viewable', array('open', 'close'));
    }

    /**
     * Show a list of timing restrictions
     */
    public function show_dates($type, $suffixes) {
        $output = array();

        $fmt = 'timeondate';
        $fmt = $this->ec->get_string($fmt);

        foreach ($suffixes as $suffix) {
            $name = $type.$suffix;
            if (empty($this->ec->$name)) {
                continue;
            }
            $date = userdate($this->ec->$name, $fmt);
            $date = html_writer::tag('b', $date);
            if ($this->ec->$name < $this->ec->time) {
                $prefix = 'past';
            } else {
                $prefix = 'future';
            }
            $output[] = $this->ec->get_string($prefix.$name, $date);
        }

        if (empty($output)) {
            return '';
        } else {
            $output = html_writer::alist($output);
            return $this->output->box($output, 'englishcentral_timing');
        }
    }

    /**
     * Show the EC progress element
     */
    public function show_progress() {

        $progress = $this->ec->get_progress();

        // calculate total percent
        $percent = 0;
        $percent += max(0, min($progress->watch, $this->ec->watchgoal));
        $percent += max(0, min($progress->learn, $this->ec->learngoal));
        $percent += max(0, min($progress->speak, $this->ec->speakgoal));
        if ($percent) {
            $percent /= ($this->ec->watchgoal + $this->ec->learngoal + $this->ec->speakgoal);
            $percent = round(100 * $percent, 0);
        }

        $output = '';
        $output .= $this->output->box_start('englishcentral_progress', 'id_progresscontainer');

        $timing = '';
        if ($open = ($this->ec->videoopen ? $this->ec->videoopen : $this->ec->activityopen)) {
            $timing .= html_writer::tag('dt', $this->ec->get_string('from'));
            $timing .= html_writer::tag('dd', userdate($open));
        }
        if ($close = ($this->ec->videoclose ? $this->ec->videoclose : $this->ec->activityclose)) {
            $timing .= html_writer::tag('dt', $this->ec->get_string('until'));
            $timing .= html_writer::tag('dd', userdate($close));
        }
        if ($timing) {
            $timing = html_writer::tag('dl', $timing);
        }
        $timing = html_writer::tag('h4', $this->ec->get_string('yourprogress'), array('class' => 'title')).$timing;
        $output .= html_writer::tag('div', $timing, array('class' => 'timing'));

        // format titlecharts
        $output .= html_writer::start_tag('div', array('class' => 'titlechart-container'));
        $output .= $this->show_titlechart_type('watch', $progress);
        $output .= $this->show_titlechart_type('learn', $progress);
        $output .= $this->show_titlechart_type('speak', $progress);
        $output .= $this->show_titlechart('total', $percent, '%', 'achieved', $percent);
        $output .= html_writer::end_tag('div');
        $output .= $this->output->box_end();
        return $output;
    }

    public function show_titlechart_type($type, $progress) {
        $num = intval($progress->$type);
        $div = intval($this->ec->{$type.'goal'});
        if ($div==0) {
            $percent = 0;
        } else {
            $percent = round(100 * $num / $div);
        }
        return $this->show_titlechart($type, $num, " / $div", $type.'goalunits', $percent);
    }

    public function show_titlechart($type, $text1, $text2, $string, $percent) {
        $title = $this->ec->get_string($type.'goal');
        $help = $this->help_icon($type.'goal', $this->ec->plugin);
        $title = html_writer::tag('h4', $title.$help, array('class' => 'title'));
        $chart = $this->show_chart($type, $text1, $text2, $string, $percent);
        return html_writer::tag('div', $title.$chart, array('class' => 'titlechart'));
    }

    public function show_chart($type, $text1, $text2, $string, $percent) {
        $output = '';

        // outer ring
        $params = array('class' => 'outerring',
                        'style' => $this->get_chart_transform($percent));
        $output .= html_writer::tag('div', '', $params);

        // start innertext
        $output .= html_writer::start_tag('div', array('class' => 'innertext'));

        // line1
        $output .= html_writer::start_tag('div', array('class' => 'line1'));
        $output .= html_writer::tag('span', $text1, array('class' => 'text1'));
        $output .= html_writer::tag('span', $text2, array('class' => 'text2'));
        $output .= html_writer::end_tag('div');

        // line2
        $output .= html_writer::tag('div', $this->ec->get_string($string), array('class' => 'line2'));

        // end innertext
        $output .= html_writer::end_tag('div');

        $params = array('class' => "chart $type ".$this->get_chart_class($percent));
        return html_writer::tag('div', $output, $params);
    }

    public function get_chart_transform($percent) {
        switch (true) {
            case ($percent < 0): $percent = 0; break;
            case ($percent > 100): $percent = 100; break;
        }
        $degrees = round(360 * $percent / 100);
        if ($percent >= 50) {
            $degrees -= 180;
        }
        return 'transform: rotate('.$degrees.'deg);';
    }

    public function get_chart_class($percent) {
        if ($percent >= 50) {
            return 'over50';
        } else {
            return 'under50';
        }
    }

    /**
     * Show the EC videos element
     */
    public function show_videos() {
        global $DB, $USER;

        $output = '';
        $output .= $this->output->box_start('englishcentral_videos');

        $attempts = $this->ec->get_attempts();

        // get video ids in this EC activity
        $connection_available = true;
        if ($videoids = $this->ec->get_videoids()) {

            // fetch video info from EC server
            if ($videos = $this->auth->fetch_dialog_list($videoids)) {

                // build index to map videoid onto $videos item
                $index = array();
                foreach ($videos as $i => $video) {
                    if (isset($video->dialogID)) {
                        $index[$video->dialogID] = $i;
                    }
                }

                // extract names of count/complete $fields
                $fields = $this->ec->get_attempts_fields(false);
                $fields = explode(',', $fields);

                // create video thumbnails in required order
                foreach ($videoids as $videoid) {
                    if (array_key_exists($videoid, $index)) {
                        $video = $videos[$index[$videoid]];
                        $empty = empty($attempts[$videoid]);
                        foreach ($fields as $field) {
                            $video->$field = ($empty ? 0 : $attempts[$videoid]->$field);
                        }
                        $output .= $this->show_video($video);
                    }
                }
            } else {
                $connection_available = false;
            }
        } else {
            $output .= html_writer::tag('p', $this->ec->get_string('novideos'));
        }

		if (has_capability('mod/englishcentral:manage', $this->ec->context) ) {
		    $initially_visible = $videoids;
            $output .= $this->show_removevideo_icon($initially_visible);
            //$output .= $this->show_addvideo_icon();
        }

        if ($connection_available==false) {
            $output .= html_writer::tag('p', $this->ec->get_string('noconnection'));
        }

        $output .= $this->output->box_end();
        return $output;
    }

    public function show_video($video) {
        $output = '';

        switch (true) {
            case ($video->difficulty <= 2): $difficulty = 'beginner';     break;
            case ($video->difficulty <= 4): $difficulty = 'intermediate'; break;
            case ($video->difficulty >= 5): $difficulty = 'advanced';     break;
            default: $difficulty = '';
        }

        // remove leading 00: from duration
        if (substr($video->duration, 0, 3)=='00:') {
            $video->duration = substr($video->duration, 3);
        }

        $output .= html_writer::start_tag('div', array('class' => 'activity-thumbnail'));

        $output .= html_writer::start_tag('div', array('class' => 'thumb-outline'));

        $params = array('class' => 'activity-title', 'data-url' => $video->dialogURL);
        $showdetails = false;
        if ($this->ec->showdetails) {
            $is_student = has_capability('mod/englishcentral:view', $this->ec->context);
            $is_teacher = has_capability('mod/englishcentral:addinstance', $this->ec->context);
            switch ($this->ec->showdetails) {
                case 1: $showdetails = ($is_student && ($is_teacher == false)); break;
                case 2: $showdetails = (($is_student == false) && $is_teacher); break;
                case 3: $showdetails = ($is_student || $is_teacher); break;
            }
        }
        if ($showdetails && isset($video->videoDetailsURL)) {
            $params['data-video-details-url'] = $video->videoDetailsURL;
        }
        $output .= html_writer::tag('span', $video->title, $params);

        $params = array('class' => 'thumb-frame',
                        'data-url' => $video->dialogURL,
                        'data-demopicurl' => $video->demoPictureURL,
                        'style' => 'background-image: url("'.$video->thumbnailURL.'");',
                        'description' => $video->description
                    ); 

        $topicsList = array('topics' => $video->topics);

        $newTopicsList = [];

        if(is_array($topicsList['topics'][0] )) {
            foreach ($topicsList['topics'][0] as $key => $value) {
                array_push($newTopicsList, $value);
            }
        }else {
            foreach ($topicsList['topics'] as $thetopic) {
                array_push($newTopicsList, $thetopic->name);
            }
        }

        if(count($newTopicsList)>0) {
            $params['topics'] = $newTopicsList[0];
        }else{
            $params['topics'] = '';
        }

        $output .= html_writer::start_tag('span', $params);

        $params = array('class' => 'play-icon');
        $output .= html_writer::tag('span', '', $params);

        $output .= $this->show_video_status($video);

        $output .= html_writer::end_tag('span');

        if ($this->ec->showlevelnumber || $this->ec->showleveltext) {

            $params = array('class' => 'difficulty-level-indicator '.$difficulty);
            $output .= html_writer::start_tag('span', $params);

            if ($this->ec->showlevelnumber) {
                $label = $this->ec->get_string('levelx', $video->difficulty);
                $params = array('class' => 'difficulty-level text-center');
                $output .= html_writer::tag('span', $label, $params);
            }
            if ($this->ec->showleveltext) {
                $label = $this->ec->get_string($difficulty);
                $params = array('class' => 'difficulty-label');
                $output .= html_writer::tag('span', $label, $params);
            }
            $output .= html_writer::end_tag('span');
        }

        if ($this->ec->showduration) {
            $label = $video->duration;
            $params = array('class' => 'duration');
            $output .= html_writer::tag('span', $label, $params);
        }

        $output .= html_writer::end_tag('div'); // activity-outline

        $output .= html_writer::end_tag('div'); // activity-thumbnail

        return $output;
    }

    public function show_video_status($video) {
        $output = '';
        if (isset($video->watchcomplete) && $video->watchcomplete) {
            $output .= html_writer::tag('span', $video->watchcomplete, array('class' => 'watch-status completed'));
            $output .= html_writer::tag('span', $video->learncount, array('class' => 'learn-status'));
            $output .= html_writer::tag('span', $video->speakcount, array('class' => 'speak-status'));
        } else if (isset($video->watchcount) && $video->watchcount) {
            // we could try a fancy unicode char, core_text::code2utf8(0x27eb)
            $output .= html_writer::tag('span', '~', array('class' => 'watch-status inprogress'));
        }
        return $output;
    }

    // this method is not used,
    // nor is the addvideo icon
    
    protected function show_addvideo_icon() {
        return $this->show_videos_icon('add');
    }

    protected function show_removevideo_icon($initially_visible=true) {
        return $this->show_videos_icon('remove',$initially_visible);
    }

    protected function show_videos_icon($type,$initially_visible=true){
        $text = $this->ec->get_string($type.'video');
        if (method_exists($this, 'image_url')) {
            $image_url = 'image_url'; // Moodle >= 3.3
        } else {
            $image_url = 'pix_url'; // Moodle <= 3.2
        }
        $image_url = $this->$image_url($type.'video', $this->ec->plugin);
        $image = html_writer::empty_tag('img', array('src' => $image_url, 'title' => $text));
        $removeText = html_writer::tag('span', $this->ec->get_string('removevideo'), array('class' => 'remove-text'));
        $removeIcon = html_writer::tag('div', '', array('class' => 'remove-icon'));
        $help = $this->ec->get_string($type.'videohelp');
        $help = html_writer::tag('span', $help, array('class' => 'videohelp'));
        $hidden = $initially_visible ? '' : ' page-mod-englishcentral-hide';
        return html_writer::tag('div', $image.$removeIcon.$removeText.$help, array('class' => 'videoicon '.$type.'video' . $hidden));
    }

    public function show_progress_report() {
        global $DB, $CFG;
        $output = '';

        $this->setup_sort();
        $url = $this->ec->get_report_url();

		// fetch groupmode/menu/id for this activity
		if ($groupmode = groups_get_activity_groupmode($this->ec->cm)) {
			$groupmenu = groups_print_activity_menu($this->ec->cm, $url, true);
			$groupid = groups_get_activity_group($this->ec->cm);
		} else {
			$groupmenu = '';
			$groupid = 0;
		}

        // initialize study goals
        $goals = (object)array('watch' => 0,
                               'learn' => 0,
                               'speak' => 0);

        // Create SQL to fetch aggregate items from the EC attempts table.
        $select = 'userid,'.
                  'SUM(watchcomplete) + SUM(learncount) + SUM(speakcount) AS percent,'.
                  'SUM(watchcomplete) AS watch,'.
                  'SUM(learncount) AS learn,'.
                  'SUM(speakcount) AS speak';
        $from   = '{englishcentral_attempts}';
        $where  = 'ecid = ?';
        $params = array($this->ec->id);
		if ($groupid) {
			$where .= ' AND userid IN (SELECT gm.userid FROM {groups_members} gm WHERE gm.groupid = ?)';
			$params[] = $groupid;
        }
        $where = "$where GROUP BY userid";

        $from   = "(SELECT $select FROM $from WHERE $where) items,".
                  '{user} u';
        $where  = 'items.userid = u.id';

        //get_all_user_name_fields deprecated in 3.11
        if($CFG->version<2021051700) {
            $select = 'items.*,' . get_all_user_name_fields(true, 'u');
        }else{
            $userfields = \core_user\fields::for_name();
            $usersql = $userfields->get_sql('u');
            //note no concatenating comma, thats how userfields -> selects works
            $select = 'items.*' . $usersql->selects;
        }

        if ($this->sort=='firstname' || $this->sort=='lastname') {
            $order = 'u.'.$this->sort;
        } else {
            $order = 'items.'.$this->sort;
        }
        if ($this->order) {
            $order .= ' '.$this->order;
        }

        // set goals to maximum in these aggregate items
        if ($items = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $order", $params)) {
            foreach ($items as $userid => $item) {
                $goals->watch = max($goals->watch, $item->watch);
                $goals->learn = max($goals->learn, $item->learn);
                $goals->speak = max($goals->speak, $item->speak);
            }
        } else {
            $items = array();
        }

        // override goals with teacher-specified goals, if available
        if ($this->ec->watchgoal + $this->ec->learngoal + $this->ec->speakgoal) {
            $goals->watch = intval($this->ec->watchgoal);
            $goals->learn = intval($this->ec->learngoal);
            $goals->speak = intval($this->ec->speakgoal);
        }

        $goals->total = ($goals->watch +
                         $goals->learn +
                         $goals->speak);

        $type = 'firstname';
        $fullname = get_string($type, 'moodle');
        $fullname .= $this->get_sort_icon($url, $type);

        $fullname .= ' ';

        $type = 'lastname';
        $fullname .= get_string('lastname', 'moodle');
        $fullname .= $this->get_sort_icon($url, $type);
        $fullname = html_writer::tag('span', $fullname, array('class' => 'fullname'));

        $type = 'percent';
        $percent = '%'; // get_string($type, 'grades');
        $percent .= $this->get_sort_icon($url, $type);
        $percent = html_writer::tag('span', $percent, array('class' => 'percent'));

        $output .= html_writer::tag('dt', $fullname.$percent, array('class' => 'user title'));

        $title = '';
        $left = 0;
        foreach (array('watch', 'learn', 'speak') as $type) {
            if ($goals->$type) {
                $text = $this->ec->get_string($type.'goal');
                $sort = $this->get_sort_icon($url, $type);
                $percent = (100 * min(1, $goals->$type / $goals->total));
                $style = "margin-left: $left%; width: $percent%;";
                $params = array('class' => $type, 'style' => $style);
                $title .= html_writer::tag('span', $text.' '.$sort, $params);
                $left += $percent;
            }
        }
        $output .= html_writer::tag('dd', $title, array('class' => 'bars title'));

        foreach ($items as $userid => $item) {
            $item->total = (min($goals->watch, $item->watch) +
                            min($goals->learn, $item->learn) +
                            min($goals->speak, $item->speak));
            if ($goals->total==0) {
                $item->percent = '';
            } else {
                $item->percent = round(100 * min(1, $item->total / $goals->total)).'%';
            }
            $items[$userid] = $item;
        }

        if ($this->sort=='percent') {
            uasort($items, array($this, 'uasort_percent'));
        }

        foreach ($items as $userid => $item) {
            $output .= $this->show_progress_report_item($item, $goals);
        }

        if (count($items)) {
            $output = html_writer::tag('dl', $output, array('class' => 'userbars'));
        } else {
            $output = html_writer::tag('p', $this->ec->get_string('noprogressreport'));
        }

		if ($groupmenu) {
			$output = $groupmenu.$output;
		}

        return $output;
    }

    protected function uasort_percent($a, $b) {
        $anum = intval($a->percent);
        $bnum = intval($b->percent);
        if ($anum > $bnum) {
            return ($this->order=='ASC' ? 1 : -1);
        }
        if ($anum < $bnum) {
            return ($this->order=='ASC' ? -1 : 1);
        }
        return 0;
    }

    protected function show_progress_report_item($item, $goals) {
        $output = '';
        $output .= html_writer::tag('dt', $this->show_progress_report_user($item, $goals), array('class' => 'user'));
        $output .= html_writer::tag('dd', $this->show_progress_report_bars($item, $goals), array('class' => 'bars'));
        return $output;
    }

    protected function show_progress_report_user($item, $goals) {
        $output = '';
        $output .= html_writer::tag('span', fullname($item), array('class' => 'fullname'));
        $output .= html_writer::tag('span', $item->percent, array('class' => 'percent'));
        return $output;
    }

    protected function show_progress_report_bars($item, $goals) {
        $output = '';
        $output .= $this->show_progress_report_bar($item, $goals, 'watch');
        $output .= $this->show_progress_report_bar($item, $goals, 'learn');
        $output .= $this->show_progress_report_bar($item, $goals, 'speak');
        return $output;
    }

    protected function show_progress_report_bar($item, $goals, $type) {
        if (empty($goals->$type)) {
            return '';
        }

        $text = $item->$type.' / '.$goals->$type;
        switch ($type) {
            case 'watch': $title = $this->ec->get_string('watchvideos', $text); break;
            case 'learn': $title = $this->ec->get_string('learnwords', $text); break;
            case 'speak': $title = $this->ec->get_string('speaklines', $text); break;
        }
        $text = html_writer::tag('span', $text, array('class' => 'text', 'title' => $title));

        if (empty($item->$type)) {
            $bar = '';
        } else {
            $value = min($item->$type, $goals->$type);
            $width = (100 * min(1, $value / $goals->$type)).'%;';
            $params = array('class' => 'bar', 'style' => 'width: '.$width);
            $bar = html_writer::tag('span', '', $params);
        }

        $width = (100 * min(1, $goals->$type / $goals->total)).'%';
        $params = array('class' => $type, 'style' => 'width: '.$width);

        return html_writer::tag('span', $bar.$text, $params);
    }

    /**
     * Set the sort item/order
     */
    protected function setup_sort() {
        global $SESSION;

        // initialize session info
        if (empty($SESSION->englishcentral)) {
            $SESSION->englishcentral = new stdClass();
            $SESSION->englishcentral->sort = '';
            $SESSION->englishcentral->order = '';
        }

        // override sort item/order with incoming data
        $sort = optional_param('sort', '', PARAM_ALPHA);
        switch (true) {

            case ($sort==''):
                $sort = $SESSION->englishcentral->sort;
                $order = $SESSION->englishcentral->order;
                break;

            case ($sort==$SESSION->englishcentral->sort):
                $order = optional_param('order', '', PARAM_ALPHA);
                break;

            default:
                $order = '';
        }

        if ($sort=='') {
            $sort = 'lastname';
            $order = '';
        }

        if ($order=='') {
            if ($sort=='firstname' || $sort=='lastname') {
                $order = 'ASC';
            } else {
                $order = 'DESC';
            }
        }

        // store new/updated sort item/order
        $this->sort = $SESSION->englishcentral->sort = $sort;
        $this->order = $SESSION->englishcentral->order = $order;
    }

    protected function get_sort_icon($url, $sort) {
        global $OUTPUT;

        if ($sort==$this->sort) {
            $order = $this->order;
        } else {
            $order = ''; // unsorted
        }

        switch (true) {
            case ($order=='ASC'):
                $text = 'sortdesc';
                $icon = 't/sort_asc';
                break;
            case ($order=='DESC'):
                $text = 'sortasc';
                $icon = 't/sort_desc';
                break;
            case ($sort=='firstname'):
            case ($sort=='lastname'):
                $text = "sortby$sort";
                $icon = 't/sort';
            default:
                $text = 'sort';
                $icon = 't/sort';
                break;
        }

        $params = array();
        if ($sort) {
            $params['sort'] = $sort;
        } else {
            $url->remove_params('sort');
        }
        if ($order) {
            $params['order'] = ($order=='ASC' ? 'DESC' : 'ASC');
        } else {
            $url->remove_params('order');
        }
        if (count($params)) {
            $url->params($params);
        }

        $text = get_string($text, 'grades');
        $params = array('class' => 'sorticon');
        $icon = $OUTPUT->pix_icon($icon, $text, 'moodle', $params);

        return html_writer::link($url, $icon, array('title' => $text));
    }

    /**
     * Show the EC videos element
     */
    public function show_search() {
        $output = '';
		if (has_capability('mod/englishcentral:manage', $this->ec->context)) {



            // start settings/form
            $output .= html_writer::start_tag('form', array('class' => 'search-form'));
            $output .= html_writer::tag('dt', $this->ec->get_string('videosearch'), array('class' => 'visible', 'id' => 'search-label'));
            $output .= html_writer::start_tag('dl', array('class' => 'search-fields'));

            // text box size
            $size = ''; // 30
            $output .= html_writer::start_tag('div', array('id' => 'search-fields-main'));
            $output .= $this->show_search_term('searchterm', $size);
            $output .= $this->show_search_button('searchbutton');
            $output .= html_writer::end_tag('div');
            $output .= html_writer::start_tag('div', array('id' => 'search-fields-advanced'));
            $output .= $this->show_search_level('level'); // =difficulty
            //$output .= $this->show_search_topics('topics', $size);
            //$output .= $this->show_search_duration('duration');
            //$output .= $this->show_search_copyright('copyright', $size);
            $output .= html_writer::end_tag('div');

            // end settings/form
            $output .= html_writer::end_tag('dl');
            $output .= html_writer::end_tag('form');

            // enclose settings in search-box
            $output = html_writer::tag('div', $output, array('class' => 'search-box'));

            // append element to display search-results
            $output .= html_writer::tag('div', '', array('class' => 'search-results'));

            // enclose search-box and search-results in container
            $output = html_writer::tag('div', $output, array('id' => 'search-inner-container'));

            $output .= html_writer::tag('div', '', array('id' => 'close-search-button'));
            // append element to display button-alike-behavior

            $output .= html_writer::start_tag('div', array('id' => 'faux-search-button'));
            $output .= html_writer::start_tag('div', array('class' => 'faux-search-button-icon'));
            $output .= html_writer::end_tag('div');
            $output .= html_writer::tag('span', $this->ec->get_string('addvideo'), array('class' => 'faux-search-button-text'));
            $output .= html_writer::end_tag('div');

            // enclose search-box and search-results in container
            $output = html_writer::tag('div', $output, array('id' => 'id_searchcontainer'));

            // append element to display search-results
            $output .= html_writer::tag('div', '', array('class' => 'add-video-box'));
        }
        return $output;
    }

    public function show_search_term($name, $size='') {
        $output = '';
        $params = array('type' => 'text',
                        'name' => $name,
                        'id' => 'id_'.$name,
                        'placeholder' => $this->ec->get_string('videosearchprompt'));
        if ($size) {
            $params['size'] = $size;
        }
        $output .= html_writer::tag('dd', html_writer::empty_tag('input', $params), array('class' => 'visible'));
        return $output;
    }

    public function show_search_topics($name, $size='') {
        $output = '';
        $params = array('type' => 'text',
                        'name' => $name,
                        'id' => 'id_'.$name);
        if ($size) {
            $params['size'] = $size;
        }
        $output .= html_writer::tag('dt', $this->ec->get_string('topics'));
        $output .= html_writer::tag('dd', html_writer::empty_tag('input', $params));
        return $output;
    }

    public function show_search_level($name) {
        $output = '';
        $output .= html_writer::tag('dt', $this->ec->get_string($name));
        $output .= html_writer::start_tag('dd');
        $output .= html_writer::start_tag('div', array('class' => "checkboxgroup $name"));
        for ($i=1; $i<=7; $i++) {
            $output .= html_writer::start_tag('div', array('class' => "checkboxitem $name-$i"));
            $id = 'id_'.$name.'_'.$i;
            $params = array('type'  => 'checkbox',
                            'name'  => $name.'[]',
                            'value' => $i,
                            'id'    => $id);
            $output .= html_writer::empty_tag('input', $params);
            $output .= html_writer::tag('label', $i, array('for' => $id));
            $output .= html_writer::end_tag('div');
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('dd');
        return $output;
    }

    public function show_search_duration($name) {
        $output = '';
        $output .= html_writer::tag('dt', get_string('duration', 'search'));
        $output .= html_writer::start_tag('dd');
        $output .= html_writer::start_tag('div', array('class' => "checkboxgroup $name"));
        for ($i=1; $i<=3; $i++) {
            $output .= html_writer::start_tag('div', array('class' => "checkboxitem $name-$i"));
            $id = 'id_'.$name.'_'.$i;
            $params = array('type'  => 'checkbox',
                            'name'  => $name.'[]',
                            'value' => $i,
                            'id'    => $id);
            $output .= html_writer::empty_tag('input', $params);
            $output .= html_writer::tag('label', $this->ec->get_string("duration$i"), array('for' => $id));
            $output .= html_writer::end_tag('div');
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('dd');
        return $output;
    }

    public function show_search_copyright($name, $size) {
        $output = '';
        $params = array('type' => 'text',
                        'name' => $name,
                        'size' => $size,
                        'id' => 'id_'.$name);
        $output .= html_writer::tag('dt', $this->ec->get_string($name));
        $output .= html_writer::tag('dd', html_writer::empty_tag('input', $params));
        return $output;
    }

    public function show_search_button($name) {
        $output = '';
        $output .= html_writer::start_tag('dd', array('class' => 'visible'));
        $params = array('type' => 'submit',
                        'name' => $name,
                        'id' => 'id_'.$name,
                        'class' => 'btn btn-primary',
                        'value' => get_string('search'));
        $output .= html_writer::empty_tag('input', $params);
        $output .= html_writer::tag('a', get_string('showadvanced', 'form'), array('class' => 'search-advanced'));
        $output .= html_writer::end_tag('dd');
        return $output;
    }


    /**
     * create a container for the EC player
     */

    //fetch modal content
    /*
    public function show_player($firstthumbnail){
        $data=[];
        $data['firstthumbnail']=$firstthumbnail;
        return $this->render_from_template('mod_englishcentral/showplayer', $data);
    }
    */
    public function show_player($hidden=false) {
        $data=[];
        if($hidden){
            $data['display']='page-mod-englishcentral-hide';
        }else{
            $data['display']='';
        }
        return $this->render_from_template('mod_englishcentral/showplayer', $data);
    }

}
