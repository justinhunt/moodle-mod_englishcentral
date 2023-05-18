<?php

/**
 * Utils for minilesson plugin
 *
 * @package    mod_minilesson
 * @copyright  2020 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_englishcentral;
defined('MOODLE_INTERNAL') || die();

use \mod_englishcentral\constants;


class utils {

    public static function add_mform_elements($mform, $context,$setuptab=false) {
        global $CFG, $PAGE;
        $plugin = 'mod_englishcentral';
        $config = get_config($plugin);

        //if this is setup tab we need to add a field to tell it the id of the activity
        if($setuptab) {
            $mform->addElement('hidden', 'n');
            $mform->setType('n', PARAM_INT);
        }


        $dateoptions = array('optional' => true);
        $textoptions = array('size' => \mod_englishcentral_mod_form::TEXT_NUM_SIZE);


        $PAGE->requires->js_call_amd("$plugin/form", 'init');
        //-------------------------------------------------------------------------------
        $name = 'general';
        $label = get_string($name, 'form');
        $mform->addElement('header', $name, $label);
        //-------------------------------------------------------------------------------

        // Adding the standard "name" field
        $name = 'name';
        $label = get_string('activityname', $plugin);
        $mform->addElement('text', $name, $label, array('size'=>'64'));
        if (empty($CFG->formatstringstriptags)) {
            $mform->setType($name, PARAM_CLEAN);
        } else {
            $mform->setType($name, PARAM_TEXT);
        }
        $mform->addRule($name, null, 'required', null, 'client');
        $mform->addRule($name, get_string('maximumchars', null, 255), 'maxlength', 255, 'client');
        $mform->addHelpButton($name, 'activityname', $plugin);


        // Adding the standard "intro" and "introformat" fields
        //we do not support this in tabs
        if(!$setuptab) {
            $label = get_string('moduleintro');
            $mform->addElement('editor', 'introeditor', $label, array('rows' => 10), array('maxfiles' => EDITOR_UNLIMITED_FILES,
                    'noclean' => true, 'context' => $context, 'subdirs' => true));
            $mform->setType('introeditor', PARAM_RAW); // no XSS prevention here, users must be trusted
            $mform->addElement('advcheckbox', 'showdescription', get_string('showdescription'));
            $mform->addHelpButton('showdescription', 'showdescription');
        }


        //-----------------------------------------------------------------------------
        $name = 'timing';
        $label = get_string($name, 'form');
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);
        //-----------------------------------------------------------------------------

        $name = 'activityopen';
        $label = get_string($name, $plugin);
        $mform->addElement('date_time_selector', $name, $label, $dateoptions);
        $mform->addHelpButton($name, $name, $plugin);
        self::set_type_default_advanced($mform, $config, $name, PARAM_INT);

        $name = 'videoopen';
        $label = get_string($name, $plugin);
        $mform->addElement('date_time_selector', $name, $label, $dateoptions);
        $mform->addHelpButton($name, $name, $plugin);
        self::set_type_default_advanced($mform, $config, $name, PARAM_INT);

        $name = 'videoclose';
        $label = get_string($name, $plugin);
        $mform->addElement('date_time_selector', $name, $label, $dateoptions);
        $mform->addHelpButton($name, $name, $plugin);
        self::set_type_default_advanced($mform, $config, $name, PARAM_INT);

        $name = 'activityclose';
        $label = get_string($name, $plugin);
        $mform->addElement('date_time_selector', $name, $label, $dateoptions);
        $mform->addHelpButton($name, $name, $plugin);
        self::set_type_default_advanced($mform, $config, $name, PARAM_INT);

        //-------------------------------------------------------------------------------
        $name = 'goals';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);
        //-------------------------------------------------------------------------------

        $names = array('watchgoal' => 10,
                'learngoal' => 20,
                'speakgoal' => 10,
                'studygoal' => 60);
        foreach ($names as $name => $default) {
            $label = get_string($name, $plugin);
            $units = get_string($name.'units', $plugin);
            $elements = array(
                    $mform->createElement('text', $name, '', $textoptions),
                    $mform->createElement('static', '', '', $units)
            );
            $mform->addElement('group', $name.'group', $label, $elements, ' ', false);
            $mform->setType($name, PARAM_INT);
            $mform->setDefault($name, $default);
            $mform->addHelpButton($name.'group', $name, $plugin);
        }

        //-----------------------------------------------------------------------------
        $name = 'display';
        $label = get_string($name, 'form');
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);
        //-----------------------------------------------------------------------------

        $name = 'showduration';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, 1);

        self::set_type_default_advanced($mform, $config, $name, PARAM_INT);

        $name = 'showlevelnumber';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, 1);


        $name = 'showleveltext';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, 1);

        $name = 'showdetails';
        $label = get_string($name, $plugin);
        $options = array(get_string('no'),
                         get_string('showtostudentsonly', $plugin),
                         get_string('showtoteachersonly', $plugin),
                         get_string('showtoteachersandstudents', $plugin));
        $mform->addElement('select', $name, $label, $options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, 3);

    } //end of add_mform_elements

    /**
     * set_type_default_advanced
     *
     * @param $mform
     * @param $config
     * @param $name of field
     * @param $type PARAM_xxx constant value
     * @param $default (optional, default = null)
     * @todo Finish documenting this function
     */
    public static function set_type_default_advanced($mform, $config, $name, $type, $default=null) {
        $mform->setType($name, $type);
        if (isset($config->$name)) {
            $mform->setDefault($name, $config->$name);
        } else if ($default) {
            $mform->setDefault($name, $default);
        }
        $adv_name = 'adv'.$name;
        if (isset($config->$adv_name)) {
            $mform->setAdvanced($name, $config->$adv_name);
        }
    }

    public static function add_video($ecid,$videoid){
            global $DB;

            $table = 'englishcentral_videos';
            $record = array('ecid' => $ecid,
                'videoid' => $videoid);
            if ($record['videoid'] == $DB->get_field($table, 'videoid', $record)) {
                // video is already in our database - unexpected !!
            } else {
                if ($sortorder = $DB->get_field($table, 'MAX(sortorder)', array('ecid' => $ecid))) {
                    $sortorder++;
                } else {
                    $sortorder = 1;
                }
                $record['sortorder'] = $sortorder;
                $record['id'] = $DB->insert_record($table, $record);
            }
            return $record['id'];
    }


}