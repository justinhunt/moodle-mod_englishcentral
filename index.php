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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_englishcentral
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/// Replace englishcentral with the name of your module and remove this line

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // course
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_course_login($course);
$coursecontext = context_course::instance($course->id);

if (function_exists('get_log_manager')) {
    // Moodle >= 2.6
    $params = array('context' => $coursecontext);
    $event = \mod_englishcentral\event\course_module_instance_list_viewed::create($params);
    $event->trigger();
} else if (function_exists('add_to_log')) {
    // Moodle <= 2.5
    add_to_log($course->id, 'englishcentral', 'view all', 'index.php?id='.$course->id, '');
}

$PAGE->set_url('/mod/englishcentral/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'englishcentral'), 2);

if ($englishcentrals = get_all_instances_in_course('englishcentral', $course)) {

    $table = new html_table();
    $usessections = course_get_format($course)->uses_sections();
    $formatname = 'format_' . $course->format;
    if ($usessections) {
        $sectionname = get_string('sectionname', $formatname);
        $table->head  = array($sectionname, get_string('name'));
        $table->align = array('center', 'left');
    } else {
        $table->head  = array(get_string('name'));
        $table->align = array('left', 'left', 'left');
    }

    foreach ($englishcentrals as $englishcentral) {

        $params = array('id' => $englishcentral->coursemodule);
        $link = new moodle_url('/mod/englishcentral/view.php', $params);

        $label = format_string($englishcentral->name, true);
        if ($englishcentral->visible) {
            $params = array();
        } else {
            $params = array('class' => 'dimmed');
        }
        $link = html_writer::link($link, $label, $params);

        if ($usessections) {
            // Formats can use special name for section 0.
            if ($englishcentral->section == 0) {
                $englishcentral->section = get_string('section0name', $formatname);
            }
            $table->data[] = array($englishcentral->section, $link);
        } else {
            $table->data[] = array($link);
        }
    }

    echo html_writer::table($table);

} else {
    // there are no EnglishCentral activities in this course
    $label = get_string('noenglishcentrals', 'englishcentral');
    notice($label, new moodle_url('/course/view.php', array('id' => $course->id)));
}

echo $OUTPUT->footer();
