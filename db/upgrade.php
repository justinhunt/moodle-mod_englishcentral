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

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

  
    // Second example, some hours later, the same day 2007/04/01
    // two more fields and one index were added to install.xml (note the micro increment
    // "01" in the last two digits of the version
    if ($oldversion < 2015031501) {

        // Define field timecreated to be added to englishcentral
        $table = new xmldb_table('englishcentral');
        $field = new xmldb_field('lightboxmode', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Add field timecreated
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        // Another save point reached
        upgrade_mod_savepoint(true, 2015031501, 'englishcentral');
    }



    return true;
}
