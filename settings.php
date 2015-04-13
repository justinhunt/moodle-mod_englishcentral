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



	  $settings->add(new admin_setting_configtext('englishcentral/consumerkey',
        get_string('consumerkey', 'englishcentral'), get_string('consumerkeyexplain', 'englishcentral'), 'YOUR CONSUMER KEY', PARAM_TEXT));
		
	 $settings->add(new admin_setting_configtext('englishcentral/consumersecret',
        get_string('consumersecret', 'englishcentral'), get_string('consumersecretexplain', 'englishcentral'), 'YOUR CONSUMER SECRET', PARAM_TEXT));

	$settings->add(new admin_setting_configcheckbox('englishcentral/lightboxmode', get_string('lightboxmode', 'englishcentral'), '', 1));
	$settings->add(new admin_setting_heading('englishcentral/defaultsettings', get_string('defaultsettings', 'englishcentral'), ''));
	$settings->add(new admin_setting_configcheckbox('englishcentral/watchmode', get_string('watchmode', 'englishcentral'), '', 1));
	$settings->add(new admin_setting_configcheckbox('englishcentral/speakmode', get_string('speakmode', 'englishcentral'), '', 1));
	$settings->add(new admin_setting_configcheckbox('englishcentral/learnmode', get_string('learnmode', 'englishcentral'), '', 0));
	$settings->add(new admin_setting_configcheckbox('englishcentral/simpleui', get_string('simpleui', 'englishcentral'), '', 0));
	$settings->add(new admin_setting_configcheckbox('englishcentral/speaklitemode', get_string('speaklitemode', 'englishcentral'), '', 0));
	$settings->add(new admin_setting_configcheckbox('englishcentral/hiddenchallengemode', get_string('hiddenchallengemode', 'englishcentral'), '', 0));

}
