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
 * @package    mod_englishcentral
 * @copyright  2018 Gordon Bateson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Include required files */
require_once('../../config.php');

// get expected input params
// check we are logged in
require_login();

// initialize EC activity/auth objects
$ec = \mod_englishcentral\activity::create();
$auth = \mod_englishcentral\auth::create($ec);

$PAGE->set_url('/mod/englishcentral/support.php');
$PAGE->set_context($ec->context);
$PAGE->set_pagelayout('course');

// check we have suitable capability
$ec->req('config', 'moodle/site');

// initialize the renderer
$renderer = $PAGE->get_renderer($ec->plugin);
$renderer->attach_activity_and_auth($ec, $auth);

echo $renderer->header($ec->get_string('view'));
echo $renderer->show_support_form();
echo $renderer->footer();
