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
 * Defines all the backup steps that will be used by {@link backup_englishcentral_activity_task}
 *
 * @package     mod_englishcentral
 * @category    backup
 * @copyright   2014 Justin Hunt <poodllsupport@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete webquest structure for backup, with file and id annotations
 *
 */
class backup_englishcentral_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the structure of the 'englishcentral' element inside the webquest.xml file
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // we may need the EC partnerid, if we are backing up userinfo
        static $partnerid = null;

        // cache the $userinfo flag and $siteadmin flag
        $userinfo = $this->get_setting_value('userinfo');
        $siteadmin = has_capability('moodle/site:config', context_system::instance());

        ////////////////////////////////////////////////////////////////////////
        // XML nodes declaration - non-user data
        ////////////////////////////////////////////////////////////////////////

        $fieldnames = array('id', 'course'); // excluded fields
        $fieldnames = $this->get_fieldnames('englishcentral', $fieldnames);
        $activity = new backup_nested_element('englishcentral', array('id'), $fieldnames);

        $videos = new backup_nested_element('videos');
        $fieldnames = array('id', 'ecid'); // excluded fields
        $fieldnames = $this->get_fieldnames('englishcentral_videos', $fieldnames);
        $video = new backup_nested_element('video', array('id'), $fieldnames);

        ////////////////////////////////////////////////////////////////////////
        // XML nodes declaration - user data
        ////////////////////////////////////////////////////////////////////////

        if ($userinfo) {
            $accountids = new backup_nested_element('accountids');
            $fieldnames = array('id'); // excluded fields
            $fieldnames = $this->get_fieldnames('englishcentral_accountids', $fieldnames);
            $fieldnames[] = 'partnerid'; // additional field
            $accountid = new backup_nested_element('accountid', array('id'), $fieldnames);

            $attempts = new backup_nested_element('attempts');
            $fieldnames = array('id', 'ecid'); // excluded fields
            $fieldnames = $this->get_fieldnames('englishcentral_attempts', $fieldnames);
            $attempt = new backup_nested_element('attempt', array('id'), $fieldnames);

            $phonemes = new backup_nested_element('phonemes');
            $fieldnames = array('id', 'ecid'); // excluded fields (keep attemptid)
            $fieldnames = $this->get_fieldnames('englishcentral_phonemes', $fieldnames);
            $phoneme = new backup_nested_element('phoneme', array('id'), $fieldnames);
        }

        ////////////////////////////////////////////////////////////////////////
        // build the tree in the order needed for restore
        ////////////////////////////////////////////////////////////////////////

        $activity->add_child($videos);
        $videos->add_child($video);

        if ($userinfo) {
            $activity->add_child($accountids);
            $accountids->add_child($accountid);

            $activity->add_child($attempts);
            $attempts->add_child($attempt);

            $activity->add_child($phonemes);
            $phonemes->add_child($phoneme);
        }

        ////////////////////////////////////////////////////////////////////////
        // data sources
        ////////////////////////////////////////////////////////////////////////

        $activity->set_source_table('englishcentral', array('id' => backup::VAR_ACTIVITYID));
        $video->set_source_table('englishcentral_videos', array('ecid' => backup::VAR_PARENTID));

        if ($userinfo) {

            // get partnerid (first time only)
            if ($partnerid===null) {
                if ($siteadmin) {
                    $partnerid = get_config('mod_englishcentral', 'partnerid');
                }
                if ($partnerid && is_numeric($partnerid)) {
                    $partnerid = intval($partnerid);
                } else {
                    $partnerid = 0;
                }
            }

            // accountids (include partnerid in each record)
            if ($partnerid) {
                list($sql, $params) = $this->get_accountids_userids($this->get_setting_value(backup::VAR_ACTIVITYID));
                $sql = "SELECT *, $partnerid AS partnerid ".
                       'FROM {englishcentral_accountids} '.
                       "WHERE accountid > 0 AND userid $sql";
                $accountid->set_source_sql($sql, $params);
            }

            // attempts
            $params = array('ecid' => backup::VAR_PARENTID);
            $attempt->set_source_table('englishcentral_attempts', $params);

            // phonemes
            $params = array('ecid' => backup::VAR_PARENTID);
            $phoneme->set_source_table('englishcentral_phonemes', $params);
            // Note that a phoneme should probably be a child of an attempt
            // but we put it as a child of an EC activity for legacy reasons
            // i.e. that's how things were done in earlier versions of this module
        }

        ////////////////////////////////////////////////////////////////////////
        // id annotations (foreign keys on non-parent tables)
        ////////////////////////////////////////////////////////////////////////

        if ($userinfo) {
            $accountid->annotate_ids('user', 'userid');
            $attempt->annotate_ids('user', 'userid');
            $phoneme->annotate_ids('user', 'userid');
        }

        ////////////////////////////////////////////////////////////////////////
        // file annotations
        ////////////////////////////////////////////////////////////////////////

        $activity->annotate_files('mod_englishcentral', 'intro', null);

        ////////////////////////////////////////////////////////////////////////
        // Return the root element, wrapped in a standard activity structure.
        ////////////////////////////////////////////////////////////////////////

        return $this->prepare_activity_structure($activity);
    }

    /**
     * get_fieldnames
     *
     * @uses $DB
     * @param account $tablename the name of the Moodle table (without prefix)
     * @param array $excluded_fieldnames these field names will be excluded
     * @return array of field names
     */
    protected function get_fieldnames($tablename, array $excluded_fieldnames)   {
        global $DB;
        $fieldnames = array_keys($DB->get_columns($tablename));
        return array_diff($fieldnames, $excluded_fieldnames);
    }

    /**
     * get_accountids_userids
     *
     * Get userids for all users who have attempted this EnglishCentral activity
     *
     * @uses $DB
     * @return array ($userids, $params) to extract accountids used in this EnglishCentral activity
     */
    protected function get_accountids_userids($ecid) {
        global $DB;

        if ($userids = $DB->get_records_menu('englishcentral_attempts', array('ecid' => $ecid), 'id', 'id,userid')) {
            $userids = array_unique($userids);
        } else {
            $userids = array();
        }

        // Note: we don't put the ids into $params like this:
        //   return $DB->get_in_or_equal($userids);
        // because Moodle 2.0 backup expects only backup::VAR_xxx
        // constants, which are all negative, in $params, and will
        // throw an exception for any positive values in $params.
        // - baseelementincorrectfinalorattribute
        //   backup/util/structure/base_final_element.class.php

        switch (count($userids)) {
            case 0:  $userids = '< 0'; break;
            case 1:  $userids = '= '.reset($userids); break;
            default: $userids = 'IN ('.implode(',', $userids).')';
        }

        return array($userids, array());
    }
}
