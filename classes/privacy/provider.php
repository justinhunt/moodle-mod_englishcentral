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
 * Privacy Subsystem implementation for mod_englishcentral.
 *
 * @package    mod_englishcentral
 * @copyright  2018 Justin Hunt https://poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_englishcentral\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

//3.3 user_provider not backported so we use this switch to avoid errors when using same codebase for 3.3 and higher
if (interface_exists('\core_privacy\local\request\core_userlist_provider')) {
    interface the_user_provider extends \core_privacy\local\request\core_userlist_provider{}
} else {
    interface the_user_provider {};
}

/**
 * Privacy Subsystem for mod_englishcentral
 *
 * @copyright  2018 Justin Hunt https://poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin does store personal user data.
    \core_privacy\local\metadata\provider,
    // This plugin is a core_user_data_provider.
    \core_privacy\local\request\plugin\provider,
    //user provider 3.4 and above
    the_user_provider{

    use \core_privacy\local\legacy_polyfill;

    /**
     * Return meta data about this plugin.
     *
     * @param  collection $collection A list of information to add to.
     * @return collection Return the collection after adding to it.
     */
    public static function _get_metadata(collection $collection) {

        $userdetail = [
            'id' => 'privacy:metadata:attemptid',
            'ecid' => 'privacy:metadata:ecid',
            'userid' => 'privacy:metadata:userid',
            'videoid' => 'privacy:metadata:videoid',
            'watchcomplete' => 'privacy:metadata:watchcomplete',
            'watchtotal' => 'privacy:metadata:watchtotal',
            'watchcount' => 'privacy:metadata:watchcount',
            'watchlineids' => 'privacy:metadata:watchlineids',
            'learntotal' => 'privacy:metadata:learntotal',
            'learncount' => 'privacy:metadata:learncount',
            'learnwordids' => 'privacy:metadata:learnwordids',
            'speakcomplete' => 'privacy:metadata:speakcomplete',
            'speaktotal' => 'privacy:metadata:speaktotal',
            'speakcount' => 'privacy:metadata:speakcount',
            'speaklineids' => 'privacy:metadata:speaklineids',
            'totalpoints' => 'privacy:metadata:totalpoints',
            'sessiongrade' => 'privacy:metadata:sessiongrade',
            'sessionscore' => 'privacy:metadata:sessionscore',
            'activetime' => 'privacy:metadata:activetime',
            'totaltime' => 'privacy:metadata:totaltime',
            'timecompleted' => 'privacy:metadata:timecompleted',
            'timecreated' => 'privacy:metadata:timecreated',
            'status' => 'privacy:metadata:status'
        ];
        $collection->add_database_table('englishcentral_attempts', $userdetail, 'privacy:metadata:attempttable');


        $collection->add_external_location_link('englishcentral.com', [
            'accountid' => 'privacy:metadata:englishcentralcom:accountid'
        ], 'privacy:metadata:englishcentralcom');
        return $collection;
    }


    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function _get_contexts_for_userid($userid) {

        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {englishcentral} actt ON actt.id = cm.instance
            INNER JOIN {englishcentral_attempts} usert ON usert.ecid = actt.id
                 WHERE usert.userid = :theuserid";
        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'englishcentral',
            'theuserid' => $userid
        ] ;

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     *
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        // Find users with attempts.
        $sql = "SELECT usert.userid
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN  {englishcentral} actt ON actt.id = cm.instance
                  JOIN {englishcentral_attempts} usert ON usert.ecid = actt.id
                 WHERE c.id = :contextid";

        $params = [
            'contextid' => $context->id,
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'englishcentral',
        ];

        $userlist->add_from_sql('userid', $sql, $params);

    }

    /**
     * Export personal data for the given approved_contextlist.
     *
     * User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function _export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT usert.id as attemptid,
                       cm.id AS cmid,
                       usert.userid AS userid,
                       usert.videoid,
                        usert.watchcomplete as watchcomplete,
                        usert.watchtotal as watchtotal,
                        usert.watchcount as watchcount,
                        usert.watchlineids as watchlineids,
                        usert.learntotal as learntotal,
                        usert.learncount as learncount,
                        usert.learnwordids as learnwordids,
                        usert.speakcomplete as speakcomplete,
                        usert.speaktotal as speaktotal,
                        usert.speakcount as speakcount,
                        usert.speaklineids as speaklineids,
                        usert.totalpoints as totalpoints,
                        usert.sessiongrade as sessiongrade,
                        usert.sessionscore as sessionscore,
                        usert.activetime as activetime,
                        usert.totaltime as totaltime,
                        usert.timecompleted as timecompletec,
                        usert.timecreated as timecreated,
                        usert.status as status
                  FROM {englishcentral_attempts} usert
                  JOIN {englishcentral} actt ON usert.ecid = actt.id
                  JOIN {course_modules} cm ON actt.id = cm.instance
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {context} c ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                 WHERE c.id {$contextsql}
                   AND usert.userid = :userid
               ORDER BY usert.id, cm.id";
        $params = [
                'userid' => $user->id,
                'modulename' => 'englishcentral',
                'contextlevel' => CONTEXT_MODULE
            ] + $contextparams;

        $attempts = $DB->get_recordset_sql($sql, $params);


        foreach ($attempts as $attempt) {
            $attempt->timemodified =\core_privacy\local\request\transform::datetime($attempt->timecreated);
            $context = \context_module::instance($attempt->cmid);
            $attemptdata = get_object_vars($attempt);
            self::export_attempt_data_for_user($attemptdata, $context, $user);
        }
        $attempts->close();
    }

    /**
     * Export the supplied personal data for a single English Central attempt along with any generic data or area files.
     *
     * @param array $attemptdata the personal data to export
     * @param \context_module $context the context of the English Central.
     * @param \stdClass $user the user record
     */
    protected static function export_attempt_data_for_user(array $attemptdata, \context_module $context, \stdClass $user) {
        // Fetch the generic module data for the choice.
        $contextdata = helper::get_context_data($context, $user);

        // Merge with choice data and write it.
        $contextdata = (object)array_merge((array)$contextdata, $attemptdata);
        writer::with_context($context)->export_data([], $contextdata);

        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        if (!$cm = get_coursemodule_from_id('englishcentral', $context->instanceid)) {
            return;
        }

        $instanceid = $cm->instance;

        $attempts = $DB->get_records('englishcentral_attempts', ['ecid' => $instanceid], '', 'id');

        // Now delete all attempts
        $DB->delete_records('englishcentral_attempts', ['ecid' => $instanceid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {

                $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);

                $entries = $DB->get_records('englishcentral_attempts', ['ecid' => $instanceid, 'userid' => $userid],
                    '', 'id');

                if (!$entries) {
                    continue;
                }

                list($insql, $inparams) = $DB->get_in_or_equal(array_keys($entries), SQL_PARAMS_NAMED);

                // Now delete all user related entries.
                $DB->delete_records('englishcentral_attempts', ['ecid' => $instanceid, 'userid' => $userid]);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist    $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();
        $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
        list($userinsql, $userinparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $attemptswhere = "ecid = :instanceid AND userid {$userinsql}";
        $userinstanceparams = $userinparams + ['instanceid' => $instanceid];

        $attemptsset = $DB->get_recordset_select('englishcentral_attempts', $attemptswhere, $userinstanceparams, 'id', 'id');
        $attempts = [];

        foreach ($attemptsset as $attempt) {
            $attempts[] = $attempt->id;
        }

        $attemptsset->close();

        if (!$attempts) {
            return;
        }


        $deletewhere = "ecid = :instanceid AND userid {$userinsql}";
        $DB->delete_records_select('englishcentral_attempts', $deletewhere, $userinstanceparams);
    }
}
