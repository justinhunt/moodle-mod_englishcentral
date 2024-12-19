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
 * Sets up the tabs at the top of the module view page　for teachers.
 *
 * This file was adapted from the mod/lesson/tabs.php
 *
 * @package mod_englishcentral
 * @copyright  2014 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

defined('MOODLE_INTERNAL') || die();

use mod_englishcentral\constants;

if (empty($moduleinstance)) {
    print_error('cannotcallscript');
}

if (! isset($currenttab)) {
    $currenttab = 'view';
}

if (! isset($cm)) {
    $cm = get_coursemodule_from_instance(constants::M_MODNAME, $moduleinstance->id);
    $context = context_module::instance($cm->id);
}

if (! isset($course)) {
    $course = $moduleinstance->course;
}

$userid = optional_param('userid', 0, PARAM_INT);
$config = get_config(constants::M_COMPONENT);

$tabs = $row = $inactive = $activated = [];

$url = "$CFG->wwwroot/mod/englishcentral/view.php?id=$cm->id";
$label = get_string('view', constants::M_COMPONENT);
$row[] = new tabobject('view', $url, $label, $label);

if(has_capability('mod/englishcentral:manage', $context) && $config->enablesetuptab) {
    $url = "$CFG->wwwroot/mod/englishcentral/setup.php?id=$cm->id";
    $label = get_string('setup', constants::M_COMPONENT);
    $row[] = new tabobject('setup', $url, $label, $label);
}

if(has_capability('mod/englishcentral:viewreports', $context) ) {
    $url = "$CFG->wwwroot/mod/englishcentral/report.php?id=$cm->id";
    $label = get_string('reports', constants::M_COMPONENT);
    $row[] = new tabobject('report', $url, $label, $label);
}

if(has_capability('mod/englishcentral:viewdevelopertools', $context) ) {
    $url = "$CFG->wwwroot/mod/englishcentral/developer.php?id=$cm->id";
    $label = get_string('developertools', constants::M_COMPONENT);
    $row[] = new tabobject('developer', $url, $label, $label);
}

$tabs[] = $row;

print_tabs($tabs, $currenttab, $inactive, $activated);
