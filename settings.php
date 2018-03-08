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
 * englishcentral module admin settings and defaults
 *
 * @package    mod
 * @subpackage englishcentral
 * @copyright  2014 Justin Hunt poodllsupport@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $plugin = 'mod_englishcentral';
    $link = new moodle_url('/mod/englishcentral/support.php');
    $link = html_writer::tag('a', 'EnglishCentral.com', array('href' => $link, 'target' => 'EC'));
    // whenever possible, the support URL will display a form in the browser's preferred language

    $name = 'partnerid';
    $label = get_string($name, $plugin);
    $explain = get_string($name.'explain', $plugin, $link);
    $default = get_string($name.'default', $plugin);
    $settings->add(new admin_setting_configtext("$plugin/$name", $label, $explain, $default, PARAM_TEXT));

    $name = 'consumerkey';
    $label = get_string($name, $plugin);
    $explain = get_string($name.'explain', $plugin, $link);
    $default = get_string($name.'default', $plugin);
    $settings->add(new admin_setting_configtext("$plugin/$name", $label, $explain, $default, PARAM_TEXT));

    $name = 'consumersecret';
    $label = get_string($name, $plugin);
    $explain = get_string($name.'explain', $plugin, $link);
    $default = get_string($name.'default', $plugin);
    $settings->add(new admin_setting_configtext("$plugin/$name", $label, $explain, $default, PARAM_TEXT));

    $name = 'encryptedsecret';
    $label = get_string($name, $plugin);
    $explain = get_string($name.'explain', $plugin, $link);
    $default = get_string($name.'default', $plugin);
    $settings->add(new admin_setting_configtext("$plugin/$name", $label, $explain, $default, PARAM_TEXT));

    $name = 'developmentmode';
    $label = get_string($name, $plugin);
    $explain = get_string($name.'explain', $plugin);
    $default = (strpos($CFG->wwwroot, '/localhost/')===false ? 0 : 1);
    $settings->add(new admin_setting_configcheckbox("$plugin/$name", $label, $explain, $default));
}
