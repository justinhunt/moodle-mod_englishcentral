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
 * mod/englishcentral/db/upgradelib.php
 *
 * @package    mod
 * @subpackage englishcentral
 * @copyright  2025 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/**
 * Replaces an old database table with a new one during plugin upgrade.
 *
 * This function is used during the upgrade of the English Central activity plugin
 * for Moodle. It ensures the new table is created, transfers data from the old table
 * (if it exists), and then drops the old table.
 *
 * @param xmldb_manager $dbman  The database manager object used for schema operations.
 * @param xmldb_table   $table  The new table definition.
 * @param array         $fields An associative array mapping old field names to new field names.
 * @param string        $oldname The name of the old table to be replaced.
 */
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

/**
 * Creates or updates a database table during plugin upgrade.
 *
 * This function ensures that the specified table exists and is up-to-date.
 * If the table already exists, it updates fields and indexes based on the provided definition.
 * If it does not exist, it creates the table from scratch.
 *
 * @param xmldb_manager $dbman  The database manager object used for schema operations.
 * @param xmldb_table   $table  The table definition.
 * @param array         $fields Optional associative array mapping old field names to new field names.
 */
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

/**
 * xmldb_englishcentral_check_structure
 *
 * @uses $CFG
 * @uses $DB
 * @param object $dbman the database manager
 * @param array $tablenames (optional, default=null) specific tables to check
 * @param array $tableprefix (optional, default=englishcentral) the prefix for DB tables belonging to this plugin
 * @param array $pluginname (optional, default=mod_englishcentral) the full frakenstyle name of this plugin e.g. mod_englishcentral
 * @param array $plugindir (optional, default=mod/englishcentral) the relative path to main folder for this plugin's directory
 * @return void (but may update database structure)
 */
function xmldb_englishcentral_check_structure($dbman, $tablenames=null, $tableprefix='englishcentral',
                                    $pluginname='mod_englishcentral', $plugindir='mod/englishcentral') {
    global $CFG, $DB;

    // To see what tables/fields/indexes were added/changed/dropped,
    // set the $debug flag to TRUE during development of this script.
    $debug = false;

    // Define array [$pluginname => boolean] to cache
    // whether or not we have checked all tables for this plugin.
    static $checkedall = [];

    // Define array [$pluginname => [$tablenames]] to cache
    // which tables for this plugin have already been checked.
    static $checked = [];

    // If this is the frst time to check any tables for this plugin,
    // initialize its $checkedall flag and $checked array.
    if (! array_key_exists($pluginname, $checkedall)) {
        $checkedall[$pluginname] = false;
        $checked[$pluginname] = [];
    }

    // If we have already checked all tables for this plugin,
    // we can stop here.
    if ($checkedall[$pluginname]) {
        return true;
    }

    // If we are going to check all tables for this $plugin,
    // then we can set its $checkall flag to "true".
    if ($tablenames === null) {
        $checkedall[$pluginname] = true;
    }

    // Locate the XML file for this plugin, and try to read it.
    $filepath = "/$plugindir/db/install.xml";
    $file = new xmldb_file($CFG->dirroot.$filepath);

    if (! $file->fileExists()) {
        // Presumably this would only happen on a development site.
        $error = "XML file not found: $filepath";
        throw new ddl_exception('ddlxmlfileerror', null, $error);
    }

    // Parse the the structure of the XML.
    $loaded = $file->loadXMLStructure();
    $structure = $file->getStructure();

    // Check that the XML file could be loaded.
    if (! $file->isLoaded()) {
        if ($structure && ($error = $structure->getAllErrors())) {
            $error = implode (', ', $error);
            $error = "Errors found in XMLDB file ($filepath): ". $error;
        } else {
            $error = "XMLDB file not loaded ($filepath)";
        }
        throw new ddl_exception('ddlxmlfileerror', null, $error);
    }

    // Get a list of tables for this plugin that are defined in the XML.
    if (! $tables = $structure->getTables()) {
        $error = "No tables found in XML file ($filepath)";
        throw new ddl_exception('ddlxmlfileerror', null, $error);
    }

    // Get a list of "$errors" in the schema. Actually, an "$error"
    // is really just something different between the XML schema
    // and the current structure of the Moodle database.
    $errors = $dbman->check_database_schema($structure);
    if ($tablenames) {
        // We are only interested in specific tables.
        $keys = array_values($tablenames);
    } else {
        $keys = array_keys($errors);
    }

    // Extract only errors relating to tables for this plugin.
    $keys = preg_grep('/^'.$tableprefix.'(_|$)/', $keys);
    $errors = array_intersect_key($errors, array_flip($keys));

    // Loop through $tablenames mentioned in the $errors for this plugin.
    foreach ($errors as $tablename => $messages) {

        // Skip tables that have already been checked.
        if (array_key_exists($tablename, $checked[$pluginname])) {
            continue;
        }
        $checked[$pluginname][$tablename] = true;

        $i = $file->findObjectInArray($tablename, $tables);
        if (is_numeric($i)) {
            // A table in the XML file.
            // It may or may not exist in the DB.
            $table = $tables[$i];
        } else {
            // A table that is in the DB but not in the XML file.
            // In other words, a table that is to be removed.
            // Perhaps it should have been renamed, but it's too late now.
            $table = new xmldb_table($tablename);
        }

        // Get current (uncached) info about columns and indexes in database.
        $columns = $DB->get_columns($tablename, false);
        $indexes = $DB->get_indexes($tablename, false);

        // If we try to change any fields that are indexed, the $dbman will abort with an error.
        // As a workaround, we make a note of which fields are used in the keys/indexes,
        // and then, if any of them are to be changed, we first remove the key/index,
        // then change the field and finally add the key/index back to the table.

        $special = (object)[
            'keyfields' => [],
            'indexfields' => [],
        ];

        $dropped = (object)[
            'keys' => [],
            'indexes' => [],
        ];

        // Map each key field onto an array of keys that use the field.
        foreach ($table->getKeys() as $key) {
            foreach ($key->getFields() as $field) {
                if ($key->getType() == XMLDB_KEY_PRIMARY) {
                    // We can never alter the "id" field.
                    continue;
                }
                if (empty($special->keyfields[$field])) {
                    $special->keyfields[$field] = [];
                }
                $special->keyfields[$field][] = $key;
            }
        }

        // Map each index field onto an array of indexes that use the field.
        foreach ($table->getIndexes() as $index) {
            foreach ($index->getFields() as $field) {
                if (empty($special->indexfields[$field])) {
                    $special->indexfields[$field] = [];
                }
                $special->indexfields[$field][] = $index;
            }
        }

        // Loop through the error messages relating to this table.
        foreach ($messages as $message) {

            switch (true) {

                // Moodle <= 2.7 uses "Table".
                // Moodle >= 2.8 uses "table".
                case preg_match('/[Tt]able is missing/', $message):
                    $dbman->create_table($table);
                    if ($debug) {
                        echo "Table $tablename was created<br>";
                    }
                    break;

                // Moodle <= 2.7 uses "Table".
                // Moodle >= 2.8 uses "table".
                case preg_match('/[Tt]able is not expected/', $message):
                    $dbman->drop_table($table);
                    if ($debug) {
                        echo "Table $tablename was dropped<br>";
                    }
                    break;

                // Moodle <= 2.7 uses "Field".
                // Moodle >= 2.8 uses "column".
                case preg_match('/(Field|column) (.*?) (.*)/', $message, $match):
                    $name = trim($match[2], "'");
                    $text = trim($match[3]);

                    $fields = $table->getFields();
                    $i = $table->findObjectInArray($name, $fields);

                    if (is_numeric($i)) {
                        $field = $fields[$i];
                    } else {
                        $field = new xmldb_field($name);
                    }

                    if (array_key_exists($name, $special->keyfields)) {
                        foreach ($special->keyfields[$name] as $key) {
                            // There is no "key_exists" method, but "index_exists"
                            // seems to work if we give it an "xmldb_index" object.
                            $index = new xmldb_index($key->getName(), $key->getType(), $key->getFields());
                            if ($dbman->index_exists($table, $index)) {
                                $dbman->drop_key($table, $key);
                                $dropped->keys[] = $key;
                            }
                        }
                        // Remove this field from the list of keyfields,
                        // as it will not be needed again.
                        unset($special->keyfields[$name]);
                    }

                    if (array_key_exists($name, $special->indexfields)) {
                        foreach ($special->indexfields[$name] as $index) {
                            if ($dbman->index_exists($table, $index)) {
                                $dbman->drop_index($table, $index);
                                $dropped->indexes[] = $index;
                            }
                        }
                        // Remove this field from the list of index fields,
                        // as it will not be needed again.
                        unset($special->indexfields[$name]);
                    }

                    if (substr($text, 0, 15) == 'is not expected') {
                        // E.g. column 'xyz' is not expected.
                        if ($dbman->field_exists($table, $field)) {
                            $dbman->drop_field($table, $field);
                            if ($debug) {
                                echo "Field $tablename.$name was dropped<br>";
                            }
                        }
                    } else {
                        // E.g. column 'xyz' is missing.
                        if ($dbman->field_exists($table, $field)) {
                            if (substr($text, 0, 18) == 'should be NOT NULL') {
                                $default = $field->getDefault();
                                $DB->set_field_select($tablename, $name, $default, "$name IS NULL");
                                if ($debug) {
                                    echo "NULL values in were set to $default ($tablename.$name)<br>";
                                }
                            }
                            $dbman->change_field_type($table, $field);
                            if ($debug) {
                                echo "Field $tablename.$name was updated<br>";
                            }
                        } else {
                            $dbman->add_field($table, $field);
                            if ($debug) {
                                echo "Field $tablename.$name was added<br>";
                            }
                        }
                    }
                    break;

                // Note: early versions of Moodle may not have this.
                case preg_match('/CREATE(.*?)INDEX(.*?)ON(.*?);/', $message, $match):
                    $DB->execute(rtrim($match[0], '; '));
                    if ($debug) {
                        echo 'Index '.$match[1].' was added<br>';
                    }
                    break;

                // Note: early versions of Moodle may not have this.
                case preg_match("/Unexpected index '(\w+)'/", $message, $match):
                    $name = $match[1];
                    if (array_key_exists($name, $indexes)) {
                        $index = new xmldb_index($name);
                        $index->setFromADOIndex($indexes[$name]);
                        if ($dbman->index_exists($table, $index)) {
                            $dbman->drop_index($table, $index);
                        }
                        unset($indexes[$name]);
                        if ($debug) {
                            echo 'Index '.$match[1].' was dropped<br>';
                        }
                    }
                    break;

                default:
                    if ($debug) {
                        echo '<p>Unknown XMLDB error in '.$pluginname.':<br>'.$message.'</p>';
                        die;
                    }
            }
        }

        // Restore any keys that were dropped.
        foreach ($dropped->keys as $key) {
            $index = new xmldb_index($key->getName(), $key->getType(), $key->getFields());
            if (! $dbman->index_exists($table, $index)) {
                $dbman->add_key($table, $key);
            }
        }

        // Restore any indexes that were dropped.
        foreach ($dropped->indexes as $index) {
            if (! $dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
    }
}
