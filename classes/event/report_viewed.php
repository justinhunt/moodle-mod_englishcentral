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

/**
 * The mod_englishcentral report viewed event.
 *
 * @package    mod_englishcentral
 * @copyright  2014 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_englishcentral\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_englishcentral report viewed event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int ecid: the id of the englishcentral activity.
 *      - string mode: the name of the report.
 * }
 *
 * @package    mod_englishcentral
 * @since      Moodle 2.9
 * @copyright  2014 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_viewed extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventreportviewed', 'mod_englishcentral');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $mode = s($this->other['mode']);
        return "The user with id '$this->userid' viewed the report '$mode' ".
               "for the EC activity with course module id '$this->contextinstanceid'.";
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $params = array('id' => $this->contextinstanceid,
                        'mode' => $this->other['mode']);
        return new \moodle_url('/mod/englishcentral/report.php', $params);
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        $cmid = $this->contextinstanceid;
        $url = 'report.php?id=' . $cmid . '&mode=' . $this->other['mode'];
        return array($this->courseid, 'englishcentral', 'report', $url, $this->other['ecid'], $cmid);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['ecid'])) {
            throw new \coding_exception('The \'ecid\' value must be set in other.');
        }
        if (!isset($this->other['mode'])) {
            throw new \coding_exception('The \'mode\' value must be set in other.');
        }
    }

    public static function get_other_mapping() {
        return array(
            'ecid' => array('db' => 'englishcentral', 'restore' => 'englishcentral')
        );
    }
}
