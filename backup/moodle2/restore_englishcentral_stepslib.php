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
 * @package   mod_englishcentral
 * @copyright 2014 Justin Hunt poodllsupport@gmail.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
/**
 * Define all the restore steps that will be used by the restore_englishcentral_activity_task
 */

/**
 * Structure step to restore one englishcentral activity
 */
class restore_englishcentral_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        // fetch the $userinfo flag
        $userinfo = $this->get_setting_value('userinfo');

        $paths = array();

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - non-user data
        ////////////////////////////////////////////////////////////////////////

        $path = '/activity/englishcentral';
        $paths[] = new restore_path_element('englishcentral', $path);

        $path = '/activity/englishcentral/videos/video';
        $paths[]= new restore_path_element('englishcentral_videos', $path);

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - user data
        ////////////////////////////////////////////////////////////////////////

        if ($userinfo) {
            $path = '/activity/englishcentral/accountids/accountid';
            $paths[] = new restore_path_element('englishcentral_accountids', $path);

            $path = '/activity/englishcentral/attempts/attempt';
            $paths[] = new restore_path_element('englishcentral_attempts', $path);

            $path = '/activity/englishcentral/phonemes/phoneme';
            $paths[] = new restore_path_element('englishcentral_phonemes', $path);
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_englishcentral($data) {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields (e.g. convert fields names from OLD to NEW)
        if (! $data->course = $this->get_courseid()) {
            return false; // missing courseid - shouldn't happen !!
        }
        $data->activityopen  = $this->apply_date_offset($data->activityopen);
        $data->activityclose = $this->apply_date_offset($data->activityclose);
        $data->videoopen     = $this->apply_date_offset($data->videoopen);
        $data->videoclose    = $this->apply_date_offset($data->videoclose);

        // add new record
        if (! $newid = $DB->insert_record('englishcentral', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }

        // inmediately after inserting "activity" record, call this
        $this->apply_activity_instance($newid);
    }

    protected function process_englishcentral_videos($data) {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields (e.g. convert fields names from OLD to NEW)
        $data->ecid = $this->get_new_parentid('englishcentral');

        // add new record
        if (! $newid = $DB->insert_record('englishcentral_videos', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }

        // store mapping from $oldid to $newid
        $this->set_mapping('englishcentral_videos', $oldid, $newid, false);
    }

    protected function process_englishcentral_accountids($data) {
        global $DB;

        // we should only restore the accountids if the backup 
        // and restore sites have the same partnerID
        static $partnerid = null;

        // fetch $partnerid of restore site (first time only)
        if ($partnerid===null) {
            // only site admin has access to the partnerid on the Moodle site
            if (has_capability('moodle/site:config', context_system::instance())) {
                $partnerid = get_config('mod_englishcentral', 'partnerid');
            }
            if ($partnerid && is_numeric($partnerid)) {
                $partnerid = intval($partnerid);
            } else {
                $partnerid = 0;
            }
        }

        if ($partnerid==0) {
            return false; // current user does have access to partnerID
        }

        // convert $data to object
        $data = (object)$data;

        // sanity check on the values
        if (empty($data->userid) || empty($data->accountid)) {
            return false; // nothing to do
        }

        // check partnerID
        if (empty($data->partnerid) || $data->partnerid != $partnerid) {
            return false; // accountid is for a different partnerID
        }

        // fix fields
        if (! $data->userid = $this->get_mappingid('user', $data->userid)) {
            return false; // invalid userid - shouldn't happen !!
        }

        // add new record, if necessary
        if (! $DB->record_exists('englishcentral_accountids', array('userid' => $data->userid))) {
            if (! $newid = $DB->insert_record('englishcentral_accountids', $data)) {
                return false; // could not add new record - shouldn't happen !!
            }
        }
    }

    protected function process_englishcentral_attempts($data) {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields (e.g. convert fields names from OLD to NEW)
        $data->ecid = $this->get_new_parentid('englishcentral');

        // add new record
        if (! $newid = $DB->insert_record('englishcentral_attempts', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }

        // store mapping from $oldid to $newid
        $this->set_mapping('englishcentral_attempts', $oldid, $newid, false);
    }
    
    protected function process_englishcentral_phonemes($data) {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields (e.g. convert fields names from OLD to NEW)
        $data->ecid = $this->get_new_parentid('englishcentral');
        $data->attemptid = $this->get_mappingid('englishcentral_attempt', $data->attemptid);

        // add new record
        if (! $newid = $DB->insert_record('englishcentral_phonemes', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }

        // store mapping from $oldid to $newid
        $this->set_mapping('englishcentral_phonemes', $oldid, $newid);
    }
    
    protected function after_execute() {
        // Add englishcentral related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_englishcentral', 'intro', null);
    }
}
