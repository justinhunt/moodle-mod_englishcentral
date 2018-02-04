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

    /**
     * attach the $ec object so it is accessible throughout this class
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
    public function header($extrapagetitle=null) {
        $activityname = format_string($this->ec->name, true, $this->ec->course);
        $title = $this->ec->course->shortname.': '.$activityname;
        if ($extrapagetitle) {
            $title .= ': '.$extrapagetitle;
        }
        $this->page->set_title($title);
        $this->page->set_heading($this->ec->course->fullname);

        $output = $this->output->header();
        if ($this->ec->can_manage()) {
            $output .= $this->output->heading_with_help($activityname, 'overview', 'englishcentral');
        } else {
            $output .= $this->output->heading($activityname);
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
        if ($this->ec->can('config', 'moodle/site', context_system::instance())) {
            $link = array('section' => 'modsetting'.$this->ec->pluginname);
            $link = new moodle_url('/admin/settings.php', $link);
            $link = html_writer::link($link, get_string('settings'));
            return $this->ec->get_string('updatesettings', $link);
        } else {
            return $this->ec->get_string('consultadmin');
        }
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
        $output .= $this->show_dates_readonly();
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
        return $this->show_dates('available', array('from', 'until'));
    }

    /**
     * Show a list of readonly time restrictions
     */
    public function show_dates_readonly() {
        return $this->show_dates('readonly', array('until', 'from'));
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
        $output = '';
        $output .= $this->output->box_start('englishcentral_progress');
        if ($videoids = $this->ec->get_videoids()) {
            $progress = $this->auth->fetch_dialog_progress($videoids);
            $debug = false; // enable this during development
            if ($debug) {
                print_object($progress);
            }
            /////////////////////////////////////////////////////////
            // code to format progress data goes here
            /////////////////////////////////////////////////////////
            $output .= 'PROGRESS data goes here';
        } else {
            $output .= 'No progress to report so far';
        }
        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Show the EC videos element
     */
    public function show_videos() {
        $output = '';
        $output .= $this->output->box_start('englishcentral_videos');
        if ($videoids = $this->ec->get_videoids()) {
            $videos = $this->auth->fetch_dialog_list($videoids);
            foreach ($videos as $video) {
                $output .= $this->show_video($video);
            }
        } else {
            $output .= $this->ec->get_string('novideos');
        }
        $output .= $this->add_videos_button();
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

        $output .= html_writer::tag('a', $video->title, array('class' => 'activity-title',
                                                              'href' => $video->dialogURL));

        $output .= html_writer::start_tag('a', array('class' => 'thumb-frame',
                                                     'href'  => $video->dialogURL,
                        'style' => 'background-image: url("'.$video->thumbnailURL.'");'));
        $output .= html_writer::tag('span', '', array('class' => 'play-icon'));
        $output .= html_writer::end_tag('a');

        $output .= html_writer::start_tag('span', array('class' => 'difficulty-level-indicator '.$difficulty));

        $output .= html_writer::tag('span', $this->ec->get_string('levelx', $video->difficulty),
                                            array('class' => 'difficulty-level text-center difficulty-icon'));

        $output .= html_writer::start_tag('span', array('class' => 'difficulty-label'));
        $output .= html_writer::tag('span', $this->ec->get_string($difficulty));
        $output .= html_writer::end_tag('span'); // difficulty-label

        $output .= html_writer::end_tag('span'); // difficulty-level-indicator

        $output .= html_writer::tag('span', $video->duration, array('class' => 'duration'));

        $output .= html_writer::end_tag('div'); // activity-outline

        $output .= html_writer::end_tag('div'); // activity-thumbnail

        return $output;
    }

    protected function add_videos_button() {
        $text = $this->ec->get_string('addvideos');
        $icon = $this->pix_icon('t/addfile', $text);
        return html_writer::tag('div', $icon.$text, array('class' => 'addvideos'));
    }
}

