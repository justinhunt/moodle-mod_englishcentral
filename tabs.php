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
* Sets up the tabs at the top of the englishcentral　for teachers.
*
* This file was adapted from the mod/lesson/tabs.php
*
 * @package mod_englishcentral
 * @copyright  2014 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
*/

defined('MOODLE_INTERNAL') || die();

/// This file to be included so we can assume config.php has already been included.
global $DB;
if (empty($englishcentral)) {
    print_error('cannotcallscript');
}
if (!isset($currenttab)) {
    $currenttab = '';
}
if (!isset($cm)) {
    $cm = get_coursemodule_from_instance('englishcentral', $englishcentral->id);
    $context = context_module::instance($cm->id);
}
if (!isset($course)) {
    $course = $DB->get_record('course', array('id' => $englishcentral->course));
}

$tabs = $row = $inactive = $activated = array();


$row[] = new tabobject('view', "$CFG->wwwroot/mod/englishcentral/view.php?id=$cm->id", get_string('view', 'englishcentral'), get_string('previewenglishcentral', 'englishcentral', format_string($englishcentral->name)));
$row[] = new tabobject('reports', "$CFG->wwwroot/mod/englishcentral/reports.php?id=$cm->id", get_string('reports', 'englishcentral'), get_string('viewreports', 'englishcentral'));

$tabs[] = $row;

print_tabs($tabs, $currenttab, $inactive, $activated);
