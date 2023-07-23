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
 * The mod_page course module viewed event.
 *
 * @package    mod_englishcentral
 * @copyright  2023 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_englishcentral\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The mod_englishcentral course module viewed event class.
 *
 * @package    mod_englishcentral
 * @since      Moodle 2.7
 * @copyright  2023 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progress_updated extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'englishcentral';
    }

    public static function get_objectid_mapping() {
        return array('db' => 'englishcentral_attempts', 'restore' => 'englishcentral_attempts');
    }

    public static function get_other_mapping() {
        return array(
            'ecid' => array('db' => 'englishcentral', 'restore' => 'englishcentral')
        );
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('progressupdated', 'mod_englishcentral');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' progressed in the EnglishCentral activity with course module id '$this->contextinstanceid'.";
    }
}

