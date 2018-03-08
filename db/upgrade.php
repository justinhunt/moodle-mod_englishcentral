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
    global $CFG, $DB;

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

        upgrade_mod_savepoint(true, "$newversion", 'englishcentral');
    }

    $newversion = 2018012403;
    if ($oldversion < $newversion) {

        // =============================================
        // create USERIDS table
        // (this will be renamed to ACCOUNTIDS later)
        // =============================================

        $table = new xmldb_table('englishcentral_userids');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('ecuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('engluser_userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        $table->add_index('engluser_ecuserid', XMLDB_INDEX_UNIQUE, array('ecuserid'));

        xmldb_englishcentral_create_table($dbman, $table);

        // =============================================
        // create VIDEOS table
        // =============================================

        $table = new xmldb_table('englishcentral_videos');

        $table->add_field('id',        XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('ecid',      XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('videoid',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('visible',   XMLDB_TYPE_INTEGER,  '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER,  '6', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $table->add_index('englvide_ecid', XMLDB_INDEX_NOTUNIQUE, array('ecid'));
        $table->add_index('englvide_videoid', XMLDB_INDEX_NOTUNIQUE, array('videoid'));
        $table->add_index('englvide_sortorder', XMLDB_INDEX_NOTUNIQUE, array('ecid,sortorder'));

        xmldb_englishcentral_create_table($dbman, $table);

        // =============================================
        // transfer videoids
        // =============================================

        if ($records = $DB->get_records('englishcentral')) {
            $table = 'englishcentral_videos';
            foreach ($records as $record) {
                if (empty($record->videoid)) {
                    continue;
                }
                $params = array('ecid' => $record->id,
                                'videoid' => $record->videoid);
                if ($DB->record_exists($table, $params)) {
                    continue;
                }
                $DB->insert_record($table, $params);
            }
        }

        // =============================================
        // remove fields from ENGLISHCENTRAL table
        // =============================================

        $table = new xmldb_table('englishcentral');
        $fields = array('videotitle', 'videoid', 'goalperiod',
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
            new xmldb_field('availablefrom',  XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'studygoal'),
            new xmldb_field('availableuntil', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'availablefrom'),
            new xmldb_field('readonlyfrom',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'availableuntil'),
            new xmldb_field('readonlyuntil',  XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'readonlyfrom')
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

        $table->add_field('id',              XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('ecid',            XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('userid',          XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('videoid',         XMLDB_TYPE_INTEGER, '10');
        $table->add_field('linestotal',      XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('totalactivetime', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('watchedcomplete', XMLDB_TYPE_INTEGER,  '2');
        $table->add_field('activetime',      XMLDB_TYPE_INTEGER, '10');
        $table->add_field('datecompleted',   XMLDB_TYPE_INTEGER, '10');
        $table->add_field('linesrecorded',   XMLDB_TYPE_INTEGER, '10');
        $table->add_field('lineswatched',    XMLDB_TYPE_INTEGER, '10');
        $table->add_field('points',          XMLDB_TYPE_INTEGER, '10');
        $table->add_field('recordingcomplete', XMLDB_TYPE_INTEGER, '2');
        $table->add_field('sessiongrade',    XMLDB_TYPE_CHAR,   '255');
        $table->add_field('sessionscore',    XMLDB_TYPE_INTEGER, '10');
        $table->add_field('status',          XMLDB_TYPE_INTEGER,  '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated',     XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('englatte_ecid', XMLDB_INDEX_NOTUNIQUE, array('ecid'));
        $table->add_index('englatte_userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('englatte_videoid', XMLDB_INDEX_NOTUNIQUE, array('videoid'));

        xmldb_englishcentral_replace_table($dbman, $table, $fields, $oldname);

        // =============================================
        // replace PHONEMES table
        // =============================================

        $table = new xmldb_table('englishcentral_phonemes');
        $fields = array('englishcentralid' => 'ecid');
        $oldname = 'englishcentral_phs';

        $table->add_field('id',          XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('ecid',        XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('attemptid',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('userid',      XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('phoneme',     XMLDB_TYPE_CHAR,   '255', null, null,          null, '');
        $table->add_field('badcount',    XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('goodcount',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary',             XMLDB_KEY_PRIMARY,     array('id'));
        $table->add_index('englphs_ecid',      XMLDB_INDEX_NOTUNIQUE, array('ecid'));
        $table->add_index('englphs_attemptid', XMLDB_INDEX_NOTUNIQUE, array('attemptid'));
        $table->add_index('englphs_userid',    XMLDB_INDEX_NOTUNIQUE, array('userid'));

        xmldb_englishcentral_replace_table($dbman, $table, $fields, $oldname);

        upgrade_mod_savepoint(true, "$newversion", 'englishcentral');
    }

    $newversion = 2018012805;
    if ($oldversion < $newversion) {

        $config = get_config('englishcentral');
        foreach ($config as $name => $value) {
            set_config($name, $value, 'mod_englishcentral');
            unset_config($name, 'englishcentral');
        }

        upgrade_mod_savepoint(true, "$newversion", 'englishcentral');
    }

    $newversion = 2018020417;
    if ($oldversion < $newversion) {

        // rename timing fields in main "englishcentral" table
        $table = new xmldb_table('englishcentral');
        $fields = array(
            'activityopen'  => new xmldb_field('availablefrom',  XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
            'activityclose' => new xmldb_field('availableuntil', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
            'videoopen'     => new xmldb_field('readonlyfrom',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
            'videoclose'    => new xmldb_field('readonlyuntil',  XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0')
        );
        foreach ($fields as $newname => $field) {
            $oldexists = $dbman->field_exists($table, $field);
            $newexists = $dbman->field_exists($table, $newname);
            if ($oldexists) {
                if ($newexists) {
                    $dbman->drop_field($table, $field);
                    $oldexists = false;
                } else {
                    $dbman->rename_field($table, $field, $newname);
                    $newexists = true;
                }
            }
            $field->setName($newname);
            if ($newexists) {
                $dbman->change_field_type($table, $field);
            } else {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_mod_savepoint(true, "$newversion", 'englishcentral');
    }

    $newversion = 2018021020;
    if ($oldversion < $newversion) {

        // =============================================
        // create ACCOUNTIDS table
        // =============================================

        $table = new xmldb_table('englishcentral_accountids');
        $fields = array('ecuserid' => 'accountid');
        $oldname = 'englishcentral_userids';

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('accountid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('engluser_userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Use NOTUNIQUE, because initially the accountid is set to "0" for all users
        // Later, it gets set to a unique non-zero value
        $table->add_index('engluser_accountid', XMLDB_INDEX_NOTUNIQUE, array('accountid'));

        xmldb_englishcentral_replace_table($dbman, $table, $fields, $oldname);

        // =============================================
        // adjust VIDEOS table
        // =============================================

        $table = new xmldb_table('englishcentral_videos');

        // remove videotitle field
        $field = new xmldb_field('videotitle');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // add visible field
        $field = new xmldb_field('visible', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'videoid');
        if (! $dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // add sortorder field
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0', 'visible');
        if ($dbman->field_exists($table, $field)) {
            // do nothing
        } else {
            $dbman->add_field($table, $field);

            // define new index on sortorder field
            $index = new xmldb_index('englvide_sortorder', XMLDB_INDEX_UNIQUE, array('ecid,sortorder'));

            // remove index, if it already exists
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }

            // set sortorder field on existing records
            $ecid = 0;
            $sortorder = 0;
            if ($videos = $DB->get_records($table->getName(), array(), 'ecid,id')) {
                foreach ($videos as $video) {
                    if ($ecid && $ecid==$video->ecid) {
                        $sortorder++;
                    } else {
                        $sortorder = 1;
                    }
                    $ecid = $video->ecid;
                    $DB->set_field($table->getName(), 'sortorder', $sortorder, array('id' => $video->id));
                }
            }

            // add index on sortorder
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, "$newversion", 'englishcentral');
    }

    $newversion = 2018022532;
    if ($oldversion < $newversion) {

        // Define table englishcentral_attempts to be created.
        $table = new xmldb_table('englishcentral_attempts');

        // define modified  field names (OLD => NEW)
        $fields = array(
            'lineswatched'      => 'watchcount',
            'watchedcomplete'   => 'watchcomplete',
            'linestotal'        => 'speaktotal',
            'linesrecorded'     => 'speakcount',
            'recordingcomplete' => 'speakcomplete',
            'points'            => 'totalpoints',
            'totalactivetime'   => 'totaltime',
            'datecompleted'     => 'timecompleted',
        );

        $table->add_field('id',            XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('ecid',          XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('userid',        XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('videoid',       XMLDB_TYPE_INTEGER, '10');

        $table->add_field('watchcomplete', XMLDB_TYPE_INTEGER,  '2');
        $table->add_field('watchtotal',    XMLDB_TYPE_INTEGER, '10'); // number of watchable lines
        $table->add_field('watchcount',    XMLDB_TYPE_INTEGER, '10'); // number of lines watched
        $table->add_field('watchlineids',  XMLDB_TYPE_TEXT);          // comma-separated list of line ids

        $table->add_field('learncomplete', XMLDB_TYPE_INTEGER,  '2');
        $table->add_field('learntotal',    XMLDB_TYPE_INTEGER, '10'); // number of learnable words
        $table->add_field('learncount',    XMLDB_TYPE_INTEGER, '10'); // number of words learned
        $table->add_field('learnwordids',  XMLDB_TYPE_TEXT);          // comma-separated list of word ids

        $table->add_field('speakcomplete', XMLDB_TYPE_INTEGER,  '2');
        $table->add_field('speaktotal',    XMLDB_TYPE_INTEGER, '10'); // number of speakable lines
        $table->add_field('speakcount',    XMLDB_TYPE_INTEGER, '10'); // number of lines spoken
        $table->add_field('speaklineids',  XMLDB_TYPE_TEXT);          // comma-separated list of line ids

        $table->add_field('totalpoints',   XMLDB_TYPE_INTEGER, '10');
        $table->add_field('sessiongrade',  XMLDB_TYPE_CHAR,   '255'); // EC grade (e.g. "A")
        $table->add_field('sessionscore',  XMLDB_TYPE_INTEGER, '10'); // EC numeric score (e.g. 97)

        $table->add_field('activetime',    XMLDB_TYPE_INTEGER, '10');
        $table->add_field('totaltime',     XMLDB_TYPE_INTEGER, '10');

        $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('timecreated',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // the following fields doesn't seem to be necessary
        $table->add_field('status',        XMLDB_TYPE_INTEGER,  '2', null, XMLDB_NOTNULL, null, '0');

        // keys for englishcentral_attempts
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // indexes for englishcentral_attempts
        $table->add_index('englatte_ecid', XMLDB_INDEX_NOTUNIQUE, array('ecid'));
        $table->add_index('englatte_userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('englatte_videoid', XMLDB_INDEX_NOTUNIQUE, array('videoid'));

        // create/modify the table
        xmldb_englishcentral_create_table($dbman, $table, $fields);

        upgrade_mod_savepoint(true, "$newversion", 'englishcentral');
    }

    $newversion = 2018022735;
    if ($oldversion < $newversion) {
        require_once $CFG->dirroot.'/mod/englishcentral/lib.php';

        // update/create grades for all hotpots

        // set up sql strings
        $strupdating = get_string('updatinggrades', 'mod_englishcentral');
        $select = 'ec.*, cm.idnumber AS cmidnumber';
        $from   = '{englishcentral} ec, {course_modules} cm, {modules} m';
        $where  = 'ec.id = cm.instance AND cm.module = m.id AND m.name = ?';
        $params = array('englishcentral');

        // get previous record index (if any)
        $configname = 'updategrades';
        $configvalue = get_config('mod_englishcentral', $configname);
        if (is_numeric($configvalue)) {
            $i_min = intval($configvalue);
        } else {
            $i_min = 0;
        }

        if ($i_max = $DB->count_records_sql("SELECT COUNT('x') FROM $from WHERE $where", $params)) {
            if ($rs = $DB->get_recordset_sql("SELECT $select FROM $from WHERE $where", $params)) {
                if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
                    $bar = false;
                } else {
                    $bar = new progress_bar('englishcentralupgradegrades', 500, true);
                }
                $i = 0;
                foreach ($rs as $ec) {

                    // update grade
                    if ($i >= $i_min) {
                        upgrade_set_timeout(); // apply for more time (3 mins)
                        englishcentral_update_grades($ec);
                    }

                    // update progress bar
                    $i++;
                    if ($bar) {
                        $bar->update($i, $i_max, $strupdating.": ($i/$i_max)");
                    }

                    // update record index
                    if ($i > $i_min) {
                        set_config($configname, $i, 'mod_englishcentral');
                    }
                }
                $rs->close();
            }
        }

        // delete the record index
        unset_config($configname, 'mod_englishcentral');

        upgrade_mod_savepoint(true, "$newversion", 'englishcentral');
    }

    $newversion = 2018030651;
    if ($oldversion < $newversion) {
        // select attempts records whose ecid + videoid does not exist in videos table
        $select = 'ea.*';
        $from   = '{englishcentral_attempts} ea '.
                  'LEFT JOIN {englishcentral_videos} ev ON ea.ecid = ev.ecid AND ea.videoid = ev.videoid';
        $where  = 'ea.ecid = ? AND ev.id IS NULL';
        $params = array(1); // this issue only affects attempts with ecid==1

        // SELECT ea.* FROM mdl_englishcentral_attempts ea
        //        LEFT JOIN mdl_englishcentral_videos ev
        //               ON ea.ecid = ev.ecid
        //              AND ea.videoid = ev.videoid
        //  WHERE ea.ecid = 1
        //    AND ev.id IS NULL;
        if ($orphans = $DB->get_records_sql("SELECT $select FROM $from WHERE $where", $params)) {
            $fields = array('watchcount' => 'watchlineids',
                            'learncount' => 'learnwordids',
                            'speakcount' => 'speaklineids');
            foreach ($orphans as $orphan) {
                // merge all attempts by this user at this video
                // try to locate a valid $ecid while we're at it
                $ecid = 0;
                $record = null; // new attempt
                $table = 'englishcentral_attempts';
                $params = array('userid' => $orphan->userid,
                                'videoid' => $orphan->videoid);
                $attempts = $DB->get_records($table, $params, 'id');
                foreach ($attempts as $attempt) {
                    if ($record===null) {
                        $record = clone($attempt);
                        foreach ($fields as $field) {
                            $record->$field = array();
                        }
                    } else {
                        // remove this $attempt
                        $DB->delete_records($table, array('id' => $attempt->id));
                    }
                    // transfer attempt details
                    foreach ($fields as $field) {
                        $record->$field += array_fill_keys(explode(',', $attempt->$field), 1);
                    }
                    if ($ecid==0) {
                        $ecid = ($attempt->ecid==$orphan->ecid ? 0 : $attempt->ecid);
                    }
                }
                foreach ($fields as $count => $field) {
                    $record->$field = array_keys($record->$field);
                    $record->$field = array_filter($record->$field);
                    $record->$count = count($record->$field);
                    $record->$field = implode(',', $record->$field);
                }
                if ($ecid==0) {
                    if ($ecid = $DB->get_records('englishcentral_videos', array('videoid' => $orphan->videoid))) {
                        $ecid = reset($ecid);
                        $ecid = $ecid->ecid;
                    } else {
                        $ecid = 0; // shouldn't happen !!
                    }
                }
                if ($ecid) {
                    $record->ecid = $ecid;
                    $DB->update_record($table, $record);
                } else {
                    // sorry, we couldn't rescue this orphan :-(
                    // probably because we have no record of its videoid
                    $DB->delete_records($table, array('id' => $record->id));
                }
            }
        }

        upgrade_mod_savepoint(true, "$newversion", 'englishcentral');
    }

    return true;
}

function xmldb_englishcentral_replace_table($dbman, $table, $fields, $oldname) {
    global $DB;

    $table_exists = $dbman->table_exists($table);
    xmldb_englishcentral_create_table($dbman, $table);

    if ($dbman->table_exists($oldname)) {
        if ($records = $DB->get_records($oldname)) {
            foreach ($records as $record) {
                if ($table_exists && $DB->record_exists($table->getName(), array('id' => $record->id))) {
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

function xmldb_englishcentral_create_table($dbman, $table, $fields=array()) {
    global $DB;
    if ($dbman->table_exists($table)) {

        // remove all existing indexes and keys (except PRIMARY key)
        $indexes = $DB->get_indexes($table->getName());
        foreach ($indexes as $indexname => $index) {
            if ($indexname=='primary') {
                continue;
            }
            if (isset($index['unique']) && $index['unique']) {
                $type = XMLDB_INDEX_UNIQUE;
            } else {
                $type = XMLDB_INDEX_NOTUNIQUE;
            }
            $index = new xmldb_index($indexname, $type, $index['columns']);
            $dbman->drop_index($table, $index);
        }

        // add/change fields
        $previous = ''; // name of previous field in DB
        foreach ($table->getFields() as $field) {
            if ($previous) {
                $field->setPrevious($previous);
            }
            $newname = $field->getName();
            $oldname = array_search($newname, $fields);
            if ($oldname && $dbman->field_exists($table, $oldname)) {
                $field->setName($oldname);
                $dbman->rename_field($table, $field, $newname);
                $field->setName($newname);
            }
            if ($dbman->field_exists($table, $field)) {
                $dbman->change_field_type($table, $field);
            } else {
                $dbman->add_field($table, $field);
            }
            $previous = $field->getName();
        }

        // (re)add indexes
        foreach ($table->getIndexes() as $index) {
            if ($index->getName()=='primary') {
                continue;
            }
            $dbman->add_index($table, $index);
        }
        foreach ($table->getKeys() as $index) {
            if ($index->getName()=='primary') {
                continue;
            }
            $index = new xmldb_index($index->getName(), $index->getType(), $index->getFields());
            $dbman->add_index($table, $index);
        }
    } else {
        $dbman->create_table($table);
    }
}
