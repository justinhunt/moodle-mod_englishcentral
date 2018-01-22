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
 * This file keeps track of upgrades to the englishcentral module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_englishcentral
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute englishcentral upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_englishcentral_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    $newversion = 2015031501;
    if ($oldversion < $newversion) {

        // Define field timecreated to be added to englishcentral
        $table = new xmldb_table('englishcentral');
        $field = new xmldb_field('lightboxmode', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Add field lightboxmode
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Another save point reached
        upgrade_mod_savepoint(true, "$newversion", 'englishcentral');
    }

    $newversion = 2018012201;
    if ($oldversion < $newversion) {

        // =============================================
        // create VIDEOS table
        // =============================================

        $table = new xmldb_table('englishcentral_videos');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ecid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('videoid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('englvide_ecid', XMLDB_KEY_FOREIGN, array('ecid'), 'englishcentral', array('id'));

        $table->add_index('englvide_videoid', XMLDB_INDEX_NOTUNIQUE, array('videoid'));

        if (! $dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // =============================================
        // transfer videoids
        // =============================================

        if ($records = $DB->get_records('englishcentral')) {
            $table = 'englishcentral_videos';
            foreach ($records as $record) {
                if (empty($record->videoid)) {
                    continue;
                }
                $record = array('ecid' => $record->id,
                                'videoid' => $record->videoid)
                if ($DB->record_exists($table, $record)) {
                    continue;
                }
                $DB->insert_record($table, $record);
            }
        }

        // =============================================
        // remove fields from ENGLISHCENTRAL table
        // =============================================

        $table = new xmldb_table('englishcentral');
        $fields = array('videotitle', 'videoid',
                        'watchmode', 'speakmode', 'learnmode',
                        'hiddenchallengemode', 'speaklitemode',
                        'lightboxmode', 'simpleui', 'maxattempts');
        foreach ($fields as $field) {
            $field = new xmldb_field($field);
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        // =============================================
        // add fields to ENGLISHCENTRAL table
        // =============================================

        $table = new xmldb_table('englishcentral');
        $fields = array(
            new xmldb_field('watchgoal',  XMLDB_TYPE_INTEGER,  '6', null, XMLDB_NOTNULL, null, '0', 'introformat'),
            new xmldb_field('learngoal',  XMLDB_TYPE_INTEGER,  '6', null, XMLDB_NOTNULL, null, '0', 'watchgoal'),
            new xmldb_field('speakgoal',  XMLDB_TYPE_INTEGER,  '6', null, XMLDB_NOTNULL, null, '0', 'learngoal'),
            new xmldb_field('studygoal',  XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'speakgoal'),
            new xmldb_field('goalperiod', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'goalperiod')
        );

        foreach ($fields as $field) {
            if ($dbman->field_exists($table, $field)) {
                $dbman->change_field_type($table, $field);
            } else {
                $dbman->add_field($table, $field);
            }
        }

        // =============================================
        // replace ATTEMPTS table
        // =============================================


        $table = new xmldb_table('englishcentral_attempts');
        $fields = array('englishcentralid' => 'ecid');
        $oldname = 'englishcentral_attempt';

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ecid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('videoid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('linestotal', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('totalactivetime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('watchedcomplete', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('activetime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('datecompleted', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('linesrecorded', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('lineswatched', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('points', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('recordingcomplete', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('sessiongrade', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('sessionscore', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('englatte_ecid', XMLDB_KEY_FOREIGN, array('ecid'), 'englishcentral', array('id'));
        $table->add_key('englatte_userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        $table->add_index('englatte_videoid', XMLDB_INDEX_NOTUNIQUE, array('videoid'));

        xmldb_englishcentral_replace_table($dbman, $table, $fields, $oldname);

        // =============================================
        // replace PHONEMES table
        // =============================================

        $table = new xmldb_table('englishcentral_phonemes');
        $fields = array('englishcentralid' => 'ecid');
        $oldname = 'englishcentral_phs';

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ecid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('phoneme', XMLDB_TYPE_CHAR, '255', null, null, null, '');
        $table->add_field('badcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('goodcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('englphs_ecid', XMLDB_KEY_FOREIGN, array('ecid'), 'englishcentral', array('id'));
        $table->add_key('englphs_attemptid', XMLDB_KEY_FOREIGN, array('attemptid'), 'englishcentral_attempts', array('id'));
        $table->add_key('englphs_userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        xmldb_englishcentral_replace_table($dbman, $table, $fields, $oldname);

        upgrade_mod_savepoint(true, "$newversion", 'englishcentral');
    }

    return true;
}

function xmldb_englishcentral_replace_table($dbman, $table, $fields, $oldname) {
    global $DB;

    $tablexists = $dbman->table_exists($table);
    if ($tablexists==false) {
        $dbman->create_table($table);
    }

    if ($dbman->table_exists($oldname)) {
        if ($records = $DB->get_records($oldname, null)) {
            foreach ($records as $record) {
                if ($tablexists && $DB->record_exists($table->getName(), array('id' => $record->id))) {
                    continue; // record has already been transferred
                }
                foreach ($fields as $oldfield => $newfield) {
                    $record->$newfield = $record->$oldfield;
                    unset($record->$oldfield);
                }
                $DB->insert_record($table->getName(), $record);
            }
        }
        $dbman->drop_table(new xmldb_table($oldname));
    }
}
